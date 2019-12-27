<?php
require_once(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
require_once(dirname(__FILE__) . '/../moova.php');

$context = Context::getContext();

// Instance of module class
$moova = new Moova();

switch (Tools::getValue('action')) {
    case 'processOrder':
        echo $moova->processOrder(Tools::getValue('order'));
        break;
    case 'updateOrderStatus':
        echo $moova->updateOrderStatus(Tools::getValue('trackingNumber'), 'READY', NULL);
        break;
    case 'getLabel':
        echo $moova->getShippingLabel(Tools::getValue('trackingNumber'));
        break;
    default:
        die('error');
}
