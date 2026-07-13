<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-04 operator_spacing — toggle spacing around binary operators.
 *
 * Toggle: a+b  <->  a + b
 *         a-b  <->  a - b
 *         a*b  <->  a * b
 *         a/b  <->  a / b
 *         a%b  <->  a % b
 * Also handles compound assignment: a+=b <-> a += b, etc.
 *
 * Token-stream preserving (still Type-1 after normalization).
 *
 * params:
 *   style (string, default 'spaced')  'spaced' -> 'a + b'  |  'tight' -> 'a+b'
 */
final class OperatorSpacing implements Transform
{
    public function code(): string
    {
        return 'WS-04';
    }

    public function name(): string
    {
        return 'operator_spacing';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'spaced';

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $outLines[] = $this->transformLine($line, $style);
            $outMap[]   = $in->lineMap[$idx];
        }

        return new TransformResult($outLines, $outMap);
    }

    private function transformLine(string $line, string $style): string
    {
        if ($style === 'tight') {
            // 'spaced' -> 'tight': remove spaces around operators
            return preg_replace_callback(
                '/([a-zA-Z0-9_\$]+)\s*([\+\-\*\/\%]|(\+\+)|(--)|(<<)|(>>)|(>>>))\s*([a-zA-Z0-9_\$]+)/',
                static fn(array $m): string => $m[1] . $m[2] . $m[8],
                $line
            ) ?? $line;
        }

        // 'tight' -> 'spaced': add spaces around operators
        // Compound assignment operators (+=, -=, *=, /=, %=, <<=, >>=, >>>=)
        // Comparison operators (==, !=, ===, !==, <, >, <=, >=, <=>, ??)
        // Logical operators (&&, ||, ??, ?:)
        // Bitwise operators (&, |, ^, ~, <<, >>, >>>)
        // Arithmetic operators (+, -, *, /, %)
        // Increment/decrement (++, --)
        return preg_replace_callback(
            '/([a-zA-Z0-9_\$]+)(\s*)((?:\+\+|--|\+=|-=|\*=|\/=|\%=|<<=|>>=|>>>=|[+\-*\/%<>]=?|&&|\|\||\?\?|:|<<|>>|>>>|&|\||\^|~))(\s*)([a-zA-Z0-9_\$]+)/',
            static fn(array $m): string => $m[1] . ' ' . $m[3] . ' ' . $m[5],
            $line
        ) ?? $line;
    }
}
