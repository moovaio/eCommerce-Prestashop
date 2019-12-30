<?php

include_once(_PS_MODULE_DIR_ . '/moova/Api/MoovaApi.php');

class MoovaSdk
{
    private $api;
    public function __construct()
    {
        $this->api = new MoovaApi(
            Configuration::get('MOOVA_APP_ID', ''),
            Configuration::get('MOOVA_APP_KEY', ''),
            Configuration::get('MOOVA_LIVE_MODE', false) ?? false
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
        $payload =  $this->getOrderModel($to, $items);
        $res = $this->api->post('/v2/budgets', $payload);
        if (!$res || !isset($res->budget_id)) {
            return false;
        }

        return $res->price;
    }

    private function getOrderModel($to, $items)
    {
        $street = $this->getAddress($to->address1);
        return [
            'from' => [
                'street' => Configuration::get('MOOVA_ORIGIN_STREET', ''),
                'number' => Configuration::get('MOOVA_ORIGIN_NUMBER', ''),
                'floor' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
                'apartment' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
                'city' => Configuration::get('MOOVA_ORIGIN_CITY', ''),
                'state' => Configuration::get('MOOVA_ORIGIN_STATE', ''),
                'postalCode' => Configuration::get('MOOVA_ORIGIN_POSTAL_CODE', ''),
                'country' => Configuration::get('MOOVA_ORIGIN_COUNTRY'),
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
                'floor' => $to->address2,
                'city' => $to->city,
                'state' => $to->state,
                'postalCode' => $to->postcode,
                'country' => $to->country,
                'instructions' => $to->other,
            ],
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
        $payload =  $this->getOrderModel($to, $items);
        $payload['internalCode'] = $to->internalCode;

        $payload["to"]["contact"] = [
            "firstName" => $contact->firstname,
            "lastName" =>  $contact->lastname,
            "email" =>   $contact->email,
            "phone" => $to->phone
        ];
        $res = $this->api->post('/shippings', $payload);
        return $res;
    }

    /**
     * Gets the shipping label url for a Moova Shipment
     *
     * @param string $order_id
     * @return array|false
     */
    public function getShippingLabel(string $orderId)
    {
        $res = $this->api->get("/shippings/$orderId/label");
        if (!isset($res->label)) {
            return false;
        }
        return $res;
    }

    /**
     * Updates the order status in Moova
     *
     * @param string $orderId
     * @param string $status
     * @param string $reason
     * @return false|array
     */
    public function updateOrderStatus(string $orderId, string $status, $reason = '')
    {
        $payload = [];
        if ($reason) {
            $payload['reason'] = $reason;
        }
        $res = $this->api->post('/shippings/' . $orderId . '/' . strtolower($status), $payload);
        if (!isset($res->id)) {
            return false;
        }
        $query = "INSERT INTO " . _DB_PREFIX_ . "moova_status (`shipping_id`, `date`, `status`) VALUES ('$orderId', '$res->created_at', 'READY')";
        Db::getInstance()->execute($query);
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
}
