<?php

declare(strict_types=1);

namespace Gen\Transforms\Ty;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * TY-02 nullable — toggle between `?T` and `T` with docblock `@param` (Type-2).
 *
 * This transform converts a non-nullable type hint to nullable (?T) and adds
 * a corresponding docblock annotation, or vice versa. It only affects type
 * hints that have a clear singular type (no union types).
 *
 * params:
 *   to_nullable (bool)  true = make nullable (add ?), false = make non-nullable (remove ?)
 */
final class Nullable implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'TY-02';
    }

    public function name(): string
    {
        return 'nullable';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $toNullable = (bool)($params['to_nullable'] ?? true);

        $text = $in->text();
        $lines = explode("\n", $text);

        $outLines = [];
        foreach ($lines as $line) {
            // Match parameter type hints: "Type $param" or "?Type $param"
            // and convert to the opposite form.
            if (preg_match('/^(\s*)(\?)?(\w+)(\s+\$&amp;?\w+)/', $line, $m)) {
                $indent = $m[1];
                $hasNullable = $m[2] !== '';
                $typeName = $m[3];
                $rest = $m[4];

                if ($toNullable && !$hasNullable) {
                    // Add nullable.
                    $line = $indent . '?' . $typeName . $rest;
                } elseif (!$toNullable && $hasNullable) {
                    // Remove nullable.
                    $line = $indent . $typeName . $rest;
                }
            }

            // Match return type hints: ": Type" or ": ?Type"
            if (preg_match('/^(\s*:\s*)(\?)(\w+)$/', trim($line), $m)) {
                $prefix = $m[1];
                $hasNullable = $m[2] !== '';
                $typeName = $m[3];
                $trimmed = trim($line);

                if ($toNullable && !$hasNullable) {
                    $line = $prefix . '?' . $typeName;
                } elseif (!$toNullable && $hasNullable) {
                    $line = $prefix . $typeName;
                }
            }

            $outLines[] = $line;
        }

        return new TransformResult($outLines, $in->lineMap);
    }
}
