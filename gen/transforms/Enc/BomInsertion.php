<?php

declare(strict_types=1);

namespace Gen\Transforms\Enc;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class BomInsertion implements Transform
{
    public function code(): string
    {
        return 'ENC-01';
    }

    public function name(): string
    {
        return 'bom';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $action = $params['action'] ?? 'insert';
        $lines = $in->lines;

        if ($action === 'insert') {
            if ($lines === []) {
                return new TransformResult(["\xEF\xBB\xBF"], [-1]);
            }
            $lines[0] = "\xEF\xBB\xBF" . $lines[0];
            $lineMap = $in->lineMap;
            $lineMap[0] = -1;
            return new TransformResult($lines, $lineMap);
        }

        if ($action === 'remove' && isset($lines[0]) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        return new TransformResult($lines, $in->lineMap);
    }
}
