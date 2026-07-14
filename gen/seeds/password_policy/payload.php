<?php

declare(strict_types=1);

namespace Acme\Seed\PasswordPolicy;

/**
 * Seed payload: password validation with configurable rules.
 * Checks minimum length, uppercase, lowercase, digits, and special chars.
 */
final class PasswordPolicySeed
{
    // <<<PAYLOAD:password_policy>>>
    public function validatePassword(string $password, array $rules): array
    {
        $errors = [];
        if (isset($rules['min_length']) && strlen($password) < $rules['min_length']) {
            $errors[] = 'too_short';
        }
        if (isset($rules['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'missing_uppercase';
        }
        if (isset($rules['require_lowercase']) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'missing_lowercase';
        }
        if (isset($rules['require_digit']) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'missing_digit';
        }
        if (isset($rules['require_special']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'missing_special';
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    // <<<END-PAYLOAD>>>
}