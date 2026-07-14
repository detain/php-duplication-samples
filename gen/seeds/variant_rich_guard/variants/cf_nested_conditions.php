<?php

declare(strict_types=1);

namespace Acme\Seed\VariantRichGuard;

/**
 * CF-03 variant: nested conditions equivalent to the pristine guard.
 * Re-expresses the early-return guard structure as nested if-blocks.
 */
final class VariantRichGuardNestedSeed
{
    // <<<PAYLOAD:variant_rich_guard>>>
    public function authorize(array $user, array $resource, array $context): array
    {
        if (isset($user['id']) && $user['id'] !== '') {
            $status = $user['status'] ?? 'inactive';
            if ($status === 'active' || $status === 'verified') {
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
            return ['allowed' => false, 'reason' => 'inactive_user'];
        }
        return ['allowed' => false, 'reason' => 'no_user_id'];
    }
    // <<<END-PAYLOAD>>>
}
