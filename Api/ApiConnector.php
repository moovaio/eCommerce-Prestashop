<?php

namespace Moova\Api;

abstract class ApiConnector
{
    protected function exec(string $method, string $url, array $body, array $headers)
    {
        if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
            $body = json_encode($body);
        }

        $options = [
            "http"=>[
                'method' => $method,
                'headers' => $headers,
                'body' => $body
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            throw new Error('Error executing API Moova');
        }
        return $result;
    }

    public function get(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        if (!empty($body))
            $url .= '?' . http_build_query($body);
        return $this->exec('GET', $url, [], $headers);
    }

    public function post(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('POST', $url, $body, $headers);
    }

    public function put(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('PUT', $url, $body, $headers);
    }

    public function delete(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('DELETE', $url, $body, $headers);
    }

    protected function add_params_to_url($url, $params)
    {
        if (strpos($url, '?') !== false) {
            $url .= '&' . $params;
        } else {
            $url .= '?' . $params;
        }
        return $url;
    }

    public abstract function get_base_url();
}
