<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-03 insert_functional — insert a genuinely new small step (e.g., extra
 * trim, validate, normalize) inside the clone (Type-3).
 *
 * This inserts a functional statement that is syntactically valid and makes
 * sense in context, not just dead code. It represents a real code change.
 *
 * params:
 *   step (string)     the kind of functional step: 'trim', 'normalize', 'validate', 'cast'
 *   after (int)       1-based statement ordinal to insert after (default: random)
 */
final class InsertFunctional implements Transform
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
        return 'insert_functional';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $step = $params['step'] ?? $rng->pick(['trim', 'normalize', 'validate', 'cast', 'abs']);
        $endLines = $this->ast->bodyStatementEndLines($in->text());
        $afterOrd = (int)($params['after'] ?? $rng->int(1, count($endLines)));

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        if ($afterOrd < 1 || $afterOrd > count($endLines)) {
            $afterOrd = 1;
        }

        $insertAfterLine = $endLines[$afterOrd - 1];

        $stepCode = match ($step) {
            'trim' => '$__v = trim($__v ?? "");',
            'normalize' => '$__v = strtolower(trim($__v ?? ""));',
            'validate' => 'if ($__v !== null && $__v !== "") { /* ok */ }',
            'cast' => '$__v = (int)($__v ?? 0);',
            'abs' => '$__v = abs((int)($__v ?? 0));',
            default => '$__v = trim($__v ?? "");',
        };

        $outLines = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;

            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $outLines[] = $indent . $stepCode;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
