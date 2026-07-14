<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class TabInStrings implements Transform
{
    public function code(): string
    {
        return 'ENC-07';
    }

    public function name(): string
    {
        return 'tab_in_strings';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';
        $inString = false;
        $stringStartQuote = null;

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                if (!$inString) {
                    if ($t === '"' || $t === "'") {
                        $inString = true;
                        $stringStartQuote = $t;
                    }
                } else {
                    if ($t === $stringStartQuote) {
                        $inString = false;
                        $stringStartQuote = null;
                    } elseif ($t === "\t") {
                        $t = "\t";
                    }
                }
                $rebuilt .= $t;
                continue;
            }

            if ($t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $str = $t[1];
                $quote = $str[0] ?? '"';
                if ($quote === '"') {
                    $inner = substr($str, 1, -1);
                    $inner = str_replace("\t", '    ', $inner);
                    $rebuilt .= '"' . $inner . '"';
                    continue;
                }
            }

            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
