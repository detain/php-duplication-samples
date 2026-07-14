<?php

declare(strict_types=1);

namespace Acme\Seed\FormValidator;

final class FormValidatorSeed
{
    // <<<PAYLOAD:form_validator>>>
    /**
     * Validate a registration form submission.
     * Payload: nested ifs accumulating errors with else-branching.
     */
    public function validate(array $data): array
    {
        $errors = [];
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
                }
            }
        }
        return $errors;
    }
    // <<<END-PAYLOAD>>>
}
