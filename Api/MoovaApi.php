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

include_once(_PS_MODULE_DIR_ . '/moova/Api/ApiConnector.php');
include_once(_PS_MODULE_DIR_ . '/moova/Api/ApiInterface.php');
class MoovaApi extends ApiConnector implements ApiInterface
{
    const DEV_BASE_URL = 'https://api-dev.moova.io/b2b';
    const PROD_BASE_URL = 'https://api-prod.moova.io/b2b';

    public function __construct(string $clientid, string $client_secret, bool $isProd)
    {
        $this->api_config = [
            'appId' => $clientid,
        ];
        $this->auth_header = $client_secret;
        $this->isProd = $isProd;
    }

    public function get(string $endpoint, array $body = [], array $headers = [])
    {
        $body = array_merge($this->api_config, $body);
        $url = $this->getBaseUrl() . $endpoint;

        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        if (!empty($body)) {
            $url .= '?' . http_build_query($body);
        }
        return $this->exec('GET', $url, [], $headers);
    }

    public function post(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->getBaseUrl() . $endpoint;
        $url = $this->addParamsToUrl($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('POST', $url, $body, $headers);
    }

    public function put(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->getBaseUrl() . $endpoint;
        $url = $this->addParamsToUrl($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('PUT', $url, $body, $headers);
    }

    public function delete(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->getBaseUrl() . $endpoint;
        $url = $this->addParamsToUrl($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('DELETE', $url, $body, $headers);
    }

    public function getBaseUrl()
    {
        if ($this->isProd == false) {
            return self::DEV_BASE_URL;
        }
        return self::PROD_BASE_URL;
    }
}
