<?php

declare(strict_types=1);

namespace App\Validation;

class FormValidator
{
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];
        $this->rules = $rules;

        foreach ($rules as $field => $ruleSet) {
            $rulesList = explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $params = [];

        if (str_contains($rule, ':')) {
            [$ruleName, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
            $rule = $ruleName;
        }

        $error = match ($rule) {
            'required' => $this->validateRequired($value),
            'email' => $this->validateEmail($value),
            'min' => $this->validateMin($value, (int) ($params[0] ?? 0)),
            'max' => $this->validateMax($value, (int) ($params[0] ?? 0)),
            'between' => $this->validateBetween($value, (int) ($params[0] ?? 0), (int) ($params[1] ?? 0)),
            'numeric' => $this->validateNumeric($value),
            'integer' => $this->validateInteger($value),
            'string' => $this->validateString($value),
            'array' => $this->validateArray($value),
            'in' => $this->validateIn($value, $params),
            'not_in' => $this->validateNotIn($value, $params),
            'regex' => $this->validateRegex($value, $params[0] ?? ''),
            'url' => $this->validateUrl($value),
            'date' => $this->validateDate($value),
            'confirmed' => $this->validateConfirmed($field),
            default => null,
        };

        if ($error !== null) {
            $this->errors[$field][] = $error;
        }
    }

    private function validateRequired(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return 'This field is required';
        }

        if (is_array($value) && count($value) === 0) {
            return 'This field is required';
        }

        return null;
    }

    private function validateEmail(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address';
        }

        return null;
    }

    private function validateMin(mixed $value, int $min): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && $value < $min) {
            return "Value must be at least {$min}";
        }

        if (is_string($value) && mb_strlen($value) < $min) {
            return "Must be at least {$min} characters";
        }

        if (is_array($value) && count($value) < $min) {
            return "Must have at least {$min} items";
        }

        return null;
    }

    private function validateMax(mixed $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && $value > $max) {
            return "Value must not exceed {$max}";
        }

        if (is_string($value) && mb_strlen($value) > $max) {
            return "Must not exceed {$max} characters";
        }

        if (is_array($value) && count($value) > $max) {
            return "Must not have more than {$max} items";
        }

        return null;
    }

    private function validateBetween(mixed $value, int $min, int $max): ?string
    {
        if ($this->validateMin($value, $min) !== null) {
            return "Value must be between {$min} and {$max}";
        }

        if ($this->validateMax($value, $max) !== null) {
            return "Value must be between {$min} and {$max}";
        }

        return null;
    }

    private function validateNumeric(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return 'Must be a number';
        }

        return null;
    }

    private function validateInteger(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return 'Must be an integer';
        }

        return null;
    }

    private function validateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return 'Must be a string';
        }

        return null;
    }

    private function validateArray(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return 'Must be an array';
        }

        return null;
    }

    private function validateIn(mixed $value, array $values): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!in_array($value, $values, true)) {
            return 'Selected value is invalid';
        }

        return null;
    }

    private function validateNotIn(mixed $value, array $values): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($value, $values, true)) {
            return 'Selected value is not allowed';
        }

        return null;
    }

    private function validateRegex(mixed $value, string $pattern): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match($pattern, (string) $value) !== 1) {
            return 'Format is invalid';
        }

        return null;
    }

    private function validateUrl(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return 'Please enter a valid URL';
        }

        return null;
    }

    private function validateDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$parsed || $parsed->format('Y-m-d') !== $value) {
            return 'Please enter a valid date';
        }

        return null;
    }

    private function validateConfirmed(string $field): ?string
    {
        $value = $this->data[$field] ?? null;
        $confirmation = $this->data[$field . '_confirmation'] ?? null;

        if ($value !== $confirmation) {
            return 'Confirmation does not match';
        }

        return null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}
