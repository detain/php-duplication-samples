<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class NbspIndentation implements Transform
{
    public function code(): string
    {
        return 'ENC-02';
    }

    public function name(): string
    {
        return 'nbsp';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $action = $params['action'] ?? 'replace';
        $lines = $in->lines;

        if ($action === 'replace') {
            foreach ($lines as $idx => $line) {
                if (preg_match('/^(\s+)/', $line, $m)) {
                    $spaces = $m[1];
                    $nbsp = str_replace(' ', "\xC2\xA0", $spaces);
                    $lines[$idx] = $nbsp . substr($line, strlen($spaces));
                }
            }
        }

        return new TransformResult($lines, $in->lineMap);
    }
}
