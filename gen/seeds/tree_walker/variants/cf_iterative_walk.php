<?php

declare(strict_types=1);

namespace Acme\Seed\TreeWalker;

/**
 * CF-07 variant: the same tree traversal re-expressed using
 * an explicit iterative stack instead of recursion.
 * Behaviorally identical to the recursive payload.
 */
final class TreeWalkerIterativeVariant
{
    // <<<PAYLOAD:tree_walker>>>
    public function walk(array $node, array &$leaves = []): array
    {
        $stack = [$node];
        while (!empty($stack)) {
            $current = array_pop($stack);
            $children = $current['children'] ?? [];
            if (empty($children)) {
                $leaves[] = $current['value'] ?? null;
            } else {
                foreach (array_reverse($children) as $child) {
                    $stack[] = $child;
                }
            }
        }
        return $leaves;
    }
    // <<<END-PAYLOAD>>>
}
