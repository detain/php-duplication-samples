<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-12 return_shape_reorder — reorder keys of a returned literal array (Type-3).
 *
 * Reorders the keys in a returned array literal. Since PHP arrays are ordered
 * maps, key order is visible to consumers (except when using numeric sequential
 * keys where order doesn't matter for var_export).
 *
 * params:
 *   key_order (list<string>)  desired key order (list of keys to reorder)
 *      e.g., ["total", "tax", "subtotal", "discount"] for shuffled return order
 */
final class ReturnShapeReorder implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-12';
    }

    public function name(): string
    {
        return 'return_shape_reorder';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $keyOrder = $params['key_order'] ?? null;

        // If no key order specified, generate a random permutation
        if ($keyOrder === null) {
            $text = $in->text();
            $lines = $in->lines;

            // Find return statements with array literals
            // Simple pattern: return ['key' => value, ...];
            $pattern = '/return\s*\[[\s\S]*?\];/';

            if (preg_match($pattern, $text, $matches)) {
                $arrayContent = $matches[0];

                // Extract key-value pairs
                preg_match_all('/[\'"]([\w]+)[\'"]\s*=>\s*[^,]+,?/', $arrayContent, $kvMatches, PREG_SET_ORDER);

                if (count($kvMatches) > 1) {
                    // Shuffle the key order
                    $keys = array_column($kvMatches, 1);
                    $keys = $rng->shuffle($keys);

                    // Rebuild array with shuffled key order
                    $newPairs = [];
                    foreach ($kvMatches as $i => $kv) {
                        $newPairs[$keys[$i]] = $kv[0];
                    }

                    // Replace in original text
                    $newArray = 'return [';
                    $pairsPerLine = [];
                    foreach ($newPairs as $key => $pair) {
                        $pairsPerLine[] = "    '$key' => " . preg_replace('/^[\'"]([\w]+)[\'"]\s*=>\s*/', '', $pair);
                    }
                    $newArray .= implode(",\n", $pairsPerLine) . "\n];";
                    $rebuilt = str_replace($arrayContent, $newArray, $text);

                    $lines = explode("\n", $rebuilt);
                    return new TransformResult($lines, $in->lineMap);
                }
            }
        } elseif (is_array($keyOrder) && count($keyOrder) > 1) {
            // Use the specified key order
            $text = $in->text();
            $pattern = '/return\s*\[[\s\S]*?\];/';

            if (preg_match($pattern, $text, $matches)) {
                $arrayContent = $matches[0];

                preg_match_all('/[\'"]([\w]+)[\'"]\s*=>\s*[^,]+,?/', $arrayContent, $kvMatches, PREG_SET_ORDER);

                if (count($kvMatches) > 1) {
                    // Build lookup
                    $pairMap = [];
                    foreach ($kvMatches as $kv) {
                        $pairMap[$kv[1]] = $kv[0];
                    }

                    // Rebuild array with specified key order
                    $newPairs = [];
                    foreach ($keyOrder as $key) {
                        if (isset($pairMap[$key])) {
                            $newPairs[$key] = $pairMap[$key];
                        }
                    }

                    // Add any keys not in specified order
                    foreach ($pairMap as $key => $pair) {
                        if (!in_array($key, $keyOrder, true)) {
                            $newPairs[$key] = $pair;
                        }
                    }

                    $newArray = 'return [';
                    $pairsPerLine = [];
                    foreach ($newPairs as $key => $pair) {
                        $pairsPerLine[] = "    '$key' => " . preg_replace('/^[\'"]([\w]+)[\'"]\s*=>\s*/', '', $pair);
                    }
                    $newArray .= implode(",\n", $pairsPerLine) . "\n];";
                    $rebuilt = str_replace($arrayContent, $newArray, $text);

                    $lines = explode("\n", $rebuilt);
                    return new TransformResult($lines, $in->lineMap);
                }
            }
        }

        return new TransformResult($in->lines, $in->lineMap);
    }
}
