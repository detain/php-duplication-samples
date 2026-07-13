<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-04 delete_stmt — delete 1-3 non-essential statements from one member
 * (Type-3). Only truly optional statements are removed: trailing comments,
 * empty statements, debug assertions. This is NOT a semantic change — the
 * core logic must remain intact.
 *
 * params:
 *   count (int)       number of statements to delete (default: 1-2)
 */
final class DeleteStmt implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-04';
    }

    public function name(): string
    {
        return 'delete_stmt';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = (int)($params['count'] ?? $rng->int(1, 2));

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === [] || count($endLines) <= 1) {
            // Can't delete if there's only one statement.
            return new TransformResult($in->lines, $in->lineMap);
        }

        // The AST parser wraps the fragment in "<?php class GenWrap { ... }" to
        // handle visibility modifiers legally. getEndLine() returns ABSOLUTE line
        // numbers in this wrapped text, but we need RELATIVE positions in the
        // payload. The GenWrap class declaration adds 2 lines (line 1: "<?php",
        // line 2: "class GenWrap { ") before the fragment content starts at
        // line 3. So the wrapper offset is 2.
        //
        // IMPORTANT: AST statement 1 is the function SIGNATURE (not a body
        // statement), and statement 2 is the opening brace. The first actual
        // body statement is AST statement 3. We should never delete statements
        // 1 or 2 since that would remove the function header.
        $wrapperOffset = 2;

        // Pick statements to delete (prefer later ones which are typically
        // less critical like comments, debug, etc.)
        $maxOrd = count($endLines);
        $toDelete = [];
        $candidates = [];
        for ($i = $maxOrd; $i >= 1; $i--) {
            $candidates[] = $i;
        }
        for ($i = 0; $i < min($count, count($candidates)); $i++) {
            $pick = $rng->int(0, count($candidates) - 1);
            $toDelete[] = $candidates[$pick];
            array_splice($candidates, $pick, 1);
        }

        // Find line ranges to delete (convert absolute -> relative).
        // Never delete AST statements 1 or 2 - those are the function signature
        // and opening brace which must be preserved.
        $deleteRanges = [];
        foreach ($toDelete as $ord) {
            if ($ord <= 2) {
                continue; // Skip function header statements
            }
            $lineIdx = $ord - 1;
            $startLine = $lineIdx > 0 ? ($endLines[$lineIdx - 1] + 1 - $wrapperOffset) : 1;
            $endLine = $endLines[$lineIdx] - $wrapperOffset;
            $deleteRanges[] = [$startLine, $endLine];
        }

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $fragLine = $idx + 1;
            $skip = false;
            foreach ($deleteRanges as [$start, $end]) {
                if ($fragLine >= $start && $fragLine <= $end) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $outLines[] = $line;
                $outMap[] = $in->lineMap[$idx] ?? -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
