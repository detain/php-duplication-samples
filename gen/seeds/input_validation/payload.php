<?php

declare(strict_types=1);

namespace Acme\Seed\InputValidation;

/**
 * Input validation using nested if-else chains.
 * ST-10 (early_return) will transform this between:
 *   - hoist_out: convert to guard-clause style (early returns at top)
 *   - sink_in: keep nested but restructure depth
 */
final class InputValidationSeed
{
    // <<<PAYLOAD:input_validation>>>
    public function validateInput(array $data): array
    {
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email is invalid';
        } else {
            if (empty($data['password'])) {
                $errors[] = 'Password is required';
            } else if (strlen($data['password']) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            } else {
                if (empty($data['username'])) {
                    $errors[] = 'Username is required';
                } else if (strlen($data['username']) < 3) {
                    $errors[] = 'Username must be at least 3 characters';
                } else {
                    if (!isset($data['terms'])) {
                        $errors[] = 'Terms must be accepted';
                    } else if ($data['terms'] !== true) {
                        $errors[] = 'Terms must be accepted';
                    }
                }
            }
        }
        return $errors ?? [];
    }
    // <<<END-PAYLOAD>>>
}