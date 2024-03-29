<?php

/**
 * 2007-2021·PrestaShop Moova
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Moova SA <help@moova.io>
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

include_once(_PS_MODULE_DIR_ . '/moova/Api/MoovaApi.php');
include_once(_PS_MODULE_DIR_ . '/moova/Helper/Log.php');

class MoovaSdk
{
    private $api;
    public function __construct()
    {
        $prefix = '';
        $liveMode = Configuration::get('MOOVA_LIVE_MODE') == true;
        if (!$liveMode) {
            $prefix = 'DEV_';
        }
        $this->api = new MoovaApi(
            Configuration::get($prefix . 'MOOVA_APP_ID', ''),
            Configuration::get($prefix . 'MOOVA_APP_KEY', ''),
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
        $res = '';
        /*$destinationAddress = $currentCache && isset($currentCache['to']['address']) ? $currentCache['to']['address'] : null;
        $isBudgetCached = isset($payload['to']['address']) && $destinationAddress != $payload['to']['address'];
        if (false) {
            Log::info('Getting from cache');
            $res = Cache::retrieve('moova_budget_response');
            return isset($res->price) ? $res->price : false;
        }
        Cache::store('moova_budget_request', $payload);*/
        if (!empty($payload['to']['lat'])) {
            unset($payload['to']['postalCode']);
            unset($payload['from']['postalCode']);
        }
        //Log::info("getPrice - first estimate" . json_encode($payload));
        try {
            $res = $this->api->post('/b2b/budgets/estimate', $payload);
        } catch (Exception $error) {
            Log::info("Unable to make a budget,trying next time");
        }

        if (empty($res->budget_id) && !empty($payload['to']['postalCode'])) {
            unset($payload['to']['address']);
            unset($payload['to']['coords']);
            Log::info("getPrice - second estimate" . json_encode($payload));
            $res = $this->api->post('/b2b/budgets/estimate', $payload);
        }
        Log::info("getPrice - response" . json_encode($res));
        Cache::store('moova_budget_response', $res);
        return isset($res->price) ? $res->price : false;
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
                ],
                'country' => ''
            ],
            'to' => $to,
            'description' => isset($to->description) ? (string) $to->description : '',
            'conf' => [
                'assurance' => false
            ],
            'items' => $this->getItems($items),
            'type' => 'prestashop_24_horas_max',
            'flow' => 'manual',
        ];
    }

    private function getItems($items)
    {
        $formated = [];
        foreach ($items as $item) {
            $prefix = isset($item["name"]) ? '' : 'product_';
            $formated[] = [
                "name" => $item[$prefix . 'name'],
                "price" =>  $item[$prefix . "price"],
                "weight" =>  $item["weight"] * 1000,
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
        try {
            // From an Order ID you have 
            $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());
            Log::info("processOrder - starting with:" . json_encode($orderCarrier));
            if (!empty($orderCarrier->tracking_number)) {
                Log::info("processOrder - Order already created");
                return;
            }

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


            // 2- Set tracking number
            $orderCarrier->tracking_number = $res->id;
            $orderCarrier->save();
            return $res;
        } catch (Exception $error) {
            Log::info($error);
        }
    }

    public function isCarrierMoova($orderId)
    {
        $moovaCarrier = Carrier::getCarrierByReference(Configuration::get('MOOVA_CARRIER_ID_REFERENCE'));
        $order = new Order($orderId);
        $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());
        $carrier = new Carrier($order->id_carrier);
        Log::info('isCarrierMoova - order carrier:' . json_encode($orderCarrier));
        Log::info('isCarrierMoova - moova carrier:' . json_encode($carrier));
        return $carrier->id_reference === $moovaCarrier->id_reference;
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
        return $res;
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

    public function setCompanyWebhook($payload)
    {
        return $this->api->patch("/b2b/applications/webhooks", $payload);
    }

    public function getDestination($cart)
    {
        $id_address_delivery = $cart->id_address_delivery;
        $sql = new DbQuery();
        $sql->select('*')->from('address')->where("id_address=$id_address_delivery");
        $address = Db::getInstance()->executeS($sql);
        if (empty($address)) {
            return null;
        }
        $destination = $address[0];
        Log::info('getDestination -' . json_encode($destination));
        //Add country
        $country = new Country($destination['id_country']);
        $country_name = $country->name[1];
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
        $street = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_address');

        if (!Configuration::get('GOOGLE_API_KEY', '')) {
            $appendTo = [$city, $state, $country_name];
            foreach ($appendTo as $append) {
                if ($append) {
                    $street .= ",$append";
                }
            }
        }
        $lat = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_lat');
        $lng = $this->checkIsset($destination, 'MAP_MOOVA_CHECKOUT_lng');
        $address = [
            'address' => $street
        ];

        if ($lat && $lng) {
            $address = [
                "addressDescription" => $street,
                "coords" => [
                    "lat" => $lat,
                    "lng" => $lng
                ]
            ];
        }
        Log::info('getDestination- fullAddress:' . json_encode($address));
        return array_merge($address, [
            'floor' =>  $floor,
            'postalCode' => $postalCode,
            'instructions' => $instructions,
            'phone' => $destination['phone'],
            'country' => $country->iso_code
        ]);
    }

    private function checkIsset($param, $configkey)
    {
        $key = Configuration::get($configkey);
        return empty($param[$key]) ? '' : $param[$key];
    }
}
