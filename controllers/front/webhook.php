<?php
/**
 * 2007-2019 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class MoovaWebhookModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        return  $this->setTemplate('module:MOOVA/views/templates/front/webhook.tpl');
    }

    public function postProcess()
    {

        $data = json_decode(Tools::file_get_contents('php://input'), true);
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


        $query = "INSERT INTO `prestashop`." . _DB_PREFIX_ .
            "moova_status (`shipping_id`, `date`, `status`) VALUES ('$id', '$date', '$status')";

        $this->changeStatus($status, $orderId);
        Db::getInstance()->execute($query);
        $this->ajaxDie("ok");
    }

    private function changeStatus($statusMoova, $id)
    {
        $shipped = 4;
        $delivered = 5;
        if ($statusMoova === 'READY') {
            return; //This will be saving manually
        }
        if ($statusMoova === 'DELIVERED' || $statusMoova == 'INTRANSIT') {
            $state = $statusMoova === 'DELIVERED' ? $delivered : $shipped;
            $objOrder = new Order($id);
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
