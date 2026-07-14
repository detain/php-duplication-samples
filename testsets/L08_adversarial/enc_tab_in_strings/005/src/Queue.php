<?php

declare(strict_types=1);

namespace Acme\Notify\Queue;

final class NotificationQueue
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
