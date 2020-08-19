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
        $this->initConfigForm();
        $this->initOriginForm();
        $this->initFreeShippingForm();
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('message', ["status" => "ok"]);
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addJS(_PS_MODULE_DIR_ . $this->module->name . 'views/js/settings.js');
        $this->context->smarty->assign(
            array(
                'token' => Tools::getAdminTokenLite($this->controller_name)
            )
        );

        $this->context->smarty->assign("module_dir", _PS_MODULE_DIR_ . $this->module->name);

        $output = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/configure.tpl');

        $form = $output . $this->renderForm();
        $this->context->smarty->assign('content', $form);
        $this->context->smarty->assign($form);
    }
    /*

        $message = ['status' => 'waiting'];
        $showMessage = ((bool) Tools::isSubmit('submitMoovaModule')) == true;
        $form = $this->renderForm($this->getOriginForm());
        $this->context->smarty->assign($form);
        */
    /*if ($showMessage) {
            $this->postProcess();
            $message = $this->validateformIsComplete($form);
        }

        
    }
*/
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
                    'name' => 'MOOVA_MIN_PRICE',
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
            'MOOVA_MIN_PRICE' => Configuration::get('MOOVA_MIN_PRICE', '')
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    protected function initSpecialPricingForm()
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
                    'name' => 'MOOVA_MIN_PRICE',
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
            'MOOVA_MIN_PRICE' => Configuration::get('MOOVA_MIN_PRICE', '')
        ];
        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }
}
