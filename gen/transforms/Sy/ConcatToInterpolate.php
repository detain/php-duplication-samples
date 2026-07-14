<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class ConcatToInterpolate implements Transform
{
    public function code(): string
    {
        return 'SY-05';
    }

    public function name(): string
    {
        return 'concat_to_interpolate';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';
        $inString = false;
        $stringContent = '';
        $stringDelim = null;

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                if ($inString) {
                    $stringContent .= $t;
                    if ($t === $stringDelim) {
                        $converted = $this->convertConcatsToInterpolate($stringContent, $stringDelim);
                        $rebuilt .= $converted;
                        $inString = false;
                        $stringContent = '';
                        $stringDelim = null;
                    }
                } else {
                    $rebuilt .= $t;
                }
                continue;
            }

            if (!$inString && ($t[0] === T_CONSTANT_ENCAPSED_STRING || $t[0] === T_ENCAPSED_AND_WHITESPACE)) {
                $content = $t[1];
                $delim = $content[0] ?? '"';
                if ($delim === '"' || $delim === "'") {
                    if ($this->hasConcatenation($content, $delim)) {
                        $inString = true;
                        $stringContent = $content;
                        $stringDelim = $delim;
                        continue;
                    }
                }
            }

            $rebuilt .= $t[1];
        }

        if ($inString) {
            $rebuilt .= $stringContent;
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }

    private function hasConcatenation(string $content, string $delim): bool
    {
        $inside = substr($content, 1, -1);
        return strpos($inside, '.') !== false;
    }

    private function convertConcatsToInterpolate(string $content, string $delim): string
    {
        if ($delim === "'") {
            return $content;
        }

        $inside = substr($content, 1, -1);
        $result = '"';

        $segments = preg_split('/(\.[^.]*\.?)/', $inside, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($segments as $seg) {
            $seg = trim($seg);
            if ($seg === '') {
                continue;
            }
            if (strpos($seg, '.') === 0) {
                $afterDot = trim(substr($seg, 1));
                if ($afterDot === '') {
                    continue;
                }
                if (preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:\[[^\]]+\]|\->[a-zA-Z_][a-zA-Z0-9_]*)*$/', $afterDot, $m)) {
                    $result .= '{' . $m[0] . '}';
                } else {
                    $result .= '" . ' . $this->makeSimpleString($afterDot) . ' . "';
                }
            } else {
                if (preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:\[[^\]]+\]|\->[a-zA-Z_][a-zA-Z0-9_]*)*$/', $seg, $m)) {
                    $result .= '{' . $m[0] . '}';
                } else {
                    $result .= $this->makeSimpleString($seg);
                }
            }
        }

        $result .= '"';
        return $result;
    }

    private function makeSimpleString(string $value): string
    {
        if (preg_match('/^(?:[\'"][^\'"]*[\'"])$/', $value)) {
            return $value;
        }
        return '"' . addslashes($value) . '"';
    }
}
