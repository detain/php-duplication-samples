<?php

declare(strict_types=1);

namespace Gen\Transforms\Ty;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * TY-01 type_hint — add, remove, or change type hints on
 * function parameters (Type-2).
 *
 * params:
 *   operation (string)           'add', 'remove', 'change'
 *   param_name (string)         parameter name to modify
 *   type_hint (string)          type hint to add or change to (empty for remove)
 */
final class TypeHint implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'TY-01';
    }

    public function name(): string
    {
        return 'type_hint';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $operation = $params['operation'] ?? 'add';
        $paramName = $params['param_name'] ?? '';
        $typeHint = $params['type_hint'] ?? '';

        if ($paramName === '') {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();
        $lines = explode("\n", $text);
        $outLines = [];

        foreach ($lines as $line) {
            if ($operation === 'add' || $operation === 'change') {
                if ($typeHint === '') {
                    $outLines[] = $line;
                    continue;
                }
                // Add or replace type hint before $paramName.
                // Pattern: optionally nullable type, then whitespace, then $paramName.
                // Replace: int $foo -> TypeHint $foo
                // Or: $foo -> TypeHint $foo
                $pattern = '/(\??\w+)\s+\$' . preg_quote($paramName, '/') . '/';
                if (preg_match($pattern, $line)) {
                    $line = preg_replace($pattern, $typeHint . ' $' . $paramName, $line);
                } else {
                    // No type hint present, add one before $paramName.
                    $line = preg_replace(
                        '/(\$' . preg_quote($paramName, '/') . ')/',
                        $typeHint . ' $1',
                        $line
                    );
                }
            } elseif ($operation === 'remove') {
                // Remove type hint: int $foo -> $foo
                $pattern = '/\??\w+\s+\$' . preg_quote($paramName, '/') . '/';
                $line = preg_replace($pattern, '$' . $paramName, $line);
            }

            // Handle return type on function signature line.
            if ($operation === 'add' || $operation === 'change') {
                if ($typeHint === '' && preg_match('/^\s*function\s+\w+\s*\([^)]*\)\s*:\s*\??\w+/', $line)) {
                    // Remove return type.
                    $line = preg_replace('/:\s*\??\w+/', '', $line);
                } elseif ($typeHint !== '' && preg_match('/^\s*function\s+\w+\s*\([^)]*\)\s*:\s*\??\w+/', $line)) {
                    // Change return type.
                    $line = preg_replace('/:\s*\??\w+/', ': ' . $typeHint, $line);
                }
            }

            $outLines[] = $line;
        }

        return new TransformResult($outLines, $in->lineMap);
    }
}
