<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class UnicodeConfusables implements Transform
{
    public function code(): string
    {
        return 'ENC-03';
    }

    public function name(): string
    {
        return 'unicode_ident';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';
        $variants = [
            'a' => ['a', 'à', 'á', 'â', 'ä', 'æ', 'ā', 'ą', 'ă'],
            'e' => ['e', 'è', 'é', 'ê', 'ë', 'ē', 'ę', 'ě'],
            'i' => ['i', 'ì', 'í', 'î', 'ï', 'ī', 'į', 'ı'],
            'o' => ['o', 'ò', 'ó', 'ô', 'ö', 'ø', 'ō', 'œ'],
            'u' => ['u', 'ù', 'ú', 'û', 'ü', 'ū', 'ů', 'ų'],
            'c' => ['c', 'ç', 'ć', 'č'],
            'n' => ['n', 'ñ', 'ń', 'ň'],
            's' => ['s', 'ß', 'ś', 'š'],
            'z' => ['z', 'ż', 'ź', 'ž'],
        ];

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            $tokenText = $t[1];

            if ($t[0] === T_VARIABLE) {
                $varName = substr($tokenText, 1);
                $firstChar = $varName[0] ?? '';
                $firstCharLower = strtolower($firstChar);
                if (isset($variants[$firstCharLower])) {
                    $variantSet = $variants[$firstCharLower];
                    $pick = $rng->pick($variantSet);
                    if ($firstChar !== $pick && $firstChar !== strtoupper($firstChar)) {
                        $pick = strtoupper($pick);
                    }
                    $rebuilt .= '$' . $pick . substr($varName, 1);
                    continue;
                }
            }

            $rebuilt .= $tokenText;
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
