<?php

/**
 * 2007-2020·PrestaShop Moova
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
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');
include_once(_PS_MODULE_DIR_ . '/moova/Helper/Log.php');

class MoovaGetOrderShippingCostController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->moova = new MoovaSdk();
        $this->_path = $path;
    }

    public function run($cart, $shipping_fees)
    {
        Log::info('run - Trying to get price');

        $destination = $this->moova->getDestination($cart);
        $products = $cart->getProducts(true);
        Log::info('run - destination:' . json_encode($destination));
        Log::info('run - products:' . json_encode($products));
        $price = $this->moova->getPrice(
            $destination,
            $products
        );
        if ($price === false) {
            return false;
        }
        $totalPrice = $price + $shipping_fees;
        return $this->getRangePrice($totalPrice, $cart);
    }

    private function getRangePrice($price, $cart)
    {
        if (Configuration::get('MOOVA_FREE_SHIPPING', '')) {
            $minPrice  = Configuration::get('MOOVA_MIN_FREE_SHIPPING_PRICE', '');

            if ($cart->getOrderTotal(false, 1) > $minPrice && $minPrice > 0) {
                return 0;
            }
        }

        $specialPricing = Configuration::get('SPECIAL_PRICING_OPTIONS', 'default');
        if ($specialPricing == 'range') {
            if ($price < Configuration::get('MOOVA_MIN_PRICE', 0)) {
                return Configuration::get('MOOVA_MIN_PRICE', 0);
            }
            if ($price > Configuration::get('MOOVA_MAX_PRICE', 0)) {
                return Configuration::get('MOOVA_MAX_PRICE', 0);
            }
        } elseif ($specialPricing == 'fixed') {
            return Configuration::get('MOOVA_FIXED_PRICE', 'default');
        }
        return $price;
    }
}
