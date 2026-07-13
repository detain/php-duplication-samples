<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * LT-04 const_vs_literal — replace a literal with a class constant reference,
 * or vice versa (Type-2).
 *
 * This transform toggles between using a literal value and referencing a class
 * constant. For example: 100 -> SomeClass::MAX_ITEMS or SomeClass::MAX_ITEMS -> 100.
 *
 * params:
 *   direction (string)       'literal_to_const' or 'const_to_literal'
 *   const_class (string)     class name for the constant (default: 'Config')
 *   const_name (string)      constant name (default: 'DEFAULT_VALUE')
 *   const_value (string)     the literal value to use when converting const->literal
 */
final class ConstIndirection implements Transform
{
    public function __construct()
    {
    }

    public function code(): string
    {
        return 'LT-04';
    }

    public function name(): string
    {
        return 'const_vs_literal';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = $params['direction'] ?? 'literal_to_const';
        $className = $params['const_class'] ?? 'Config';
        $constName = $params['const_name'] ?? 'DEFAULT_VALUE';
        $constValue = $params['const_value'] ?? '100';

        $text = $in->text();

        if ($direction === 'literal_to_const') {
            // Replace the literal value string with a const reference.
            $literalStr = is_string($constValue) ? $constValue : (string)$constValue;
            $constRef = $className . '::' . $constName;

            // Match the literal as a string or number token and replace.
            $rebuilt = '';
            foreach (PhpTokens::rawTokens($text) as $t) {
                if (is_string($t)) {
                    $rebuilt .= $t;
                    continue;
                }
                if (($t[0] === T_LNUMBER || $t[0] === T_DNUMBER || $t[0] === T_CONSTANT_ENCAPSED_STRING)
                    && $t[1] === $literalStr) {
                    $rebuilt .= $constRef;
                } else {
                    $rebuilt .= $t[1];
                }
            }
        } else {
            // Replace const reference with literal value.
            $constRef = $className . '::' . $constName;
            $literalStr = is_string($constValue) ? $constValue : (string)$constValue;

            $rebuilt = '';
            foreach (PhpTokens::rawTokens($text) as $t) {
                if (is_string($t)) {
                    $rebuilt .= $t;
                    continue;
                }
                if ($t[0] === T_STRING && $t[1] === $constRef) {
                    $rebuilt .= $literalStr;
                } else {
                    $rebuilt .= $t[1];
                }
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
