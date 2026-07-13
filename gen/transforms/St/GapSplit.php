<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-06 gap_split — insert a 3-25 line foreign block (different computation,
 * different variables) at a statement boundary inside the clone (Type-3).
 *
 * Ground truth records ONE cluster with `notes` about the gap. The inserted
 * block is a self-contained computation that doesn't interfere with the
 * clone's logic.
 *
 * params:
 *   lines (int)       number of lines in the gap block (3-25, default: 8)
 *   after  (int)      1-based statement ordinal to insert after (default: random)
 */
final class GapSplit implements Transform
{
    private const GAP_TEMPLATES = [
        8 => <<<'PHP'
        // Gap: unrelated computation
        $__gap_data = [];
        for ($__i = 0; $i < 10; $i++) {
            $__gap_data[] = $i * $i;
        }
        $__gap_sum = array_sum($__gap_data);
        if ($__gap_sum > 0) {
            $__gap_avg = $__gap_sum / count($__gap_data);
        }
        unset($__gap_data);
PHP,
        12 => <<<'PHP'
        // Gap: column width analysis (irrelevant to main computation)
        $__colWidths = [];
        foreach ($columns as $__idx => $__col) {
            $__colWidths[$__idx] = strlen(trim($__col));
        }
        $__maxWidth = max($__colWidths);
        $__minWidth = min($__colWidths);
        $__avgWidth = array_sum($__colWidths) / count($__colWidths);
        unset($__colWidths);
PHP,
    ];

    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-06';
    }

    public function name(): string
    {
        return 'gap_split';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $desiredLines = (int)($params['lines'] ?? 0);
        $afterOrd = (int)($params['after'] ?? 0);

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        if ($afterOrd < 1 || $afterOrd > count($endLines)) {
            $afterOrd = $rng->int(1, count($endLines));
        }

        $insertAfterLine = $endLines[$afterOrd - 1];

        // Pick a gap template close to desired size.
        $gapBlock = self::GAP_TEMPLATES[8];
        foreach (self::GAP_TEMPLATES as $size => $template) {
            if ($desiredLines > 0 && abs($size - $desiredLines) < abs(strlen($gapBlock) - $desiredLines)) {
                $gapBlock = $template;
            }
        }
        $gapLines = explode("\n", $gapBlock);

        // Indent gap to match the insertion context.
        $outLines = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;

            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                foreach ($gapLines as $gapLine) {
                    $outLines[] = $indent . $gapLine;
                    $outMap[] = -1;
                }
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
