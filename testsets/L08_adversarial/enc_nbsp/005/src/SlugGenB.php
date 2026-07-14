<?php

declare(strict_types=1);

namespace Acme\Util\SlugB;

use RuntimeException;

final class SlugGenB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function slugify(string $input): string
    {
        $text = preg_replace('/[^\pL\pN\s\-_]/u', '', $input);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
