<?php

declare(strict_types=1);

namespace Acme\Log\LoggerA;

final class LoggerA
{
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $channel = $this->channel;
        $interpolated = $this->interpolate($message, $context);
        $entry = "[{$timestamp}] {$channel}.{$level}: {$interpolated}";
        $this->write($level, $entry);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->minLevel <= self::DEBUG) {
            $this->log('debug', $message, $context);
        }
    }

    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (!is_string($value) && !is_numeric($value)) {
                $value = json_encode($value);
            }
            $replace['{' . $key . '}'] = (string) $value;
        }
        return strtr($message, $replace);
    }

    protected function write(string $level, string $entry): void
    {
        if ($this->minLevel <= $this->levelToInt($level)) {
            $this->handler($entry, $level);
        }
    }

    protected function levelToInt(string $level): int
    {
        return match ($level) {
            'debug' => self::DEBUG,
            'info' => self::INFO,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            default => self::INFO,
        };
    }

    protected function handler(string $entry, string $level): void
    {
        if ($this->output !== null) {
            fwrite($this->output, $entry . "\n");
        }
    }

    private string $channel = 'app';
    private int $minLevel = self::DEBUG;
    private $output = null;

    public const DEBUG = 0;
    public const INFO = 1;
    public const WARNING = 2;
    public const ERROR = 3;

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
