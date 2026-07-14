<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class PropertyExistsToIsset implements Transform
{
    public function code(): string
    {
        return 'SY-08';
    }

    public function name(): string
    {
        return 'property_exists_to_isset';
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

        $rebuilt = preg_replace(
            '/property_exists\s*\(\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*|[\$a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*,\s*[\'"]?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\'"]?\s*\)/',
            'isset($1->$2)',
            $rebuilt
        );

        $rebuilt = preg_replace(
            '/property_exists\s*\(\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*::\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\)/',
            'isset($1::$2)',
            $rebuilt
        );

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
