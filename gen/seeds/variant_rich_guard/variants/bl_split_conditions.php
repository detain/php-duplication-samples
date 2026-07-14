<?php

declare(strict_types=1);

namespace Acme\Seed\VariantRichGuard;

/**
 * BL-01 variant: split/combined conditions equivalent to the pristine guard.
 * Reorganizes conditions using boolean algebra equivalences.
 */
final class VariantRichGuardSplitSeed
{
    // <<<PAYLOAD:variant_rich_guard>>>
    public function authorize(array $user, array $resource, array $context): array
    {
        $hasUserId = isset($user['id']) && $user['id'] !== '';
        $hasValidStatus = in_array(($user['status'] ?? 'inactive'), ['active', 'verified'], true);
        $hasPrivilegedRole = !empty(array_intersect(($user['roles'] ?? []), ['admin', 'owner']));
        $isResourceOwner = isset($resource['owner_id']) && $resource['owner_id'] === ($user['id'] ?? null);
        $hasExplicitGrant = in_array(($resource['id'] ?? ''), ($user['grants'] ?? []), true);
        $hasValidOrigin = empty($context['allowed_origins'] ?? []) || in_array(($context['request_origin'] ?? ''), $context['allowed_origins'], true);

        if (!$hasUserId) {
            return ['allowed' => false, 'reason' => 'no_user_id'];
        }
        if (!$hasValidStatus) {
            return ['allowed' => false, 'reason' => 'inactive_user'];
        }
        if ($hasPrivilegedRole || $isResourceOwner || $hasExplicitGrant) {
            return ['allowed' => true, 'reason' => $hasPrivilegedRole ? 'privileged_role' : ($isResourceOwner ? 'resource_owner' : 'explicit_grant')];
        }
        if (!$hasValidOrigin) {
            return ['allowed' => false, 'reason' => 'invalid_origin'];
        }
        return ['allowed' => false, 'reason' => 'no_matching_criteria'];
    }
    // <<<END-PAYLOAD>>>
}
