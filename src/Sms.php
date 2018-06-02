<?php

namespace Lewee\Sms;

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
        $enabled = cache('SMS')['enabled'] ?? config('sms.enabled');

        if (!$enabled) {
            $error_message = 'SMS service is disabled';

            return request()->wantsJson() ? error($error_message) : show_error($error_message)->send();
        }

        $gateway = cache('SMS')['default'] ?? config('sms.default');
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
            throw new \Exception('Sms gateway class not found');
        }

        return new $gateway();
    }
}