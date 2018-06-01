<?php

namespace Lewee\Sms;

interface Sender
{
    public function send($phone, $args);
}