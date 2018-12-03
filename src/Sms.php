<?php

namespace Lewee\Sms;

use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;

class Sms implements Sender
{
    /**
     * Send Message
     * @param $phone
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function send($phone, $args)
    {
        $setting = config_item('sms');
        $enabled = $setting['enabled'] ?? config('sms.enabled');

        if (!$enabled) {
            throw new InvalidRequestException('SMS service is disabled');
        }

        $gateway = $setting['default'] ?? config('sms.default');
        $this->getGateway($gateway)->send($phone, $args);
    }

    /**
     * Get gateway instance
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getGateway($name)
    {
        $name = ucfirst($name);
        $gateway = __NAMESPACE__ . "\\Gateways\\{$name}";

        if ( !class_exists($gateway)) {
            throw new InternalException('Sms gateway class not found');
        }

        return new $gateway();
    }
}