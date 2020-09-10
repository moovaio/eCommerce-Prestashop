<?php

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
        if ($this->context->customer->logged == true) {
            $destination = $this->moova->getDestination($cart);
            $products = $cart->getProducts(true);
            Log::info('run - destination:' . json_encode($destination));
            Log::info('run - products:' . json_encode($products));
            $price = $this->moova->getPrice(
                $destination,
                $products
            );
            if ($price === false) return false;
            $totalPrice = $price + $shipping_fees;
            return $this->getRangePrice($totalPrice, $cart);
        }
        return false;
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
        } else if ($specialPricing == 'fixed') {
            return Configuration::get('MOOVA_FIXED_PRICE', 'default');
        }
        return $price;
    }
}
