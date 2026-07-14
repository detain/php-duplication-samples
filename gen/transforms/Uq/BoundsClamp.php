<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class BoundsClamp implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-03';
    }

    public function name(): string
    {
        return 'bounds_clamp';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $min = $params['min'] ?? 0;
        $max = $params['max'] ?? 100;
        $varname = $params['varname'] ?? '$_value';

        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $insertAfterLine = $endLines[count($endLines) - 1];

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $uniqueCode = $indent . $varname . ' = max(' . $min . ', min(' . $max . ', ' . $varname . '));';
                $outLines[] = $uniqueCode;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
