<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

abstract class BaseException extends \Exception
{
    protected array $context = [];
    protected string $errorCode = 'UNKNOWN_ERROR';
    protected ?string $userMessage = null;
    protected bool $logAsWarning = true;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUserMessage(): ?string
    {
        return $this->this->userMessage;
    }

    public function shouldLogAsWarning(): bool
    {
        return $this->logAsWarning;
    }

    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'user_message' => $this->userMessage,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}

class ValidationException extends BaseException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    protected ?string $userMessage = 'The provided data is invalid';
    protected bool $logAsWarning = false;
    protected array $validationErrors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $validationErrors = [],
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'validation_errors' => $this->validationErrors,
        ]);
    }
}

class NotFoundException extends BaseException
{
    protected string $errorCode = 'NOT_FOUND';
    protected ?string $userMessage = 'The requested resource was not found';
    protected bool $logAsWarning = false;

    public function __construct(
        string $message = 'Resource not found',
        string $resourceType = 'resource',
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous, [
            'resource_type' => $resourceType,
        ]);
    }
}

class AuthenticationException extends BaseException
{
    protected string $errorCode = 'AUTHENTICATION_REQUIRED';
    protected ?string $userMessage = 'Please log in to continue';
    protected array $allowedAuthMethods = [];

    public function __construct(
        string $message = 'Authentication required',
        int $code = 401,
        ?Throwable $previous = null,
        array $allowedAuthMethods = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->allowedAuthMethods = $allowedAuthMethods;
    }

    public function getAllowedAuthMethods(): array
    {
        return $this->allowedAuthMethods;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'allowed_auth_methods' => $this->allowedAuthMethods,
        ]);
    }
}

class RateLimitException extends BaseException
{
    protected string $errorCode = 'RATE_LIMIT_EXCEEDED';
    protected ?string $userMessage = 'Too many requests. Please try again later';
    protected int $retryAfterSeconds = 60;
    protected int $limit = 0;
    protected int $remaining = 0;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfterSeconds = 60,
        int $limit = 0,
        int $remaining = 0,
        int $code = 429,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryAfterSeconds = $retryAfterSeconds;
        $this->limit = $limit;
        $this->remaining = $remaining;
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getHeaders(): array
    {
        return [
            'Retry-After' => $this->retryAfterSeconds,
            'X-RateLimit-Limit' => $this->limit,
            'X-RateLimit-Remaining' => $this->remaining,
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfterSeconds,
            'limit' => $this->limit,
            'remaining' => $this->remaining,
        ]);
    }
}
