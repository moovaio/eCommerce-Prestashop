<?php

include_once(_PS_MODULE_DIR_ . '/moova/Api/MoovaApi.php');
include_once(_PS_MODULE_DIR_ . '/moova/Helper/Log.php');

class MoovaSdk
{
    private $api;
    public function __construct()
    {
        $liveMode = Configuration::get('MOOVA_LIVE_MODE') == true ? true : false;
        $this->api = new MoovaApi(
            Configuration::get('MOOVA_APP_ID', ''),
            Configuration::get('MOOVA_APP_KEY', ''),
            $liveMode
        );
    }

    /**
     * Gets a quote for an order
     *
     * @param array $from
     * @param array $to
     * @param array $items
     * @return array|false
     */
    public function getPrice($to, $items)
    {
        $currentCache = Cache::retrieve('moova_budget_request');
        $payload = $this->getOrderModel($to, $items);
        $destinationAddress = $currentCache ? $currentCache['to']['address'] : null;
        if ($destinationAddress != $payload['to']['address']) {
            Log::info("getPrice - sending to moova:" . json_encode($payload));
            $res = $this->api->post('/b2b/budgets/estimate', $payload);
            Cache::store('moova_budget_request', $payload);
            Cache::store('moova_budget_response', $res);
            Log::info("getPrice - received from moova:" . json_encode($res));
        } else {
            $res = Cache::retrieve('moova_budget_response');
        }

        if (!$res || !isset($res->budget_id)) {
            return false;
        }

        return $res->price;
    }


    public function getStatus($id)
    {
        $res = $this->api->get("/b2b/shippings/$id");
        if (!$res || !isset($res->statusHistory)) {
            return [];
        }
        Log::info("getStatus - Get status $id:" . json_encode($res));
        $res = json_decode(json_encode($res));
        return $res->statusHistory;
    }

    private function getOrderModel($to, $items)
    {
        return [
            'from' => [
                'address' => Configuration::get('MOOVA_ORIGIN_ADDRESS', ''),
                'googlePlaceId' => Configuration::get('MOOVA_ORIGIN_GOOGLE_PLACE_ID', ''),
                'floor' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
                'apartment' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
                'instructions' => Configuration::get('MOOVA_ORIGIN_COMMENT', ''),
                "contact" => [
                    "firstName" => Configuration::get('MOOVA_ORIGIN_NAME', ''),
                    "lastName" =>  Configuration::get('MOOVA_ORIGIN_SURNAME', ''),
                    "email" =>  Configuration::get('MOOVA_ORIGIN_EMAIL', ''),
                    "phone" => Configuration::get('MOOVA_ORIGIN_PHONE', '')
                ]
            ],
            'to' => $to,
            'description' => isset($to->description) ? (string) $to->description : '',
            'conf' => [
                'assurance' => false,
                'items' => $this->getItems($items)
            ],
            'type' => 'prestashop_24_horas_max',
            'flow' => 'manual',
        ];
    }

    private function getItems($items)
    {
        $formated = [];
        foreach ($items as $item) {
            $prefix = isset($item["name"]) ? '' : 'product_';
            $formated = [
                "name" => $item[$prefix . 'name'],
                "price" =>  $item[$prefix . "price"],
                "weight" =>  $item["weight"],
                "length" =>  $item["depth"],
                "width" =>  $item["width"],
                "height" =>  $item["height"],
                "quantity" => $item[$prefix . 'quantity'],
            ];
        }
        return $formated;
    }

    /**
     * Process an order in Moova's Api
     *
     * @return array|false
     */
    public function processOrder($order)
    {
        $cart = new Cart($order->id_cart);
        $items = $order->getProducts();
        $to =  $this->getDestination($cart);
        $to['description'] = $order->getFirstMessage();
        $contact = new Customer((int) ($order->id_customer));
        $payload =  $this->getOrderModel($to, $items);
        $payload['internalCode'] = $order->reference;

        $payload["to"]["contact"] = [
            "firstName" => $contact->firstname,
            "lastName" =>  $contact->lastname,
            "email" =>   $contact->email,
            "phone" => $to['phone']
        ];

        Log::info("processOrder - Sending" . json_encode($payload));
        $res = $this->api->post('/b2b/shippings', $payload);
        Log::info("processOrder - Received from Moova" . json_encode($res));

        // From an Order ID you have 
        $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());

        // 2- Set tracking number
        $orderCarrier->tracking_number = $res->id;
        $orderCarrier->save();
        return $res;
    }

    /**
     * Gets the shipping label url for a Moova Shipment
     *
     * @param  $order_id
     * @return array|false
     */
    public function getShippingLabel($orderId)
    {
        $res = $this->api->get("/b2b/shippings/$orderId/label");
        Log::info("getShippingLabel " . json_encode($res));
        if (!isset($res->label)) {
            return false;
        }
        return $res;
    }

    /**
     * Updates the order status in Moova
     *
     * @param  $orderId
     * @param  $status
     * @param  $reason
     * @return false|array
     */
    public function updateOrderStatus($orderId,  $status, $reason = null)
    {
        $payload = [];
        if ($reason) {
            $payload['reason'] = $reason;
        }
        $res = $this->api->post('/b2b/shippings/' . $orderId . '/' . strtolower($status), $payload);
        Log::info("updateOrderStatus " . json_encode($res));
        return json_encode($res);
    }

    /**
     * Gets the autocomplete
     *
     * @param  $order_id
     * @return array|false
     */
    public function getAutocomplete($query)
    {
        return $this->api->get("/autocomplete", ["query" => $query]);
    }

    public function getDestination($cart)
    {

        $id_address_delivery = $cart->id_address_delivery;
        $destination = json_decode(json_encode(new Address($id_address_delivery), true), true);

        //Add country
        if (Configuration::get('MAP_MOOVA_CHECKOUT_country') === 'id_country') {
            $country = new Country($destination['id_country']);
            $country = $country->name[1];
        } else {
            $country = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_country');
        }

        //Add state 
        if (Configuration::get('MAP_MOOVA_CHECKOUT_state') === 'id_state') {
            $state = new State($destination['id_state']);
            $state = $state->name;
        } else {
            $state = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_state');
        }

        $floor = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_floor');
        $city = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_city');
        $postalCode = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_postalCode');
        $instructions = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_instructions');
        $address = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_address');

        $appendTo = [$city, $state, $country];
        foreach ($appendTo as $append) {
            if ($append) {
                $address .= ",$append";
            }
        }
        return [
            'address' => $address,
            'floor' =>  $floor,
            'postalCode' => $postalCode,
            'instructions' => $instructions,
            'phone' => $destination['phone']
        ];
    }

    private function checkIsset($param, $configkey)
    {
        $key = Configuration::get($configkey);
        return isset($param[$key]) ? $param[$key] : '';
    }
}
