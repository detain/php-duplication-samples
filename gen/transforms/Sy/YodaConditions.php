<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class YodaConditions implements Transform
{
    public function code(): string
    {
        return 'SY-06';
    }

    public function name(): string
    {
        return 'yoda_conditions';
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
            $rebuilt .= $t[1];
        }

        $rebuilt = preg_replace_callback(
            '/\b(\d+(?:\.\d+)?)\s*(===|!==|==|!=|<=|>=|<|>)\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/',
            function ($m) {
                return $m[3] . ' ' . $this->flipOp($m[2]) . ' ' . $m[1];
            },
            $rebuilt
        );

        $rebuilt = preg_replace_callback(
            '/\b(true|false|null)\s*(===|!==|==|!=|<=|>=|<|>)\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/',
            function ($m) {
                return $m[3] . ' ' . $this->flipOp($m[2]) . ' ' . $m[1];
            },
            $rebuilt
        );

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }

    private function flipOp(string $op): string
    {
        return match ($op) {
            '===' => '===',
            '!==' => '!==',
            '==' => '==',
            '!=' => '!=',
            '<' => '>',
            '>' => '<',
            '<=' => '>=',
            '>=' => '<=',
            default => $op,
        };
    }
}
