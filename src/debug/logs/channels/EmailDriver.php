<?php

namespace sts\logger\logs\channels;

use sts\logger\interfaces\LogChannelInterface;

class EmailDriver implements LogChannelInterface
{
    private $recipients;
    private $subject;

    public function __construct(array $recipients, $subject = 'Log Notification')
    {
        $this->recipients = $recipients;
        $this->subject = $subject;
    }

    public function log($level, $message, array $context = [])
    {
        $body = "Level: $level\nMessage: $message\nContext: " . print_r($context, true);
        foreach ($this->recipients as $recipient) {
            mail($recipient, $this->subject, $body);
        }
    }
}
