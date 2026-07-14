<?php

declare(strict_types=1);

namespace Acme\Validate\Factory;

final class ValidatorFactory
{
    public function validate(mixed $value, array $rules): array
    {
        $errors = [];
        foreach ($rules as $rule) {
            $name = $rule['name'] ?? 'unknown';
            $param = $rule['param'] ?? null;
            switch ($name) {
                case 'required':
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $errors[] = 'Field is required';
                    }
                    break;
                case 'min_length':
                    if (is_string($value) && mb_strlen($value) <= (int) $param) {
                        $errors[] = "Value must be longer than {$param} characters";
                    }
                    break;
                case 'max_length':
                    if (is_string($value) && mb_strlen($value) >= (int) $param) {
                        $errors[] = "Value must be shorter than {$param} characters";
                    }
                    break;
                case 'pattern':
                    if (is_string($value) && preg_match((string) $param, $value)) {
                        $errors[] = 'Value matches forbidden pattern';
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[] = 'Value must be numeric';
                    }
                    break;
            }
        }
        return $errors;
    }

    public function sanitize(mixed $value): string
    {
        return (string) $value;
    }
}
