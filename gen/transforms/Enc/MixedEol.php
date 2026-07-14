<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class MixedEol implements Transform
{
    public function code(): string
    {
        return 'ENC-04';
    }

    public function name(): string
    {
        return 'mixed_eol';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $lines = $in->lines;
        $outLines = [];

        foreach ($lines as $idx => $line) {
            if ($idx % 3 === 0 && $idx > 0) {
                $outLines[] = $line . "\r\n";
            } else {
                $outLines[] = $line . "\n";
            }
        }

        $combined = implode('', $outLines);
        $finalLines = explode("\n", str_replace("\r\n", "\n", $combined));

        return new TransformResult($finalLines, $in->lineMap);
    }
}
