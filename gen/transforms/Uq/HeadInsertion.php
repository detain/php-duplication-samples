<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class HeadInsertion implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-06';
    }

    public function name(): string
    {
        return 'head_insertion';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $check = $params['check'] ?? '$_valid = ($_input !== null);';
        $bodyLines = $this->ast->bodyStatementEndLines($in->text());

        if ($bodyLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $outLines = [];
        $outMap = [];

        $outLines[] = $check;
        $outMap[] = -1;

        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
        }

        return new TransformResult($outLines, $outMap);
    }
}
