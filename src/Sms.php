<?php

namespace Lewee\Sms;

class Sms implements Sender
{
    /**
     * Send Message
     * @param $phone
     * @param $args
     */
    public function send($phone, $args)
    {
        $this->getGateway(config('sms.default'))->send($phone, $args);
    }

    /**
     * Get gateway instance.
     * @param $name
     * @return mixed
     */
    public function getGateway($name)
    {
        $name = ucfirst($name);
        $gateway = __NAMESPACE__ . "\\Gateways\\{$name}";

        return new $gateway();
    }
}