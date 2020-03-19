<?php

include_once(_PS_MODULE_DIR_ . '/moova/sdk/MoovaSdk.php');

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
        if ($this->context->customer->logged == true) {
            $destination = $this->getDestination($cart);
            $products = $cart->getProducts(true);

            $price = $this->moova->getPrice(
                $destination,
                $products
            );
            if ($price === false) return false;
            $totalPrice = $price + $shipping_fees;
            return $this->getRangePrice($totalPrice,$cart);
        }
        return false;
    }

    private function getRangePrice($price,$cart)
    {
        if(Configuration::get('MOOVA_FREE_SHIPPING', '')){ 
            $minPrice  = Configuration::get('MOOVA_MIN_PRICE', '');
            $minWeight = Configuration::get('MOOVA_MIN_WEIGHT', '');

            if($cart->getOrderTotal(false, 1) > $minPrice && $minPrice>0 ){
                return 0;
            }

            if($cart->getTotalWeight() > $minWeight && $minWeight>0 ){
                return 0;
            }
        }
        return $price;
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
}
