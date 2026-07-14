<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-04 const_indirection — replace literal values with alternative numeric representations (Type-2).
 *
 * This transform toggles between using a standard literal value and an alternative
 * numeric representation (scientific notation or precision-padded literal).
 * For example: 100.0 -> 1e2 or 100.0 -> 100.00.
 *
 * params:
 *   direction (string)    'literal_to_alt' or 'alt_to_literal'
 *   style (string)        'scientific' or 'precision'
 *                         scientific: 100.0 -> 1e2, 0.07 -> 7e-2
 *                         precision:  100  -> 100.00, 0.07 -> 0.0700
 *   value (string)        the literal value being transformed
 */
final class ConstIndirection implements Transform
{
    public function code(): string
    {
        return 'LT-04';
    }

    public function name(): string
    {
        return 'const_indirection';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = $params['direction'] ?? 'literal_to_alt';
        $style = $params['style'] ?? 'scientific';
        $value = $params['value'] ?? '100.0';

        $text = $in->text();

        if ($direction === 'literal_to_alt') {
            $alt = $this->toAlternativeForm($value, $style);
            $rebuilt = '';
            foreach (PhpTokens::rawTokens($text) as $t) {
                if (is_string($t)) {
                    $rebuilt .= $t;
                    continue;
                }
                if (($t[0] === T_LNUMBER || $t[0] === T_DNUMBER || $t[0] === T_CONSTANT_ENCAPSED_STRING)
                    && $t[1] === $value) {
                    $rebuilt .= $alt;
                } else {
                    $rebuilt .= $t[1];
                }
            }
        } else {
            // alt_to_literal: convert alternative form back to original literal
            $alt = $this->toAlternativeForm($value, $style);
            $rebuilt = '';
            foreach (PhpTokens::rawTokens($text) as $t) {
                if (is_string($t)) {
                    $rebuilt .= $t;
                    continue;
                }
                if (($t[0] === T_LNUMBER || $t[0] === T_DNUMBER || $t[0] === T_STRING)
                    && $t[1] === $alt) {
                    $rebuilt .= $value;
                } else {
                    $rebuilt .= $t[1];
                }
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }

    private function toAlternativeForm(string $value, string $style): string
    {
        if ($style === 'scientific') {
            return $this->toScientificNotation($value);
        }
        return $this->toPrecisionForm($value);
    }

    private function toScientificNotation(string $value): string
    {
        // Handle integer and float strings
        $num = (float) $value;
        if ($num == 0) {
            return '0e0';
        }

        $sign = $num < 0 ? '-' : '';
        $abs = abs($num);

        // Get scientific notation: mantissa and exponent
        $exp = floor(log10($abs));
        $mantissa = $abs / pow(10, $exp);

        // Normalize mantissa to 1-10 range
        if ($mantissa >= 10) {
            $mantissa /= 10;
            $exp++;
        } elseif ($mantissa < 1 && $exp > 0) {
            $mantissa *= 10;
            $exp--;
        } elseif ($mantissa < 1 && $exp == 0) {
            // Handle case like 0.07 where exp would be negative
            while ($mantissa < 1) {
                $mantissa *= 10;
                $exp--;
            }
        }

        // Format mantissa - remove trailing zeros, keep at least one decimal if needed
        $mantissaStr = rtrim(sprintf('%.10f', $mantissa), '0');
        if (substr($mantissaStr, -1) === '.') {
            $mantissaStr .= '0';
        }

        // For whole numbers, simplify (e.g., 1e2 instead of 1.0e2)
        if (is_int((int)$mantissa) && $mantissa == (int)$mantissa) {
            $mantissaStr = (string)(int)$mantissa;
        }

        return $sign . $mantissaStr . 'e' . $exp;
    }

    private function toPrecisionForm(string $value): string
    {
        // Handle integer and float strings
        $num = (float) $value;

        // Determine decimal places based on original value
        $decimalPos = strpos($value, '.');
        $decimals = $decimalPos !== false ? strlen($value) - $decimalPos - 1 : 0;

        // For floats, use at least 2 decimal places, max 4
        $targetDecimals = max($decimals, 2);
        $targetDecimals = min($targetDecimals, 4);

        return sprintf('%.' . $targetDecimals . 'f', $num);
    }
}
