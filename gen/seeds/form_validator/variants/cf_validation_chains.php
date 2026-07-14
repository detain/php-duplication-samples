<?php

declare(strict_types=1);

namespace Acme\Seed\FormValidator;

/**
 * CF-09 variant: the same form validation re-expressed using
 * guard-clause early-returns instead of nested if-else chains.
 * Behaviorally identical to the nested-conditional payload.
 */
final class FormValidatorGuardChainVariant
{
    // <<<PAYLOAD:form_validator>>>
    public function validate(array $data): array
    {
        if (empty($data['email'])) {
            return ['Email is required'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['Email is invalid'];
        }
        if (empty($data['password'])) {
            return ['Password is required'];
        }
        if (strlen($data['password']) < 8) {
            return ['Password must be at least 8 characters'];
        }
        if (empty($data['username'])) {
            return ['Username is required'];
        }
        if (strlen($data['username']) < 3) {
            return ['Username must be at least 3 characters'];
        }
        return [];
    }
    // <<<END-PAYLOAD>>>
}
