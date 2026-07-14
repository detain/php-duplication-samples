<?php

declare(strict_types=1);

namespace Gen\Transforms\Rf;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RF-01 extracted_helper — extract a helper method from duplicated logic,
 * mount it as a fragment (Type-3).
 *
 * This transform identifies a contiguous block of statements that could be
 * extracted into a separate helper method, and replaces them with a call to
 * that helper. The extracted helper body becomes the fragment.
 *
 * params:
 *   helper_name (string)  name for the extracted helper (default: generate from context)
 *   extract_count (int)   number of statements to extract (default: random 2-5)
 */
final class ExtractedHelper implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RF-01';
    }

    public function name(): string
    {
        return 'extracted_helper';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $helperName = $params['helper_name'] ?? null;
        $extractCount = (int)($params['extract_count'] ?? 0);

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $totalStmts = count($endLines);
        if ($totalStmts < 2) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Determine extraction range.
        if ($extractCount <= 0) {
            $extractCount = $rng->int(2, min(5, $totalStmts - 1));
        }
        $extractCount = min($extractCount, $totalStmts - 1);

        $startOrd = $rng->int(1, $totalStmts - $extractCount);
        $endOrd = $startOrd + $extractCount - 1;

        // Compute line range of extraction.
        $startLine = $startOrd > 1 ? ($endLines[$startOrd - 2] + 1) : 1;
        $endLine = $endLines[$endOrd - 1];

        // Generate helper name if not provided.
        if ($helperName === null) {
            $pool = ['processHelper', 'computeValue', 'formatData', 'validateInput', 'normalizeState'];
            $helperName = $rng->pick($pool);
        }

        // Phase 1: Add remaining lines BEFORE the extraction point.
        for ($idx = 0; $idx < count($in->lines); $idx++) {
            $fragLine = $idx + 1;
            if ($fragLine >= $startLine) {
                break;
            }
            $outLines[] = $in->lines[$idx];
            $outMap[] = $in->lineMap[$idx] ?? -1;
        }

        // Collect extracted lines for analysis.
        $extractedLines = [];
        $extractedMap = [];
        for ($idx = 0; $idx < count($in->lines); $idx++) {
            $fragLine = $idx + 1;
            if ($fragLine >= $startLine && $fragLine <= $endLine) {
                $extractedLines[] = $in->lines[$idx];
                $extractedMap[] = $in->lineMap[$idx] ?? -1;
            }
        }

        // Determine indent from first extracted line.
        $indent = '';
        if (count($extractedLines) > 0) {
            preg_match('/^(\s*)/', $extractedLines[0], $m);
            $indent = $m[1];
        }

        // Determine if we need to add a return statement based on extracted content.
        $hasReturn = false;
        foreach ($extractedLines as $extractedLine) {
            if (str_contains(trim($extractedLine), 'return ')) {
                $hasReturn = true;
                break;
            }
        }

        // Phase 2: Insert the helper call IN PLACE of the extracted block.
        $callLine = $indent . '$__result = ' . $helperName . '($__ctx);';
        if ($hasReturn) {
            $callLine = $indent . 'return ' . $helperName . '($__ctx);';
        }
        $outLines[] = $callLine;
        $outMap[] = -1;

        // Also prepend helper definition comment marker.
        $helperDefLine = $indent . '// HELPER: ' . $helperName . ' (extracted lines ' . $startLine . '-' . $endLine . ')';
        $outLines[] = $helperDefLine;
        $outMap[] = -1;

        // Phase 3: Add remaining lines AFTER the extraction point.
        for ($idx = 0; $idx < count($in->lines); $idx++) {
            $fragLine = $idx + 1;
            if ($fragLine > $endLine) {
                $outLines[] = $in->lines[$idx];
                $outMap[] = $in->lineMap[$idx] ?? -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
