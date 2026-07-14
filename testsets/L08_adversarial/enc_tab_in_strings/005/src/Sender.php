<?php

declare(strict_types=1);

namespace Acme\Notify\Send;

final class MessageSender
{
    public function email(string $to, string $subject): bool
    {
        return true;
    }

    public function sms(string $to, string $message): bool
    {
        return true;
    }
}
