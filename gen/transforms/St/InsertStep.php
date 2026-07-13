<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-03 insert_step — insert a new statement (dead code insertion
 * for clone disruption) (Type-3).
 *
 * The AST supplies the fragment-relative end lines of the body's top-level
 * statements; a new statement is inserted at a random statement boundary.
 * Inserted code is syntactically valid and self-contained.
 *
 * params:
 *   statement (string)    the statement to insert (default: random from pool)
 *   after (int)           1-based statement ordinal to insert after (default: random)
 */
final class InsertStep implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-03';
    }

    public function name(): string
    {
        return 'insert_step';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $statement = $params['statement'] ?? null;
        $afterOrd = $params['after'] ?? null;

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Determine which statement to insert after.
        if ($afterOrd === null) {
            $afterOrd = $rng->int(1, count($endLines));
        } else {
            $afterOrd = (int)$afterOrd;
            if ($afterOrd < 1 || $afterOrd > count($endLines)) {
                $afterOrd = 1;
            }
        }

        // Determine the statement to insert.
        if ($statement === null) {
            $pool = [
                '$__v = trim($__v ?? "");',
                '$__tmp = array_keys([]);',
                '$__unused = null;',
                '$__dead = trim(" ");',
                '$__flag = false;',
            ];
            $statement = $rng->pick($pool);
        }

        $insertAfterLine = $endLines[$afterOrd - 1];

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;

            $fragLine = $idx + 1;
            if ($fragLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $outLines[] = $indent . $statement;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
