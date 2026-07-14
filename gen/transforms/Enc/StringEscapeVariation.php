<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class StringEscapeVariation implements Transform
{
    public function code(): string
    {
        return 'ENC-05';
    }

    public function name(): string
    {
        return 'string_escapes';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            if ($t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $str = $t[1];
                $quote = $str[0] ?? '"';

                if ($quote === '"') {
                    $content = substr($str, 1, -1);
                    $content = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function ($m) {
                        $dec = hexdec($m[1]);
                        return chr($dec);
                    }, $content);
                    $content = preg_replace_callback('/\\\\(\d{3})/', function ($m) {
                        return chr((int)$m[1]);
                    }, $content);
                    $rebuilt .= '"' . $content . '"';
                    continue;
                }
            }

            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
