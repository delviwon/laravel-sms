<?php

namespace Lewee\Sms;

class Sms implements Sender
{
    /**
     * Send Message
     * @param $phone
     * @param $args
     * @throws \Exception
     */
    public function send($phone, $args)
    {
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