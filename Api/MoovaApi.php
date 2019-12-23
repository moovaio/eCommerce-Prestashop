<?php
 
include_once(_PS_MODULE_DIR_ . '/Moova/Api/ApiConnector.php');
include_once(_PS_MODULE_DIR_ . '/Moova/Api/ApiInterface.php');
class MoovaApi extends ApiConnector implements ApiInterface
{
    const DEV_BASE_URL = 'https://api-dev.moova.io/b2b';
    const PROD_BASE_URL = 'https://api.moova.io/b2b';

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
        $url = $this->get_base_url() . $endpoint;

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
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('POST', $url, $body, $headers);
    }

    public function put(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('PUT', $url, $body, $headers);
    }

    public function delete(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query($this->api_config));
        $headers =[
            "Authorization: $this->auth_header",
            "Content-Type: application/json",
        ];
        return $this->exec('DELETE', $url, $body, $headers);
    }

    public function get_base_url()
    {
        if ($this->isProd == false) {
            return self::DEV_BASE_URL;
        }
        return self::PROD_BASE_URL;
    }
}
