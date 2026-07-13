<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-02 insert_dead — insert 1-3 unused variable assignments or dead if-blocks
 * inside the clone (Type-3).
 *
 * The AST supplies the fragment-relative end lines of the body's top-level
 * statements; dead code is inserted at random statement boundaries. Inserted
 * code is syntactically valid and self-contained, so php -l stays clean.
 *
 * params:
 *   count (int)        number of dead assignments to insert (default: 2)
 *   after  (list<int>)  1-based statement ordinals to insert after (default: [1])
 */
final class InsertDead implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-02';
    }

    public function name(): string
    {
        return 'insert_dead';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = (int)($params['count'] ?? $rng->int(1, 3));
        $afterOrdinals = $params['after'] ?? null;

        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        if ($afterOrdinals === null) {
            // Pick random statement boundaries.
            $maxOrd = count($endLines);
            $afterOrdinals = [];
            for ($i = 0; $i < min($count, $maxOrd); $i++) {
                $afterOrdinals[] = $rng->int(1, $maxOrd);
            }
            $afterOrdinals = array_unique($afterOrdinals);
        }

        // Map chosen statement ordinals -> the fragment line to insert after.
        $insertAfterLine = [];
        foreach ($afterOrdinals as $ord) {
            $ord = (int)$ord;
            if ($ord >= 1 && $ord <= count($endLines)) {
                $insertAfterLine[$endLines[$ord - 1]] = true;
            }
        }

        $deadCodePool = [
            '$__unused = null;',
            '$__dead = trim(" ");',
            '$__flag = false;',
            'if (false) { $__never = 1; }',
            '$__tmp = array_keys([]);',
        ];

        $outLines = [];
        $inserted = 0;
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;

            $fragmentLine = $idx + 1;
            if (isset($insertAfterLine[$fragmentLine]) && $inserted < $count) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $deadCode = $rng->pick($deadCodePool);
                $outLines[] = $indent . $deadCode;
                $outMap[] = -1;
                $inserted++;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
