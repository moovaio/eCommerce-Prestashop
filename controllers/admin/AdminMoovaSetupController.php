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
        parent::__construct();
    }

    protected $headerToolBar = true;

    public function init()
    {
        parent::init();
        /**
         * If values have been submitted in the form, process.
         * 
         */

        $message = ['status' => 'waiting'];
        $showMessage = ((bool) Tools::isSubmit('submitMoovaModule')) == true;
        $form = [
            $this->getConfigForm(),
            $this->getOriginForm(),
            $this->getFreeShippingForm()
        ];

        if ($showMessage) {
            $this->postProcess();
            $message = $this->validateformIsComplete($form);
        }
        $this->getConfigFormValues();
        $this->context->smarty->assign('message', $message);
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addJS($this->_path . 'views/js/settings.js');
        $this->context->smarty->assign(array(
            'token' => Tools::getAdminTokenLite($this->ORDER_TAB)
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        return $output . $this->renderForm($form);
    }

    public function initContent()
    {
        $message = ['status' => 'waiting'];
        throw new Exception('HOLA');
        $showMessage = ((bool) Tools::isSubmit('submitMoovaModule')) == true;
        $form = [
            $this->getConfigForm(),
            $this->getOriginForm(),
            $this->getFreeShippingForm()
        ];

        if ($showMessage) {
            $this->postProcess();
            $message = $this->validateformIsComplete($form);
        }

        $this->context->smarty->assign('message', $message);
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addJS($this->_path . 'views/js/settings.js');
        $this->context->smarty->assign(array(
            'token' => Tools::getAdminTokenLite($this->ORDER_TAB)
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        return $output . $this->renderForm($form);
    }

    public function renderForm()
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

        /* $values = array(
            'paypal_api_intent' => Configuration::get('PAYPAL_API_INTENT'),
        );
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);*/
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
                ),
            ),
        );
    }

    protected function getFreeShippingForm()
    {
        return array(
            'form' => array(
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
                        'name' => 'MOOVA_MIN_PRICE',
                        'label' => $this->l('Min price'),
                        'required' => false
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Weight'),
                        'name' => 'MOOVA_MIN_WEIGHT',
                        'suffix' => 'KG',
                        'label' => $this->l('Weight'),
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
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $this->tpl_form_vars = [
            'MOOVA_LIVE_MODE' => Configuration::get('MOOVA_LIVE_MODE', true),
            'MOOVA_KEY_AUTHENTICATION' => Configuration::get('MOOVA_KEY_AUTHENTICATION', ''),
            'MOOVA_APP_ID' => Configuration::get('MOOVA_APP_ID', ''),
            'MOOVA_APP_KEY' => Configuration::get('MOOVA_APP_KEY', ''),
            'MOOVA_ORIGIN_PHONE' => Configuration::get('MOOVA_ORIGIN_PHONE', ''),
            'MOOVA_ORIGIN_NAME' => Configuration::get('MOOVA_ORIGIN_NAME', ''),
            'MOOVA_ORIGIN_SURNAME' => Configuration::get('MOOVA_ORIGIN_SURNAME', ''),
            'MOOVA_ORIGIN_EMAIL' => Configuration::get('MOOVA_ORIGIN_EMAIL', ''),
            'MOOVA_ORIGIN_COMMENT' => Configuration::get('MOOVA_ORIGIN_COMMENT', ''),
            'MOOVA_ORIGIN_ADDRESS' => Configuration::get('MOOVA_ORIGIN_ADDRESS', ''),
            'MOOVA_ORIGIN_FLOOR' => Configuration::get('MOOVA_ORIGIN_FLOOR', ''),
            'MOOVA_ORIGIN_APARTMENT' => Configuration::get('MOOVA_ORIGIN_APARTMENT', ''),
            'MOOVA_ORIGIN_POSTAL_CODE' => Configuration::get('MOOVA_ORIGIN_POSTAL_CODE', ''),
            'MOOVA_FREE_SHIPPING' => Configuration::get('MOOVA_FREE_SHIPPING', ''),
            'MOOVA_MIN_WEIGHT' => Configuration::get('MOOVA_MIN_WEIGHT', ''),
            'MOOVA_MIN_PRICE' => Configuration::get('MOOVA_MIN_PRICE', '')
        ];
    }
}
