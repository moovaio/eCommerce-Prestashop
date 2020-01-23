<?php

/**
 * 2007-2020 PrestaShop
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
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');

class AdminOrderMoovaController extends ModuleAdminController
{
    private $moova;

    public function __construct()
    {
        $this->MoovaSDK = new MoovaSdk();
        parent::__construct();
    }


    public function ajaxProcessRequest()
    {
        $trackingNumber = Tools::getValue('trackingNumber');
        echo $this->MoovaSDK->updateOrderStatus($trackingNumber, 'READY', null);
    }

    public function ajaxProcessLabel()
    {
        $trackingNumber = Tools::getValue('trackingNumber');
        echo json_encode($this->MoovaSDK->getShippingLabel($trackingNumber));
    }

    public function ajaxProcessOrder()
    {
        $order = new Order(Tools::getValue('order'));
        $products = $order->getProducts();
        $destination = $this->getDestination();
        $customer = new Customer((int) ($order->id_customer));
        $carrier = pSQL($order->getIdOrderCarrier());
        $destination->internalCode = $order->reference;
        $order = $this->MoovaSDK->processOrder(
            $destination,
            $products,
            $customer
        );

        $orderId = pSQL($order->id);
        $sql = "UPDATE " . _DB_PREFIX_ . "order_carrier SET tracking_number=$orderId WHERE id_order_carrier=$carrier";
        Db::getInstance()->execute($sql);

        return json_encode($order);
    }
}
