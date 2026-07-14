<?php

declare(strict_types=1);

namespace Acme\Validation\Api;

final class ApiValidator
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function validate(mixed $value, array $rules): array
    {
        $errors = [];
        foreach ($rules as $rule) {
            $name = $rule['name'] ?? 'unknown';
            $param = $rule['param'] ?? null;
            switch ($name) {
                case 'required':
                    if ($value === null || $value === '') {
                        $errors[] = 'Value is required';
                    }
                    break;
                case 'min_length':
                    if (is_string($value) && mb_strlen($value) < (int) $param) {
                        $errors[] = "Value must be at least {$param} characters";
                    }
                    break;
                case 'max_length':
                    if (is_string($value) && mb_strlen($value) > (int) $param) {
                        $errors[] = "Value must be at most {$param} characters";
                    }
                    break;
                case 'pattern':
                    if (is_string($value) && !preg_match((string) $param, $value)) {
                        $errors[] = 'Value does not match required pattern';
                    }
                    break;
                case 'min':
                    if (is_numeric($value) && (float) $value < (float) $param) {
                        $errors[] = "Value must be at least {$param}";
                    }
                    break;
                case 'max':
                    if (is_numeric($value) && (float) $value > (float) $param) {
                        $errors[] = "Value must be at most {$param}";
                    }
                    break;
                case 'in':
                    $allowed = is_array($param) ? $param : [];
                    if (!in_array($value, $allowed, true)) {
                        $errors[] = 'Value is not in the allowed list';
                    }
                    break;
            }
        }
        return $errors;
    }
}
