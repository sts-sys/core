<?php

namespace sts\logger\interfaces;

interface LogChannelInterface
{
    public function log($level, $message, array $context = []);
}
