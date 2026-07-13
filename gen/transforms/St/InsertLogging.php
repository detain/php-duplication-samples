<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-01 insert_logging — interleave logging/metrics lines into the clone (Type-3).
 *
 * The AST supplies the fragment-relative end lines of the body's top-level
 * statements; logging lines are inserted immediately after chosen statements
 * with the anchor line's indentation. Inserted lines are syntactically valid
 * (error_log(...)) and self-contained, so php -l stays clean.
 *
 * params:
 *   after   (list<int>)  1-based statement ORDINALS to insert after (default [1])
 *   message (string)     log message text
 *   call    (string)     'error_log' (default) — the logging idiom to emit
 */
final class InsertLogging implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-01';
    }

    public function name(): string
    {
        return 'insert_logging';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $afterOrdinals = $params['after'] ?? [1];
        $message = (string)($params['message'] ?? 'processing step');
        $call    = (string)($params['call'] ?? 'error_log');

        $endLines = $this->ast->bodyStatementEndLines($in->text()); // 1-based fragment lines
        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Map chosen statement ordinals -> the fragment line to insert after.
        $insertAfterLine = [];
        foreach ($afterOrdinals as $ord) {
            $ord = (int)$ord;
            if ($ord >= 1 && $ord <= count($endLines)) {
                $insertAfterLine[$endLines[$ord - 1]] = true;
            }
        }

        $outLines = [];
        $outMap   = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1; // 1-based
            if (isset($insertAfterLine[$fragmentLine])) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $outLines[] = $indent . $call . "('" . addslashes($message) . "');";
                $outMap[]   = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
