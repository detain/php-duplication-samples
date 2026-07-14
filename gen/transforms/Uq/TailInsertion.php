<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class TailInsertion implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-07';
    }

    public function name(): string
    {
        return 'tail_insertion';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $postfix = $params['postfix'] ?? '$_processed = true;';
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
                $outLines[] = $indent . $postfix;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
