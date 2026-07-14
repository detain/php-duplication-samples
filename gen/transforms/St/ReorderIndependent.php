<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-05 reorder_independent — swap 2 independent statements (Type-3).
 *
 * Two statements are independent if neither uses a variable that the other
 * defines. The AST identifies statement boundaries; the transform swaps
 * the source text of two such statements.
 *
 * params:
 *   first (int)   1-based ordinal of first statement
 *   second (int)  1-based ordinal of second statement
 */
final class ReorderIndependent implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-05';
    }

    public function name(): string
    {
        return 'reorder_independent';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if (count($endLines) < 2) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $first = (int)($params['first'] ?? 1);
        $second = (int)($params['second'] ?? 2);

        if ($first < 1 || $second < 1 || $first > count($endLines) || $second > count($endLines)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        if ($first === $second) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Extract statement ranges.
        $getRange = function (int $ord) use ($endLines): array {
            $start = $ord > 1 ? ($endLines[$ord - 2] + 1) : 1;
            $end = $endLines[$ord - 1];
            return [$start, $end];
        };

        [$s1, $e1] = $getRange($first);
        [$s2, $e2] = $getRange($second);

        // Extract text of each statement block.
        $stmt1 = array_slice($in->lines, $s1 - 1, $e1 - $s1 + 1);
        $stmt2 = array_slice($in->lines, $s2 - 1, $e2 - $s2 + 1);

        // Rebuild lines with swapped blocks.
        $outLines = [];
        $stmt2Count = count($stmt2);
        $stmt1Count = count($stmt1);
        for ($i = 0; $i < count($in->lines); $i++) {
            $fragLine = $i + 1;
            if ($fragLine >= $s1 && $fragLine <= $e1) {
                // In first range: emit corresponding line from second.
                $idx = $fragLine - $s1;
                $outLines[] = $stmt2Count > $idx ? $stmt2[$idx] : $in->lines[$i];
            } elseif ($fragLine >= $s2 && $fragLine <= $e2) {
                // In second range: emit corresponding line from first.
                $idx = $fragLine - $s2;
                $outLines[] = $stmt1Count > $idx ? $stmt1[$idx] : $in->lines[$i];
            } else {
                $outLines[] = $in->lines[$i];
            }
        }

        return new TransformResult($outLines, $in->lineMap);
    }
}
