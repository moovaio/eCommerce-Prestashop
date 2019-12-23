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
    public function getPrice($to,$items)
    {
        $payload = [
            'from' => [
                'street' => $from['street'],
                'number' => $from['number'],
                'floor' => $from['floor'],
                'apartment' => $from['apartment'],
                'city' => $from['city'],
                'state' => $from['state'],
                'postalCode' => $from['postalCode'],
                'country' => 'AR',
                  'required' => true
            ],
            'to' => [
                'street' => $to->address1,
                'floor' => $to->address2,
                'city' => $to->city,
                'state' => $to->province,
                'postalCode' => $to->postcode,
                'country' => $to->country,
            ],
            'conf' => [
                'assurance' => false,
                'items' => $this->getItems($items)
            ],
            'type' => 'prestashop_24_horas_max'
        ]; 
        
        $res = $this->api->post('/v2/budgets', $payload);  
        throw new Error(json_encode($res));
        if (!$res || empty($res['budget_id'])) {
            return false;
        }
        
        return $res["price"];
    }

    private function getItems($items){
        $formated = [];
        foreach ($items as $item) {
            $formated=[
                "name"=>$item["name"],
                "price"=>$item["price"],
                "weight"=>$item["weight"],
                "quantity"=>$item["quantity"]
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
}