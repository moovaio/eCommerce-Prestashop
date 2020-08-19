<?php

include_once(_PS_MODULE_DIR_ . '/moova/Api/MoovaApi.php');

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
        if (!isset($to->address1)) return false;
        $payload =  $this->getOrderModel($to, $items);
        $res = $this->api->post('/b2b/budgets/estimate', $payload);
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
        $res = json_decode(json_encode($res));
        return $res->statusHistory;
    }

    private function getOrderModel($to, $items)
    {
        $street = $this->getAddress($to->address1);
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
            'to' => [
                'street' => $street['street'],
                'number' => $street['number'],
                'floor' =>  isset($to->address2) ? $to->address2 : '',
                'city' => $to->city,
                'state' => isset($to->state) ? $to->state : $to->city,
                'postalCode' => isset($to->postcode) ? $to->postcode : null,
                'country' => $to->country,
                'instructions' =>  isset($to->other) ? $to->other : '',
            ],
            'description' => isset($to->description) ? (string) $to->description : '',
            'currency' => $to->currency,
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
    public function processOrder($to, $items, $contact)
    {
        if (!isset($to->address1)) return false;
        $payload =  $this->getOrderModel($to, $items);
        $payload['internalCode'] = $to->internalCode;

        $payload["to"]["contact"] = [
            "firstName" => $contact->firstname,
            "lastName" =>  $contact->lastname,
            "email" =>   $contact->email,
            "phone" => $to->phone
        ];
        $res = $this->api->post('/b2b/shippings', $payload);
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
        return json_encode($res);
    }

    public static function getAddress($fullStreet)
    {
        //Now let's work on the first line
        preg_match('/(^\d*[\D]*)(\d+)(.*)/i', $fullStreet, $res);
        $line1 = $res;

        if ((isset($line1[1]) && !empty($line1[1]) && $line1[1] !== " ") && !empty($line1)) {
            //everything's fine. Go ahead 
            $street_name = trim($line1[1]);
            $street_number = trim($line1[2]);
        }
        return array('street' => $street_name, 'number' => $street_number);
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
}
