<?php

declare(strict_types=1);

namespace App\Validation;

class SecurityValidator
{
    public static function validatePassword(string $password, array $options = []): array
    {
        $errors = [];

        $minLength = $options['min_length'] ?? 8;
        $maxLength = $options['max_length'] ?? 128;
        $requireUppercase = $options['require_uppercase'] ?? true;
        $requireLowercase = $options['require_lowercase'] ?? true;
        $requireNumber = $options['require_number'] ?? true;
        $requireSpecial = $options['require_special'] ?? true;

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }

        if (strlen($password) > $maxLength) {
            $errors[] = "Password must not exceed {$maxLength} characters";
        }

        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if ($requireNumber && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($requireSpecial && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        if (isset($options['disallow_common']) && $options['disallow_common']) {
            $commonPasswords = ['password', '12345678', 'qwerty', 'abc123', 'letmein'];
            if (in_array(strtolower($password), $commonPasswords, true)) {
                $errors[] = 'This password is too common';
            }
        }

        if (isset($options['check_breached']) && $options['check_breached']) {
            if (self::isPasswordBreached($password)) {
                $errors[] = 'This password has appeared in a data breach';
            }
        }

        return $errors;
    }

    public static function isPasswordBreached(string $password): bool
    {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        $response = @file_get_contents("https://api.pwnedpasswords.com/range/{$prefix}");

        if ($response === false) {
            return false;
        }

        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            [$hashSuffix, $count] = explode(':', trim($line));
            if (strtoupper($hashSuffix) === $suffix) {
                return (int) $count > 0;
            }
        }

        return false;
    }

    public static function validateUsername(string $username): array
    {
        $errors = [];

        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        if (strlen($username) > 30) {
            $errors[] = 'Username must not exceed 30 characters';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, underscores, and hyphens';
        }

        if (preg_match('/^[0-9]+$/', $username)) {
            $errors[] = 'Username cannot be only numbers';
        }

        $reserved = ['admin', 'root', 'system', 'moderator', 'support'];
        if (in_array(strtolower($username), $reserved, true)) {
            $errors[] = 'This username is reserved';
        }

        return $errors;
    }

    public static function validateApiKey(string $key, array $options = []): bool
    {
        $expectedLength = $options['length'] ?? 32;
        $prefix = $options['prefix'] ?? null;

        if (strlen($key) !== $expectedLength) {
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            return false;
        }

        if ($prefix !== null && !str_starts_with($key, $prefix)) {
            return false;
        }

        return true;
    }

    public static function sanitizeFilename(string $filename, array $options = []): string
    {
        $maxLength = $options['max_length'] ?? 255;
        $replacement = $options['replacement'] ?? '_';

        $filename = preg_replace('/[^\w\.-]/', $replacement, $filename);

        $filename = preg_replace('/' . preg_quote($replacement, '/') . '+/', $replacement, $filename);

        $filename = trim($filename, $replacement);

        if (strlen($filename) > $maxLength) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);

            $maxNameLength = $maxLength - strlen($ext) - 1;
            $filename = substr($name, 0, $maxNameLength) . '.' . $ext;
        }

        if (empty($filename) || $filename === '.' || $filename === '..') {
            $filename = 'file';
        }

        return $filename;
    }

    public static function isSqlInjectionSafe(string $input): bool
    {
        $dangerousPatterns = [
            '/(\%27)|\'|(\%22)|"/',
            '/(\%23)|#/',
            '/(\%3D)|=/',
            '/WHERE|where|SELECT|select|INSERT|insert|UPDATE|update|DELETE|delete|DROP|drop/ ',
            '/UNION|union/ ',
            '/--/',
            '/\/\*.*\*\//',
            '/xp_/',
            '/exec\(|execute\(|system\(/',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }

        return true;
    }

    public static function isXssSafe(string $input): bool
    {
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/on\w+\s*=\s*[^>]*>/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/expression\s*\(/i',
            '/data\s*:/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }

        return true;
    }

    public static function validateCreditCard(string $number, string $type = null): bool
    {
        $cleaned = preg_replace('/\D/', '', $number);

        if (strlen($cleaned) < 13 || strlen($cleaned) > 19) {
            return false;
        }

        if (!self::isValidLuhn($cleaned)) {
            return false;
        }

        if ($type !== null) {
            return self::matchesCardType($cleaned, $type);
        }

        return true;
    }

    private static function isValidLuhn(string $number): bool
    {
        $sum = 0;
        $isEven = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return $sum % 10 === 0;
    }

    private static function matchesCardType(string $number, string $type): bool
    {
        $patterns = [
            'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'mastercard' => '/^5[1-5][0-9]{14}$/',
            'amex' => '/^3[47][0-9]{13}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        ];

        $pattern = $patterns[strtolower($type)] ?? null;

        if ($pattern === null) {
            return false;
        }

        return preg_match($pattern, $number) === 1;
    }
}
