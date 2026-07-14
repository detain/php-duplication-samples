<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class NoiseWrapper implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-08';
    }

    public function name(): string
    {
        return 'noise_wrapper';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'noop_wrapper';
        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $insertAfterLine = $endLines[count($endLines) - 1];

        $noise = match ($style) {
            'noop_wrapper' => '$_id = uniqid(\'noise_\', true);',
            'timestamp' => '$_ts = time();',
            'counter' => 'static $_counter = 0; $_counter++;',
            'memory' => '$_mem = memory_get_usage(false);',
            default => '$_noise = crc32(\'' . $rng->int(1000, 9999) . '\');',
        };

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $outLines[] = $indent . $noise;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
