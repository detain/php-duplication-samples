<?php

declare(strict_types=1);

namespace Gen\Transforms\Nz;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class Logging implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'NZ-01';
    }

    public function name(): string
    {
        return 'logging';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $entryMsg = $params['entry'] ?? 'method_entry';
        $exitMsg = $params['exit'] ?? 'method_exit';
        $call = $params['call'] ?? 'error_log';
        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $outLines = [];
        $outMap = [];

        preg_match('/^(\s*)/', $in->lines[0] ?? '', $m);
        $indent = $m[1];

        $outLines[] = $indent . $call . "('" . addslashes($entryMsg) . "');";
        $outMap[] = -1;

        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1;
            if ($fragmentLine === $endLines[count($endLines) - 1]) {
                $outLines[] = $indent . $call . "('" . addslashes($exitMsg) . "');";
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
