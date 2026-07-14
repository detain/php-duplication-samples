<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class InputPreprocessing implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-04';
    }

    public function name(): string
    {
        return 'input_preprocessing';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $normalization = $params['normalization'] ?? 'trim';
        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $insertAfterLine = $endLines[count($endLines) - 1];

        $normFunc = match ($normalization) {
            'trim' => 'trim($_input)',
            'strtolower' => 'strtolower($_input)',
            'strtoupper' => 'strtoupper($_input)',
            'htmlspecialchars' => 'htmlspecialchars($_input, ENT_QUOTES, \'UTF-8\')',
            default => 'trim($_input)',
        };

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $uniqueCode = $indent . '$_input = ' . $normFunc . ';';
                $outLines[] = $uniqueCode;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
