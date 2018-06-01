<?php

namespace Lewee\Sms\Gateways;

class Aliyun implements \Lewee\Sms\Sender{
    const REQUEST_URL = 'dysmsapi.aliyuncs.com';
    const SIGNATURE_METHOD = 'HMAC-SHA1';
    const SIGNATURE_VERSION = '1.0';
    const FORMAT = 'JSON';
    const REGION_ID = 'cn-hangzhou';
    const ACTION = 'SendSms';
    const VERSION = '2017-05-25';
    const X_SDK_CLIENT = 'php/2.0.0';
    private $security;
    private $access_key_id;
    private $access_key_secret;
    private $sign;

    /**
     * Aliyun constructor.
     */
    public function __construct()
    {
        $this->security = config('sms.gateways.aliyun.security');
        $this->access_key_id = config('sms.gateways.aliyun.access_key_id');
        $this->access_key_secret = config('sms.gateways.aliyun.access_key_secret');
        $this->sign = config('sms.gateways.aliyun.sign');
    }

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
            'SignName' => $this->sign,
            'TemplateCode' => $args['template_id'],
        ];

        if (isset($args['params']) && is_array($args['params'])) {
            $send_args['TemplateParam'] = json_encode($args['params'], JSON_UNESCAPED_UNICODE);
        }

        $sorted_query_string = $this->getSortedQueryString($send_args);
        $signature = $this->makeSignature($sorted_query_string);
        $url = ($this->security ? 'https' : 'http') . '://' . self::REQUEST_URL . "/?Signature={$signature}{$sorted_query_string}";

        try {
            $content = $this->fetchContent($url);
            $result = json_decode($content);

            if ($result->Code == 'OK') {
                return true;
            } else {
                return request()->wantsJson() ? $result : show_error($result->Message)->send();
            }
        } catch ( \Exception $e) {
            return false;
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
            'AccessKeyId' => $this->access_key_id,
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
        $sign = base64_encode(hash_hmac('sha1', $string_to_sign, $this->access_key_secret . '&',true));
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

    /**
     * Send curl request
     * @param $url
     * @return mixed
     */
    private function fetchContent($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-sdk-client' => self::X_SDK_CLIENT
        ]);

        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if($rtn === false) {
            trigger_error('[CURL_' . curl_errno($ch) . ']:' . curl_error($ch), E_USER_ERROR);
        }

        curl_close($ch);

        return $rtn;
    }
}