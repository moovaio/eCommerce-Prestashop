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

class AdminMoovaSetupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->moova = new MoovaSdk();
        parent::__construct();
    }

    protected $headerToolBar = true;

    public function init()
    {
        if (\Tools::isSubmit($this->controller_name . '_config')) {
            $this->postProcess();
        }
        $this->initConfigForm();
        $this->initOriginForm();
        $this->initFreeShippingForm();
        $this->initSpecialPricingForm();
        $this->initMappingForm();
        $this->initMappingFromMoovaToPrestashop();
        $this->editCheckoutFields();
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        $message = ['status' => 'waiting'];
        if (\Tools::isSubmit($this->controller_name . '_config')) {
            $message = $this->validateformIsComplete();
        }
        $this->context->smarty->assign('message', $message);
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addJS("/modules/{$this->module->name}/views/js/settings.js");
        $this->context->smarty->assign(
            array(
                'token' => Tools::getAdminTokenLite($this->controller_name)
            )
        );


        $this->context->smarty->assign("module_dir", "/modules/{$this->module->name}");
        $configurePath = _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/configure.tpl';
        $output = $this->context->smarty->fetch($configurePath);
        $renderedForm = $this->renderForm();
        $this->context->smarty->assign('content', $output . $renderedForm);
    }


    public function renderForm($fields_form = null)
    {
        if ($fields_form === null) {
            $fields_form = $this->fields_form;
        }

        $helper = new \HelperForm();
        $helper->token = \Tools::getAdminTokenLite($this->controller_name);
        $helper->currentIndex = \AdminController::$currentIndex;
        $helper->submit_action = $this->controller_name . '_config';
        $default_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = array(
            'fields_value' => $this->tpl_form_vars,
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm($fields_form);
    }

    /**
     * Create the structure of your form.
     */
    protected function initConfigForm()
    {
        $this->fields_form[]['form'] = [
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
                    'type' => 'switch',
                    'label' => $this->l('Enable logs'),
                    'name' => 'MOOVA_DEBUG',
                    'is_bool' => true,
                    'desc' => $this->l('Enable de logs, remember to disable this later!'),
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
                    'desc' => $this->l('Enter the app id'),
                    'name' => 'DEV_MOOVA_APP_ID',
                    'label' => $this->l('Testing App id'),
                    'required' => false
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'DEV_MOOVA_APP_KEY',
                    'label' => $this->l('Testing App key'),
                    'desc' => $this->l('Enter the app key'),
                    'required' => false
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'GOOGLE_API_KEY',
                    'label' => $this->l('Google Api key'),
                    'desc' => $this->l('Enter credentials of Google'),
                    'required' => false
                ),
                /*array(
                    'type' => 'switch',
                    'label' => $this->l('Enable Map in checkout'),
                    'name' => 'IS_MAP_ENABLED_CHECKOUT',
                    'is_bool' => true,
                    'desc' => $this->l('Show the google map in the checkout'),
                    'values' => array(
                        array(
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),*/
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right button',
            )
        ];

        $values = [
            'MOOVA_LIVE_MODE' => Configuration::get('MOOVA_LIVE_MODE', true),
            'MOOVA_APP_ID' => Configuration::get('MOOVA_APP_ID', ''),
            'MOOVA_APP_KEY' => Configuration::get('MOOVA_APP_KEY', ''),
            'DEV_MOOVA_APP_ID' => Configuration::get('DEV_MOOVA_APP_ID', ''),
            'DEV_MOOVA_APP_KEY' => Configuration::get('DEV_MOOVA_APP_KEY', ''),
            'GOOGLE_API_KEY' => Configuration::get('GOOGLE_API_KEY', ''),
            'MOOVA_DEBUG' => Configuration::get('MOOVA_DEBUG', false)
        ];

        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    /**
     * Get address config
     */
    protected function initOriginForm()
    {
        $this->fields_form[]['form'] = [
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
                    'name' => 'MOOVA_ORIGIN_ADDRESS',
                    'label' => $this->l('Street'),
                    'required' => true
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Street'),
                    'name' => 'MOOVA_ORIGIN_GOOGLE_PLACE_ID',
                    'label' => $this->l('Street'),
                    'required' => true
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Special observation. Example: red door'),
                    'name' => 'MOOVA_ORIGIN_COMMENT',
                    'label' => $this->l('Description'),
                    'required' => false
                ),

            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        ];

        $values = [
            'MOOVA_ORIGIN_PHONE' => Configuration::get('MOOVA_ORIGIN_PHONE', ''),
            'MOOVA_ORIGIN_NAME' => Configuration::get('MOOVA_ORIGIN_NAME', ''),
            'MOOVA_ORIGIN_SURNAME' => Configuration::get('MOOVA_ORIGIN_SURNAME', ''),
            'MOOVA_ORIGIN_EMAIL' => Configuration::get('MOOVA_ORIGIN_EMAIL', ''),
            'MOOVA_ORIGIN_COMMENT' => Configuration::get('MOOVA_ORIGIN_COMMENT', ''),
            'MOOVA_ORIGIN_GOOGLE_PLACE_ID' => Configuration::get('MOOVA_ORIGIN_GOOGLE_PLACE_ID', ''),
            'MOOVA_ORIGIN_ADDRESS' => Configuration::get('MOOVA_ORIGIN_ADDRESS', ''),
            'MOOVA_ORIGIN_FLOOR' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
            'MOOVA_ORIGIN_APARTMENT' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
            'MOOVA_ORIGIN_POSTAL_CODE' => Configuration::get('MOOVA_ORIGIN_POSTAL_CODE', '')
        ];

        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function initFreeShippingForm()
    {
        $this->fields_form[]['form'] = [
            'legend' => array(
                'title' => $this->l('Free Shipping'),
                'icon' => 'mi-payment',
            ),

            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Min price'),
                    'prefix' => '$',
                    'name' => 'MOOVA_MIN_FREE_SHIPPING_PRICE',
                    'label' => $this->l('Min price'),
                    'required' => false
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Free shipping'),
                    'name' => 'MOOVA_FREE_SHIPPING',
                    'is_bool' => true,
                    'desc' => $this->l('Enable minimum for free shipping'),
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

            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ];

        $values = [
            'MOOVA_FREE_SHIPPING' => Configuration::get('MOOVA_FREE_SHIPPING', ''),
            'MOOVA_MIN_FREE_SHIPPING_PRICE' => Configuration::get('MOOVA_MIN_FREE_SHIPPING_PRICE', '')
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function initSpecialPricingForm()
    {
        $options = array(
            array(
                'key' => 'default',
                'name' => 'Default'
            ),
            array(
                'key' => 'range',
                'name' => 'Range'
            ),
            array(
                'key' => 'fixed',
                'name' => 'Fixed'
            ),
        );

        $this->fields_form[]['form'] = [
            'legend' => array(
                'title' => $this->l('Special Price'),
                'icon' => 'mi-payment',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'lang' => true,
                    'label' => $this->l('Pricing options'),
                    'name' => 'SPECIAL_PRICING_OPTIONS',
                    'desc' => $this->l('Select between range, fixed price or the default pricing'),
                    'options' => array(
                        'query' => $options,
                        'id' => 'key',
                        'name' => 'name'
                    )
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Minimum price'),
                    'prefix' => '$',
                    'name' => 'MOOVA_MIN_PRICE',
                    'label' => $this->l('Min price'),
                    'required' => false
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Maximum price'),
                    'prefix' => '$',
                    'name' => 'MOOVA_MAX_PRICE',
                    'label' => $this->l('Maximum price'),
                    'required' => false
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Fixed price'),
                    'prefix' => '$',
                    'name' => 'MOOVA_FIXED_PRICE',
                    'label' => $this->l('Fixed price'),
                    'required' => false
                ),


            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ];

        $values = [
            'SPECIAL_PRICING_OPTIONS' => Configuration::get('SPECIAL_PRICING_OPTIONS', 'default'),
            'MOOVA_MIN_PRICE' => Configuration::get('MOOVA_MIN_PRICE', ''),
            'MOOVA_MAX_PRICE' => Configuration::get('MOOVA_MAX_PRICE', ''),
            'MOOVA_FIXED_PRICE' => Configuration::get('MOOVA_FIXED_PRICE', ''),
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function initMappingForm()
    {
        $order = new OrderState(1);
        $status = array_merge([[
            "id_order_state" => "disabled",
            "name" => $this->l('Disabled')
        ]], $order->getOrderStates($this->context->language->id));

        $preText = $this->l('When changing this status in the order, the shipping will be');
        $this->fields_form[]['form'] = [
            'legend' => array(
                'title' => $this->l('Sending status to Moova'),
                'icon' => 'mi-payment',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Start shipping'),
                    'name' => 'MOOVA_STATUS_START_SHIPPING',
                    'desc' => $preText . $this->l(' STARTED in Moova'),
                    'options' => array(
                        'query' => $status,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ];

        $values = [
            'MOOVA_STATUS_CREATE_SHIPPING' => Configuration::get('MOOVA_STATUS_CREATE_SHIPPING', 'disabled'),
            'MOOVA_STATUS_START_SHIPPING' => Configuration::get('MOOVA_STATUS_START_SHIPPING', 'disabled'),
            'MOOVA_STATUS_CANCEL_SHIPPING' => Configuration::get('MOOVA_STATUS_CANCEL_SHIPPING', 'disabled'),
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function initMappingFromMoovaToPrestashop()
    {
        $order = new OrderState(1);
        $status = array_merge(
            [[
                "id_order_state" => "disabled",
                "name" => $this->l('Disabled')
            ]],
            $order->getOrderStates($this->context->language->id)
        );

        $moovaStatuses = [
            'CONFIRMED',
            'ATPICKUPPOINT',
            'DELIVERED',
            'INCIDENCE',
            'CANCELED',
            'RETURNED',
            'TOBERETURNED',
            'WAITINGCLIENT'
        ];

        $inputs = [];
        $values = [];
        foreach ($moovaStatuses as $moovaState) {
            $name = "RECEIVE_MOOVA_STATUS_$moovaState";
            $inputs[] = [
                'type' => 'select',
                'label' => $this->l($moovaState),
                'name' => $name,
                'desc' => $this->l("When moova changes to status $moovaState the order will change to this state"),
                'options' => [
                    'query' => $status,
                    'id' => 'id_order_state',
                    'name' => 'name'
                ]
            ];

            $values = array_merge($values, [
                $name  => Configuration::get($name, 'disabled')
            ]);
        }
        $this->fields_form[]['form'] = [
            'legend' => array(
                'title' => $this->l('Sending status to Moova'),
                'icon' => 'mi-payment',
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function editCheckoutFields()
    {
        $db = \Db::getInstance();
        $request = 'SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'address';
        /** @var array $result */
        $address = $db->executeS($request);
        $checkoutOptions = array_merge([
            [
                "Field" => "disabled",
                "Field" => $this->l('Disabled')
            ]
        ], $address);

        $moovaFields = [
            "address",
            "floor",
            'instructions',
            'country',
            'state',
            'postalCode',
            'description',
            'city',
            'lat',
            'lng'
        ];
        $inputs = [];
        foreach ($moovaFields as $field) {
            $name = "MAP_MOOVA_CHECKOUT_$field";
            $inputs[] = [
                'type' => 'select',
                'label' => $field,
                'name' => $name,
                'desc' => $this->l("Value to send in checkout"),
                'options' => [
                    'query' => $checkoutOptions,
                    'id' => 'Field',
                    'name' => 'Field'
                ]
            ];
        }

        $this->fields_form[]['form'] = [
            'legend' => array(
                'title' => $this->l('Advanced checkout Mapping'),
                'description' => 'DO NOT edit this if you are not sure what you are doing',
                'icon' => 'mi-payment',
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ];
        $values = array_merge(
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_address', 'address1'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_floor', 'address2'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_instructions', 'other'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_country', 'id_country'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_state', 'id_state'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_city', 'city'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_postalCode', 'postcode'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_description', 'description'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_lat', 'disabled'),
            $this->getWithDefault('MAP_MOOVA_CHECKOUT_lng', 'disabled')
        );
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    private function getWithDefault($key, $default = null)
    {
        $value = Configuration::get($key);
        $value =  (!$value && $default) ? $default : $value;
        return [$key => $value];
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        $result = true;
        $this->setWebhookUrl();
        foreach (\Tools::getAllValues() as $fieldName => $fieldValue) {
            Configuration::updateValue($fieldName, pSQL($fieldValue));
        }
        return $result;
    }

    protected function setWebhookUrl()
    {
        $apiAccess = new WebserviceKey(Configuration::get('MOOVA_WEBHOOK_API_ACCESS', ''));
        if (!$apiAccess || empty($apiAccess->key)) {
            $str = rand();
            $key = md5($str);
            $apiAccess = new WebserviceKey();
            $apiAccess->key = $key;
            $apiAccess->save();
        }

        $permissions = [
            'WebhookMoova' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1]
        ];
        $url = _PS_BASE_URL_ . __PS_BASE_URI__ . 'api/WebhookMoova';
        WebserviceKey::setPermissionForAccount($apiAccess->id, $permissions);
        Configuration::updateValue('MOOVA_WEBHOOK_API_ACCESS', $apiAccess->id);

        $payload = [
            "webhook_enabled" => true,
            "webhook_giveup_count" => 0,
            "webhook_method" => "POST",
            "webhook_url" => $url,
            "webhook_headers" => [
                ["Authorization" => 'Basic ' . base64_encode($apiAccess->key . ':')]
            ]
        ];
        $response = $this->moova->setCompanyWebhook($payload);
        Log::info(json_encode($response));
        return $response;
    }

    private function validateformIsComplete()
    {

        foreach ($this->fields_form as $form) {
            $forms = $form['form']['input'];
            foreach ($forms as $item) {
                if ((isset($item['required']) && $item['required'] == 1)) {
                    if (!Configuration::get($item['name'])) {
                        return ["status" => 'error', 'field' => $item['label']];
                    }
                }
            }
            return ["status" => 'success'];
        }
    }
}
