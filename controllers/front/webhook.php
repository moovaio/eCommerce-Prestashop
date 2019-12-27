<?php

class MoovaLoginModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        return  $this->setTemplate('module:MOOVA/views/templates/webhook.tpl');
    }

    public function postProcess()
    {

        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'];
        $id = $data['id'];
        $date = $data['date'];
        $orderId = $this->getOrderId($id);
        if ($orderId == null) {
            return $this->ajaxDie(
                json_encode([
                    'Error' => 'Unathorized'
                ])
            );
        }


        $query = "INSERT INTO `prestashop`.`ps_moova_status` (`shipping_id`, `date`, `status`) VALUES ('$id', '$date', '$status')";

        $this->changeStatus($status, $orderId);
        Db::getInstance()->execute($query);
        $this->ajaxDie("ok");
    }

    private function changeStatus($statusMoova, $id)
    {
        $shipped = 4;
        $delivered = 5;
        if ($statusMoova === 'DELIVERED' || $statusMoova == 'INTRANSIT') {
            $state = $statusMoova === 'DELIVERED' ? $delivered : $shipped;
            $objOrder = new Order(7);
            $history = new OrderHistory();
            $history->id_order = (int) $objOrder->id;
            $history->changeIdOrderState($state, (int) ($objOrder->id));
            $history->addWithemail(true);
        }
    }


    private function getOrderId($trackingNumber)
    {
        $headers = getallheaders();
        $sql = "SELECT * FROM " . _DB_PREFIX_ . "order_carrier WHERE tracking_number='$trackingNumber'";
        $carrier = Db::getInstance()->ExecuteS($sql);
        if ($headers['Authorization'] == Configuration::get('MOOVA_KEY_AUTHENTICATION') && sizeof($carrier) > 0) {
            return $carrier[0]['id_order'];
        }
        return null;
    }
}
