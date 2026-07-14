<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class PositionalToNamed implements Transform
{
    public function code(): string
    {
        return 'SY-01';
    }

    public function name(): string
    {
        return 'positional_to_named';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';
        $tokens = PhpTokens::rawTokens($text);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            [$id, $text2, $line] = $t;

            if ($id === T_STRING && $i + 2 < $count) {
                $next = $tokens[$i + 1] ?? '';
                $nextNext = $tokens[$i + 2] ?? '';
                if (is_string($next) && $next === '(') {
                    $args = $this->extractArguments($tokens, $i + 1);
                    if ($args !== []) {
                        $named = $this->toNamedArgs($args);
                        $closeIdx = $this->findMatchingParen($tokens, $i + 1);
                        if ($closeIdx !== null) {
                            for ($j = $i + 1; $j <= $closeIdx; $j++) {
                                $rebuilt .= is_string($tokens[$j]) ? $tokens[$j] : ($tokens[$j][1] ?? '');
                            }
                            $rebuilt = substr($rebuilt, 0, -1) . $named . ')';
                            $i = $closeIdx;
                            continue;
                        }
                    }
                }
            }

            $rebuilt .= $text2;
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }

    private function extractArguments(array $tokens, int $parenStart): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        for ($i = $parenStart + 1; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            if (is_string($t)) {
                if ($t === '(') {
                    $depth++;
                } elseif ($t === ')') {
                    if ($depth === 0) {
                        break;
                    }
                    $depth--;
                } elseif ($t === ',' && $depth === 0) {
                    $args[] = trim($current);
                    $current = '';
                    continue;
                }
                $current .= $t;
            } else {
                $current .= $t[1] ?? '';
            }
        }
        if (trim($current) !== '') {
            $args[] = trim($current);
        }
        return $args;
    }

    private function findMatchingParen(array $tokens, int $parenStart): ?int
    {
        $depth = 0;
        for ($i = $parenStart; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            if (is_string($t)) {
                if ($t === '(') {
                    $depth++;
                } elseif ($t === ')') {
                    $depth--;
                    if ($depth === 0) {
                        return $i;
                    }
                }
            }
        }
        return null;
    }

    private function toNamedArgs(array $args): string
    {
        $named = [];
        foreach ($args as $arg) {
            if (preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $arg, $m)) {
                $named[] = $m[1] . ': ' . $arg;
            } else {
                $named[] = $arg;
            }
        }
        return implode(', ', $named);
    }
}
