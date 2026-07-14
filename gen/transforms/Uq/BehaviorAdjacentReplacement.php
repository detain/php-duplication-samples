<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class BehaviorAdjacentReplacement implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-05';
    }

    public function name(): string
    {
        return 'behavior_adjacent_replacement';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $strategy = $params['strategy'] ?? 'guard_clause';
        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $insertAfterLine = $endLines[count($endLines) - 1];

        $alternateCode = match ($strategy) {
            'guard_clause' => 'if ($_result === false) { return null; }',
            'early_return' => 'if (empty($_result)) { return []; }',
            'null_check' => '$_result = $_result ?? [];',
            default => '// strategy: ' . $strategy,
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
                $outLines[] = $indent . $alternateCode;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
