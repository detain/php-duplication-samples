<?php

declare(strict_types=1);

namespace Acme\Seed\TreeWalker;

final class TreeWalkerSeed
{
    // <<<PAYLOAD:tree_walker>>>
    /**
     * Collect all leaf-node values from a tree via recursive traversal.
     * Payload: recursive tree walk.
     */
    public function walk(array $node, array &$leaves = []): array
    {
        $children = $node['children'] ?? [];
        if (empty($children)) {
            $leaves[] = $node['value'] ?? null;
        } else {
            foreach ($children as $child) {
                $this->walk($child, $leaves);
            }
        }
        return $leaves;
    }
    // <<<END-PAYLOAD>>>
}
