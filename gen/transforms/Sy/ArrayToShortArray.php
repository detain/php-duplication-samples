<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class ArrayToShortArray implements Transform
{
    public function code(): string
    {
        return 'SY-04';
    }

    public function name(): string
    {
        return 'array_to_short';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $tokens = PhpTokens::rawTokens($text);
        $rebuilt = '';
        $i = 0;
        $len = count($tokens);

        while ($i < $len) {
            $t = $tokens[$i];

            if (!is_string($t) && $t[0] === T_ARRAY) {
                $rebuilt .= '[';
                $i++;

                $depth = 0;
                $content = '';
                $foundParen = false;

                while ($i < $len) {
                    $nt = $tokens[$i];
                    if (is_string($nt)) {
                        if ($nt === '(' && !$foundParen) {
                            $foundParen = true;
                            $i++;
                            continue;
                        }
                        if ($nt === '(') {
                            $depth++;
                            $content .= $nt;
                        } elseif ($nt === ')') {
                            if ($depth === 0) {
                                break;
                            }
                            $depth--;
                            $content .= $nt;
                        } else {
                            $content .= $nt;
                        }
                    } else {
                        $content .= $nt[1] ?? '';
                    }
                    $i++;
                }

                $rebuilt .= trim($content) . ']';
                $i++;
                continue;
            }

            if (is_string($t)) {
                $rebuilt .= $t;
            } else {
                $rebuilt .= $t[1] ?? '';
            }
            $i++;
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
