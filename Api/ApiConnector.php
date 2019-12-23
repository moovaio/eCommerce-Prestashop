<?php

abstract class ApiConnector
{
    protected function exec(string $method, string $url, array $data, array $headers)
    {
        $curl = curl_init();

        switch ($method){
           case "POST":
              curl_setopt($curl, CURLOPT_POST, 1);
              if ($data)
                 curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
              break;
           case "PUT":
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
              if ($data)
                 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
              break;
           default:
              if ($data)
                 $url = sprintf("%s?%s", $url, http_build_query($data));
        }
    
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
      
        $result = curl_exec($curl);
        if(!$result){
            throw new error("Unable to connect");
        }
        curl_close($curl);
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
