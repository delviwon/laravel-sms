<?php

namespace Lewee\Sms\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait Gateway
{
    /**
     * Get gateway config
     * @param $key
     * @return mixed
     */
    protected function config($key)
    {
        $gateway = self::GATEWAY;
        $config = cache('SMS') ?? config('sms');
        return $config['gateways'][$gateway][$key] ?? '';
    }

    /**
     * Get response content
     * @param $response
     * @return mixed
     */
    protected function getResponse($response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }
    }

    /**
     * Send curl request
     * @param $method
     * @param $url
     * @param array $headers
     * @param array $params
     * @return mixed|void
     */
    protected function request($method, $url, $headers = [], $params = [])
    {
        $config = [
            'timeout' => 15,
        ];

        if ($headers) {
            $config['headers'] = $headers;
        }

        switch ($method)
        {
            case 'GET':
                if ($params) {
                    $config['query'] = $params;
                }

                break;
            case 'POST':
                if ($params) {
                    $config['form_params'] = $params;
                }

                break;
            default:
                return;
        }

        try {
            $client = new Client();
            $response = $client->request($method, $url, $config);

            return $this->getResponse($response);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            return $this->getResponse($response);
        }
    }
}