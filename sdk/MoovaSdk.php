<?php

include_once(_PS_MODULE_DIR_ . '/Moova/Api/MoovaApi.php');

class MoovaSdk
{
    private $api;
    public function __construct()
    {
        $this->api = new MoovaApi(
            Configuration::get('MOOVA_APP_ID', ''),
            Configuration::get('MOOVA_APP_KEY', ''),
            Configuration::get('MOOVA_LIVE_MODE', false)
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
        $street = $this->getAddress($to->address1);
        $payload = [
            'from' => [
                'street' => Configuration::get('MOOVA_ORIGIN_STREET', ''),
                'number' => Configuration::get('MOOVA_ORIGIN_NUMBER', ''),
                'floor' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
                'apartment' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
                'city' => Configuration::get('MOOVA_ORIGIN_CITY', ''),
                'state' => Configuration::get('MOOVA_ORIGIN_STATE', ''),
                'postalCode' => Configuration::get('MOOVA_ORIGIN_POSTAL_CODE', ''),
                'country' => Configuration::get('MOOVA_ORIGIN_COUNTRY')
            ],
            'to' => [
                'street' => $street['street'],
                'number' => $street['number'],
                'floor' => $to->address2,
                'city' => $to->city,
                'state' => $to->state,
                'postalCode' => $to->postcode,
                'country' => $to->country,
            ],
            'currency' => $to->currency,
            'conf' => [
                'assurance' => false,
                'items' => $this->getItems($items) //TODO FIX HEIGHT AND OTHER SHIT
            ],
            'type' => 'prestashop_24_horas_max'
        ];

        $res = $this->api->post('/v2/budgets', $payload);

        if (!$res || !isset($res->budget_id)) {
            return false;
        }

        return $res->price;
    }

    private function getItems($items)
    {
        $formated = [];
        foreach ($items as $item) {
            $formated = [
                "name" => $item["name"],
                "price" => $item["price"],
                "weight" => $item["weight"],
                "quantity" => $item["quantity"]
            ];
        }
        return $formated;
    }

    /**
     * Process an order in Moova's Api
     *
     * @return array|false
     */
    public function processOrder(\WC_Order $order)
    {

        $seller = Helper::get_seller_from_settings();
        $customer = Helper::get_customer_from_order($order);
        $items = Helper::get_items_from_order($order);
        $data_to_send = [
            'scheduledDate' => null,
            'currency' => 'ARS',
            'type' => 'regular',
            'flow' => 'manual',
            'from' => [
                'street' => $seller['street'],
                'number' => $seller['number'],
                'floor' => $seller['floor'],
                'apartment' => $seller['apartment'],
                'city' => $seller['city'],
                'state' => $seller['state'],
                'postalCode' => $seller['postalCode'],
                'country' => 'AR',
                'instructions' => $seller['instructions']
            ],
            'to' => [
                'street' => $customer['street'],
                'number' => $customer['number'],
                'floor' => $customer['floor'],
                'apartment' => $customer['apartment'],
                'city' => $customer['locality'],
                'state' => $customer['province'],
                'postalCode' => $customer['cp'],
                'country' => 'AR',
                'instructions' => $customer['extra_info']
            ],
            'conf' => [
                'assurance' => false,
                'items' => []
            ],
            'internalCode' => $order->get_id(),
            'description' => 'Pedido número ' . $order->get_id(),
            'label' => '',
            'type' => 'woocommerce_24_horas_max',
            'extra' => []
        ];
        $grouped_items = Helper::group_items($items);
        foreach ($grouped_items as $item) {
            $data_to_send['conf']['items'][] = ['item' => $item];
        }
        $res = $this->api->post('/shippings', $data_to_send);
        if (Helper::get_option('debug')) {
            Helper::log_debug(__FUNCTION__ . ' - Data enviada a Moova: ' . json_encode($data_to_send));
            Helper::log_debug(__FUNCTION__ . ' - Data recibida de Moova: ' . json_encode($res));
        }
        if (empty($res['id'])) {
            Helper::log_error('No se pudo procesar el pedido.');
            Helper::log_error(__FUNCTION__ . ' - Data enviada a Moova: ' . json_encode($data_to_send));
            Helper::log_error(__FUNCTION__ . ' - Data recibida de Moova: ' . json_encode($res));
            return false;
        }
        return $res;
    }

    /**
     * Gets the shipping label url for a Moova Shipment
     *
     * @param string $order_id
     * @return array|false
     */
    public function get_shipping_label(string $order_id)
    {
        $res = $this->api->get('/shippings/' . $order_id . '/label');
        if (Helper::get_option('debug')) {
            Helper::log_debug(__FUNCTION__ . ' - Data enviada a Moova: ' . $order_id);
            Helper::log_debug(__FUNCTION__ . ' - Data recibida de Moova: ' . json_encode($res));
        }
        if (empty($res['label'])) {
            Helper::log_error('No se pudo obtener etiqueta del pedido ' . $order_id);
            return false;
        }
        return $res;
    }

    /**
     * Gets the tracking status for a Moova Shipment
     *
     * @param string $order_id
     * @return array|false
     */
    public function get_tracking(string $order_id)
    {
        $res = $this->get_order($order_id);
        if (is_array($res)) {
            return $res['statusHistory'];
        }
        return false;
    }

    /**
     * Gets a Moova order
     *
     * @param string $order_id
     * @return array|false
     */
    public function get_order(string $order_id)
    {
        $res = $this->api->get('/shippings/' . $order_id);
        if (Helper::get_option('debug')) {
            Helper::log_debug(__FUNCTION__ . ' - Data enviada a Moova: ' . $order_id);
            Helper::log_debug(__FUNCTION__ . ' - Data recibida de Moova: ' . json_encode($res));
        }
        if (empty($res['id'])) {
            Helper::log_error('No se pudo obtener del pedido ' . $order_id);
            return false;
        }
        return $res;
    }

    /**
     * Gets the order status in Moova
     *
     * @param string $order_id
     * @return void
     */
    public function get_order_status(string $order_id)
    {
        $res = $this->get_order($order_id);
        if (is_array($res)) {
            return $res['status'];
        }
        return false;
    }

    /**
     * Updates the order status in Moova
     *
     * @param string $order_id
     * @param string $status
     * @param string $reason
     * @return false|array
     */
    public function update_order_status(string $order_id, string $status, string $reason = '')
    {
        $data_to_send = [];
        if ($reason) {
            $data_to_send['reason'] = $reason;
        }
        $res = $this->api->post('/shippings/' . $order_id . '/' . strtolower($status), $data_to_send);
        if (Helper::get_option('debug')) {
            Helper::log_debug(__FUNCTION__ . ' - Data enviada a Moova: ' . json_encode($data_to_send));
            Helper::log_debug(__FUNCTION__ . ' - Data recibida de Moova: ' . json_encode($res));
        }
        if (empty($res['status']) || strtoupper($res['status']) !== strtoupper($status)) {
            return false;
        }
        return $res;
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
