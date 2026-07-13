<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-07 partial — create a partial clone fragment by extracting a
 * subset of statements (Type-3).
 *
 * This extracts a consecutive subset of statements from the payload,
 * producing a fragment that represents partial duplication.
 *
 * params:
 *   start (int)   1-based ordinal of first statement to include
 *   end (int)     1-based ordinal of last statement to include
 */
final class Partial implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-07';
    }

    public function name(): string
    {
        return 'partial';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $startOrd = (int)($params['start'] ?? 1);
        $endOrd = (int)($params['end'] ?? 1);

        if ($startOrd < 1 || $endOrd < 1 || $startOrd > $endOrd) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $totalStmts = count($endLines);
        $startOrd = min($startOrd, $totalStmts);
        $endOrd = min($endOrd, $totalStmts);

        // Compute line range.
        $startLine = $startOrd > 1 ? ($endLines[$startOrd - 2] + 1) : 1;
        $endLine = $endLines[$endOrd - 1];

        $outLines = [];
        $outMap = [];
        for ($i = $startLine - 1; $i < $endLine; $i++) {
            $outLines[] = $in->lines[$i];
            $outMap[] = $in->lineMap[$i] ?? -1;
        }

        return new TransformResult($outLines, $outMap);
    }
}
