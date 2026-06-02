<?php

declare(strict_types=1);

namespace App\Logging;

use Throwable;
use Psr\Log\LogLevel;
use DateTimeImmutable;

class LogWriter
{
    protected string $channel;
    protected array $processors = [];
    protected array $handlers = [];

    public function __construct(string $channel = 'app')
    {
        $this->channel = $channel;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $record = $this->prepareRecord($level, $message, $context);

        foreach ($this->handlers as $handler) {
            $handler($record);
        }
    }

    protected function prepareRecord(string $level, string $message, array $context): array
    {
        $record = [
            'channel' => $this->channel,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'datetime' => new DateTimeImmutable(),
            'formatted' => null,
        ];

        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        return $record;
    }

    public function addProcessor(callable $processor): self
    {
        $this->processors[] = $processor;
        return $this;
    }

    public function addHandler(callable $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function exception(Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->error($exception->getMessage(), $context);
    }

    public function withContext(array $context): self
    {
        $newLogger = clone $this;
        $newLogger->addProcessor(function ($record) use ($context) {
            $record['context'] = array_merge($record['context'], $context);
            return $record;
        });
        return $newLogger;
    }
}

class StandardLogFormatter
{
    public function __invoke(array $record): string
    {
        $timestamp = $record['datetime']->format('Y-m-d H:i:s.u');
        $level = strtoupper($record['level']);
        $channel = $record['channel'];
        $message = $record['message'];

        $formatted = "[{$timestamp}] {$channel}.{$level}: {$message}";

        if (!empty($record['context'])) {
            $formatted .= ' ' . json_encode($record['context']);
        }

        return $formatted . PHP_EOL;
    }
}

class JsonLogFormatter
{
    public function __invoke(array $record): string
    {
        return json_encode([
            'timestamp' => $record['datetime']->format('c'),
            'channel' => $record['channel'],
            'level' => $record['level'],
            'message' => $record['message'],
            'context' => $record['context'],
        ]) . PHP_EOL;
    }
}
