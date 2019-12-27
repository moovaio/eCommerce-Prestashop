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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/Moova/sdk/MoovaSdk.php');

class Moova extends CarrierModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'moova';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Moova.io';
        $this->need_instance = 0;
        $this->moova = new MoovaSdk();
        $this->controllers = array('dpage');

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Moova');
        $description = 'This Moova extension allows you to display real-time shipping quotes'
        . ' to your customers based on their cart details and shipping address.' .
        ' We produce shipping labels that can be downloaded both from this extension or on the website.' .
        ' Furthermore, we give you a real-time tracking URL so you and your client can follow your shipping at ' .
        'every time.';
        $this->description = $this->l($description);

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
        Configuration::updateValue('MOOVA_KEY_AUTHENTICATION', $this->randKey(40));

        return parent::install() &&
            $this->registerHook('moduleRoutes')  &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            $this->registerHook('displayAdminOrderRight');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOOVA_LIVE_MODE');
        return parent::uninstall();
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

        //If create shipping
        //Get everything i need
        //Send it
        $this->context->controller->addJquery();

        $order = Order::getOrderByCartId(Context::getContext()->cart->id);
        $carrier = $this->getCarrier($order);
        $trackingNumber = $carrier ? $carrier['tracking_number'] : false;

        Media::addJsDef(["Moova" => [
            "trackingNumber" => $trackingNumber
        ]]);

        $this->context->smarty->assign('trackingNumber', $trackingNumber);
        $status = $this->getStatusMoova($trackingNumber);

        $this->context->smarty->assign('status', $status);
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
     * displayAdminOrderRight
     */
    private function getStatusMoova($trackingNumber)
    {
        $sql = "SELECT * FROM "
            . _DB_PREFIX_ .
            "moova_status where shipping_id='$trackingNumber' order by id_moova desc";
        return Db::getInstance()->ExecuteS($sql);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitMoovaModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMoovaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm([$this->getConfigForm(), $this->getOriginForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'MOOVA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode. ' .
                            'Remember app id and key are different in production'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter the app id'),
                        'name' => 'MOOVA_APP_ID',
                        'label' => $this->l('App id'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'MOOVA_APP_KEY',
                        'label' => $this->l('App key'),
                        'desc' => $this->l('Enter the app key'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'MOOVA_KEY_AUTHENTICATION',
                        'label' => $this->l('App authentication'),
                        'desc' => $this->l('Save this key and put it in Moova.io'),
                        'required' => true
                    )


                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }


    /**
     * Get address config
     */
    protected function getOriginForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Origin address'),
                    'icon' => 'icon-home',
                ),

                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Name'),
                        'name' => 'MOOVA_ORIGIN_NAME',
                        'label' => $this->l('Name'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Surname'),
                        'name' => 'MOOVA_ORIGIN_SURNAME',
                        'label' => $this->l('Surname'),
                        'required' => false
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('email'),
                        'name' => 'MOOVA_ORIGIN_EMAIL',
                        'label' => $this->l('Email'),
                        'required' => false
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('phone'),
                        'name' => 'MOOVA_ORIGIN_PHONE',
                        'label' => $this->l('Phone'),
                        'required' => false
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Street'),
                        'name' => 'MOOVA_ORIGIN_STREET',
                        'label' => $this->l('Street'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Number'),
                        'name' => 'MOOVA_ORIGIN_NUMBER',
                        'label' => $this->l('Number'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Floor'),
                        'name' => 'MOOVA_ORIGIN_FLOOR',
                        'label' => $this->l('Floor'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Apartment'),
                        'name' => 'MOOVA_ORIGIN_APARTMENT',
                        'label' => $this->l('Apartment'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('City'),
                        'name' => 'MOOVA_ORIGIN_CITY',
                        'label' => $this->l('City'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('State'),
                        'name' => 'MOOVA_ORIGIN_STATE',
                        'label' => $this->l('State'),
                        'required' => true
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Postal code'),
                        'name' => 'MOOVA_ORIGIN_POSTAL_CODE',
                        'label' => $this->l('Postal Code'),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Country:'),
                        'desc' => $this->l('Only argentina and chile available'),
                        'name' => 'MOOVA_ORIGIN_COUNTRY',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_option' => 'AR',
                                    'name' => 'Argentina'
                                ),
                                array(
                                    'id_option' => 'CHL',
                                    'name' => 'Chile'
                                ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Special observation. Example: red door'),
                        'name' => 'MOOVA_ORIGIN_COMMENT',
                        'label' => $this->l('Description'),
                        'required' => true
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MOOVA_LIVE_MODE' => Configuration::get('MOOVA_LIVE_MODE', true),
            'MOOVA_KEY_AUTHENTICATION' => Configuration::get('MOOVA_KEY_AUTHENTICATION', ''),
            'MOOVA_APP_ID' => Configuration::get('MOOVA_APP_ID', ''),
            'MOOVA_APP_KEY' => Configuration::get('MOOVA_APP_KEY', ''),
            'MOOVA_ORIGIN_COUNTRY' => Configuration::get('MOOVA_ORIGIN_COUNTRY', ''),
            'MOOVA_ORIGIN_PHONE' => Configuration::get('MOOVA_ORIGIN_PHONE', ''),
            'MOOVA_ORIGIN_NAME' => Configuration::get('MOOVA_ORIGIN_NAME', ''),
            'MOOVA_ORIGIN_SURNAME' => Configuration::get('MOOVA_ORIGIN_SURNAME', ''),
            'MOOVA_ORIGIN_EMAIL' => Configuration::get('MOOVA_ORIGIN_EMAIL', ''),
            'MOOVA_ORIGIN_COMMENT' => Configuration::get('MOOVA_ORIGIN_COMMENT', ''),
            'MOOVA_ORIGIN_STREET' => Configuration::get('MOOVA_ORIGIN_STREET', ''),
            'MOOVA_ORIGIN_NUMBER' => Configuration::get('MOOVA_ORIGIN_NUMBER', ''),
            'MOOVA_ORIGIN_FLOOR' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
            'MOOVA_ORIGIN_APARTMENT' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
            'MOOVA_ORIGIN_CITY' => Configuration::get('MOOVA_ORIGIN_CITY', ''),
            'MOOVA_ORIGIN_STATE' => Configuration::get('MOOVA_ORIGIN_STATE', ''),
            'MOOVA_ORIGIN_POSTAL_CODE' => Configuration::get('MOOVA_ORIGIN_POSTAL_CODE', '')

        );
    }

    private function randKey($length)
    {
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= chr(mt_rand(33, 126));
        }
        return $random;
    }
    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getOrderShippingCost()
    {
        try {
            if (Context::getContext()->customer->logged == true) {
                $destination = $this->getDestination();
                $products = Context::getContext()->cart->getProducts(true);
                $price = $this->moova->getPrice(
                    $destination,
                    $products
                );
                return $price;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getDestination()
    {
        $id_address_delivery = Context::getContext()->cart->id_address_delivery;
        $destination = new Address($id_address_delivery);
        $country = new Country($destination->id_country);
        $currency = new Currency(Context::getContext()->cart->id_currency);
        $state = new State($destination->id_state);

        $destination->country = $country->iso_code;
        $destination->currency = $currency->iso_code;
        $destination->state = $state->name;
        return $destination;
    }


    public function processOrder($order)
    {
        $order = new Order($order);
        $products = $order->getProducts();
        $destination = $this->getDestination();

        $carrier = $order->getIdOrderCarrier();
        $destination->internalCode = $order->reference;

        $order = $this->moova->processOrder(
            $destination,
            $products
        );

        $sql = "UPDATE " .
            _DB_PREFIX_ .
            "order_carrier SET tracking_number='$order->id' WHERE id_order_carrier=$carrier";
        Db::getInstance()->execute($sql);

        return json_encode($order);
    }

    protected function addCarrier()
    {
        $carrier = new Carrier();

        $carrier->name = $this->l('Moova');
        $carrier->is_module = true;
        $carrier->url = 'https://dashboard.moova.io/external?id=@';
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = $this->l('24 hours delivery');
        }

        if ($carrier->add() == true) {
            @copy(dirname(__FILE__) .
                '/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_ . '/'
                . (int) $carrier->id . '.jpg');
            Configuration::updateValue('MYSHIPPINGMODULE_CARRIER_ID', (int) $carrier->id);
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
        //$zones = Zone::getZones();
        $SOUTH_AMERICA = 6;
        $carrier->addZone($SOUTH_AMERICA);
    }

    public function updateOrderStatus($trackingNumber, $status, $reason)
    {
        return $this->moova->updateOrderStatus($trackingNumber, $status, $reason);
    }

    public function getShippingLabel($trackingNumber)
    {
        return json_encode($this->moova->getShippingLabel($trackingNumber));
    }

    private function getCarrier($order)
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

    public function hookModuleRoutes()
    {
        return array(
            'module-Moova-webhook' => array(
                'controller' => 'webhook',
                'rule' =>  'moova/webhook',
                'keywords' => array(
                    'id_customer'  => array('regexp' => '[0-9]+', 'param' => 'id_customer'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'Moova',
                )
            )
        );
    }
}
