<?php

/**
 * 2007-2020·PrestaShop PrestaShop
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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');

class Moova extends CarrierModule
{
    protected $config_form = false;
    protected $ORDER_TAB = 'AdminMoovaOrderController';
    protected $MOOVA_WEBHOOK = 'moovaApi';
    public $id_carrier;

    public function __construct()
    {
        $this->name = 'moova';
        $this->tab = 'shipping_logistics';
        $this->author = 'Moova.io';
        $this->version = '1.0.5';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->author = 'Moova.io';
        $this->need_instance = 0;
        $this->moova = new MoovaSdk();
        $this->controllers = array('dpage');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        $this->module_key = '8d7853cc1d2a2821ca4e4e41dc2db3e6';

        parent::__construct();
        $this->displayName = $this->l('Moova');
        $this->description = $this->l('This extension allows you to create shippings with Moova');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall  Moova?');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $carrier = $this->addCarrier();
        $this->addZones($carrier);
        $this->addGroups($carrier);
        $this->addRanges($carrier);

        require_once(dirname(__FILE__) . '/sql/install.php');

        Configuration::updateValue('MOOVA_LIVE_MODE', false);
        return parent::install() &&
            $this->installAdminControllers() &&
            $this->registerHook('moduleRoutes')  &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            $this->registerHook('displayAdminOrderRight');
    }

    public function getAdminControllers()
    {
        return [
            [
                'class_name' => 'AdminMoovaSetupController',
                'active' => true,
                'name' => array(
                    'en' => 'Setup',
                ),
            ], [
                'class_name' => 'AdminMoovaOrderController',
                'active' => true,
                'name' => array(
                    'en' => 'Order',
                ),
            ],
        ];
    }
    /**
     * Add Tabs for our ModuleAdminController
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function installAdminControllers()
    {
        foreach ($this->getAdminControllers() as $tabData) {
            if (Tab::getIdFromClassName($tabData['class_name'])) {
                continue;
            }
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;

            foreach (Language::getLanguages(false) as $language) {
                $tab->name[$language['id_lang']] = $tabData['name']['en'];
            }

            $tab->active = $tabData['active'];
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOOVA_LIVE_MODE');
        return parent::uninstall() && $this->uninstallTab();
    }

    private function uninstallTab()
    {
        foreach ($this->getAdminControllers() as $tabData) {
            $tabId = Tab::getIdFromClassName($tabData['class_name']);
            if (!$tabId) {
                continue;
            }
            $tab = new Tab($tabId);
            $tab->delete();
        }
        return true;
    }

    public function addOrderState($name)
    {
        $state_exist = false;
        $states = OrderState::getOrderStates((int) $this->context->language->id);

        // check if order state exist
        foreach ($states as $state) {
            if (in_array($name, $state)) {
                $state_exist = true;
                break;
            }
        }

        // If the state does not exist, we create it.
        if (!$state_exist) {
            // create new order state
            $order_state = new OrderState();
            $order_state->color = '#00ffff';
            $order_state->send_email = true;
            $order_state->module_name = 'name if your module';
            $order_state->template = 'name of your email template';
            $order_state->name = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                $order_state->name[$language['id_lang']] = $name;
            }

            // Update object
            $order_state->add();
        }

        return true;
    }

    public function hookDisplayAdminOrderRight($param)
    {
        $this->context->controller->addJquery();

        $order = Order::getOrderByCartId(Context::getContext()->cart->id);
        $orderCarrier = $this->getOrderCarrier($order);
        $carrier =  new Carrier($orderCarrier['id_carrier']);
        $isMoovaCarrier =  $carrier->name === 'Moova' && $carrier->external_module_name === 'moova';

        if (!$isMoovaCarrier) {
            return;
        }

        $trackingNumber = $orderCarrier ? $orderCarrier['tracking_number'] : false;
        $status = $trackingNumber ? $this->moova->getStatus($trackingNumber) : [];

        Media::addJsDef(["Moova" => [
            "trackingNumber" => $trackingNumber
        ]]);

        $this->context->smarty->assign(array(
            'token' => Tools::getAdminTokenLite($this->ORDER_TAB),
            'trackingNumber' => $trackingNumber,
            'status' => $status
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/order.tpl');
        return $output;
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminOrders') {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return Tools::redirectAdmin($this->context->link->getAdminLink('AdminMoovaSetup'));
    }

    public function getDestination($cart)
    {
        $id_address_delivery = $cart->id_address_delivery;
        $destination = new Address($id_address_delivery);
        $country = new Country($destination->id_country);
        $currency = new Currency($cart->id_currency);
        $state = new State($destination->id_state);

        $destination->country = $country->iso_code;
        $destination->currency = $currency->iso_code;
        $destination->state = $state->name;
        return $destination;
    }

    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $controller = $this->getHookController('getOrderShippingCost');
        return $controller->run($cart, $shipping_cost);
    }

    public function getHookController($hook_name)
    {
        require_once(dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php');
        $controller_name = $this->name . $hook_name . 'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    protected function addCarrier()
    {
        $carrier = new Carrier();

        $carrier->name = $this->l('Moova');
        $carrier->is_module = true;
        $carrier->url = 'https://dashboard.moova.io/external?id=@';
        $carrier->active = 1;
        $carrier->range_behavior = 0;
        $carrier->shipping_handling = false;
        $carrier->need_range = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = $this->l('24 hours delivery');
        }

        if ($carrier->add() == true) {
            @copy(dirname(__FILE__) .
                '/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_ . '/'
                . (int) $carrier->id . '.jpg');
            Configuration::updateValue('MOOVA_CARRIER_ID', (int) $carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group) {
            $groups_ids[] = $group['id_group'];
        }

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $SOUTH_AMERICA = 6;
        $carrier->addZone($SOUTH_AMERICA);
    }


    private function getOrderCarrier($order)
    {
        try {
            $order = new Order($order);
            $carrier = $order->getIdOrderCarrier();
            $sql = "SELECT * FROM " . _DB_PREFIX_ . "order_carrier WHERE id_order_carrier=$carrier";
            $carrier = Db::getInstance()->ExecuteS($sql);
            return $carrier[0];
        } catch (Exception $e) {
            return false;
        }
    }
}
