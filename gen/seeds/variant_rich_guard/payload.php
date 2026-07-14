<?php

declare(strict_types=1);

namespace Acme\Seed\VariantRichGuard;

/**
 * Seed payload: rich access guard with multiple authorization checks.
 * Combines user status, role, ownership, and resource-specific grants.
 * Variants provide equivalent logic with different control flow.
 */
final class VariantRichGuardSeed
{
    // <<<PAYLOAD:variant_rich_guard>>>
    public function authorize(array $user, array $resource, array $context): array
    {
        if (!isset($user['id']) || $user['id'] === '') {
            return ['allowed' => false, 'reason' => 'no_user_id'];
        }
        $status = $user['status'] ?? 'inactive';
        if ($status !== 'active' && $status !== 'verified') {
            return ['allowed' => false, 'reason' => 'inactive_user'];
        }
        $roles = $user['roles'] ?? [];
        if (in_array('admin', $roles, true) || in_array('owner', $roles, true)) {
            return ['allowed' => true, 'reason' => 'privileged_role'];
        }
        $ownerId = $resource['owner_id'] ?? null;
        if ($ownerId !== null && $ownerId === $user['id']) {
            return ['allowed' => true, 'reason' => 'resource_owner'];
        }
        $grants = $user['grants'] ?? [];
        $resourceId = $resource['id'] ?? '';
        if (in_array($resourceId, $grants, true)) {
            return ['allowed' => true, 'reason' => 'explicit_grant'];
        }
        $allowedOrigins = $context['allowed_origins'] ?? [];
        $requestOrigin = $context['request_origin'] ?? '';
        if (!empty($allowedOrigins) && !in_array($requestOrigin, $allowedOrigins, true)) {
            return ['allowed' => false, 'reason' => 'invalid_origin'];
        }
        return ['allowed' => false, 'reason' => 'no_matching_criteria'];
    }
    // <<<END-PAYLOAD>>>
}
