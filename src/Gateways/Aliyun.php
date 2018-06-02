<?php

namespace Lewee\Sms\Gateways;

use Lewee\Sms\Traits\Gateway;

class Aliyun implements \Lewee\Sms\Sender{
    use Gateway;

    const GATEWAY = 'aliyun';
    const REQUEST_URL = 'dysmsapi.aliyuncs.com';
    const SIGNATURE_METHOD = 'HMAC-SHA1';
    const SIGNATURE_VERSION = '1.0';
    const FORMAT = 'JSON';
    const REGION_ID = 'cn-hangzhou';
    const ACTION = 'SendSms';
    const VERSION = '2017-05-25';
    const X_SDK_CLIENT = 'php/2.0.0';

    /**
     * Send message
     * @param $phone
     * @param $args
     * @return bool
     */
    public function send($phone, $args)
    {
        $send_args = [
            'PhoneNumbers' => $phone,
            'SignName' => $this->config('sign'),
            'TemplateCode' => $args['template_id'],
        ];

        if (isset($args['params']) && is_array($args['params'])) {
            $send_args['TemplateParam'] = json_encode($args['params'], JSON_UNESCAPED_UNICODE);
        }

        $sorted_query_string = $this->getSortedQueryString($send_args);
        $signature = $this->makeSignature($sorted_query_string);
        $protocol = $this->config('security') ? 'https' : 'http';
        $url = "{$protocol}://" . self::REQUEST_URL . "/?Signature={$signature}{$sorted_query_string}";

        $headers = [
            'x-sdk-client' => self::X_SDK_CLIENT,
        ];

        $result = $this->request('GET', $url, $headers);

        if ($result['Code'] == 'OK') {
            return true;
        } else {
            return request()->wantsJson() ? $result : show_error($result['Message'])->send();
        }
    }

    /**
     * Get sorted query string
     * @param array $ext_args
     * @return string
     */
    public function getSortedQueryString($ext_args = [])
    {
        $args = array_merge([
            'SignatureMethod' => self::SIGNATURE_METHOD,
            'SignatureNonce' => uniqid(mt_rand(0,0xffff), true),
            'SignatureVersion' => self::SIGNATURE_VERSION,
            'AccessKeyId' => $this->config('access_key_id'),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Format' => self::FORMAT,
            'RegionId' => self::REGION_ID,
            'Action' => self::ACTION,
            'Version' => self::VERSION,
        ], $ext_args);

        ksort($args);

        $sorted_query_string = '';

        foreach ($args as $key => $value) {
            $sorted_query_string .= '&' . $this->encode($key) . '=' . $this->encode($value);
        }

        return $sorted_query_string;
    }

    /**
     * Make signature
     * @param $sorted_query_string
     * @return mixed|string
     */
    protected function makeSignature($sorted_query_string)
    {
        $string_to_sign = 'GET&%2F&' . $this->encode(substr($sorted_query_string, 1));
        $sign = base64_encode(hash_hmac('sha1', $string_to_sign, $this->config('access_key_secret') . '&',true));
        return $this->encode($sign);
    }

    /**
     * Encode the signature
     * @param $str
     * @return mixed|string
     */
    protected function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }
}