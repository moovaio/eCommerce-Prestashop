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

abstract class ApiConnector
{
    protected function exec($method, $url, array $data, array $headers)
    {
        $curl = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }

                break;
            case "PATCH":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, true));
                }

                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }

                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if (!$result) {
            Log::info('exec - Unable to connect in DEV');
            return null;
        }
        curl_close($curl);
        return json_decode($result);
    }

    protected function addParamsToUrl($url, $params)
    {
        if (strpos($url, '?') !== false) {
            $url .= '&' . $params;
        } else {
            $url .= '?' . $params;
        }
        return $url;
    }

    abstract public function getBaseUrl();
}
