<?php

declare(strict_types=1);

namespace Gen\Transforms\Uq;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class PerCurrencyRounding implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'UQ-01';
    }

    public function name(): string
    {
        return 'per_currency_rounding';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $currency = $params['currency'] ?? 'USD';
        $precision = (int)($params['precision'] ?? 2);

        $text = $in->text();
        $endLines = $this->ast->bodyStatementEndLines($text);

        if ($endLines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $insertAfterLine = $endLines[count($endLines) - 1];

        $outLines = [];
        $outMap = [];
        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
            $fragmentLine = $idx + 1;
            if ($fragmentLine === $insertAfterLine) {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                $uniqueCode = $indent . '$_rounded = round($_value, ' . $precision . ');' . "\n";
                $uniqueCode .= $indent . '// unique_per_currency_' . strtolower($currency) . '_step';
                $outLines[] = $uniqueCode;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
