<?php

namespace Lewee\Sms\Gateways;

use App\Exceptions\InternalException;
use Lewee\Sms\Sender;
use Lewee\Sms\Traits\Gateway;

class Chuanglan implements Sender
{
    use Gateway;

    const GATEWAY = 'chuanglan';
    const REQUEST_URL = 'smssh1.253.com/msg/variable/json';

    public function send($phone, $args)
    {
        $protocol = $this->config('security') ? 'https' : 'http';
        $url = "{$protocol}://" . self::REQUEST_URL;

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $sign = $this->config('sign');

        $params = [
            'account' => $this->config('account'),
            'password' => $this->config('password'),
            'msg' => "【{$sign}】{$this->config('msg')}",
            'params' => "{$phone},{$args['params']['code']}",
        ];

        $result = $this->request('POST', $url, $headers, $params);

        if ($result['code'] != '0') {
            throw new InternalException($result['errorMsg']);
        }

        return true;
    }
}