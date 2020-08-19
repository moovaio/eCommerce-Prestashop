<?php

/**
 * 2007-2020 PayPal
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/afl-3.0.php
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@prestashop.com so we can send you a copy immediately.
 *
 *  DISCLAIMER
 *
 *  Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 *  versions in the future. If you wish to customize PrestaShop for your
 *  needs please refer to http://www.prestashop.com for more information.
 *
 *  @author 2007-2020 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @copyright PayPal
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use Symfony\Component\HttpFoundation\JsonResponse;

class AdminMoovaSetupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
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
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        $message = \Tools::isSubmit($this->controller_name . '_config') ? $this->validateformIsComplete() : ['status' => 'waiting'];
        $this->context->smarty->assign('message', $message);
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addJS("/modules/{$this->module->name}/views/js/settings.js");
        $this->context->smarty->assign(
            array(
                'token' => Tools::getAdminTokenLite($this->controller_name)
            )
        );


        $this->context->smarty->assign("module_dir", "/modules/{$this->module->name}");

        $output = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/configure.tpl');
        $renderedForm = $this->renderForm();
        $this->context->smarty->assign('content',  $output . $renderedForm);
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
            'MOOVA_FIXED_PRICE' => Configuration::get('MOOVA_FIXED_PRICE', '')
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        $result = true;

        foreach (\Tools::getAllValues() as $fieldName => $fieldValue) {
            Configuration::updateValue($fieldName, pSQL($fieldValue));
        }

        return $result;
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
