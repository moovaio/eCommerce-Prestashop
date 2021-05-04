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

include_once(_PS_MODULE_DIR_ . '/moova/Helper/Log.php');
include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');

class WebserviceSpecificManagementWebhookMoova implements WebserviceSpecificManagementInterface
{
    protected $objOutput;
    protected $output;
    protected $wsObject;

    public function __construct()
    {
        $this->moova = new MoovaSdk();
    }

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;
        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->objOutput->getObjectRender()->overrideContent($this->output);
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        return $this;
    }

    /**
     * @param WebserviceOutputBuilderCore $obj
     * @return WebserviceSpecificManagementInterface
     */

    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;
        return $this;
    }

    public function manage()
    {
        $body = json_decode(Tools::file_get_contents('php://input'), true);

        $trackingNumber = pSQL($body['internalCode']);

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "orders WHERE reference = '$trackingNumber' LIMIT 1";
        $listOfOrders = Db::getInstance()->executeS($sql);
        $orderId = sizeof($listOfOrders) > 0 ? $listOfOrders[0]['id_order'] : null;
        Log::info("manage - ID ORDER $orderId");
        $order = new Order($orderId);
        if (!$this->moova->isCarrierMoova($orderId)) {
            return;
        }

        $status = $body['status'];
        $prestashopStatusId = Configuration::get("RECEIVE_MOOVA_STATUS_$status", 'disabled');
        if ($prestashopStatusId != 'disabled') {
            Log::info("carrier - Changing status $status in order:$prestashopStatusId ");


            $order = new Order(1);
            $history = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->changeIdOrderState($prestashopStatusId, (int)($order->id));
        }
        $this->output = $body['status'];
    }
}
