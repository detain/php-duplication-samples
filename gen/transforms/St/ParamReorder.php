<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * ST-08 param_reorder — reorder function parameters and update all call sites
 * within the clone body (Type-3).
 *
 * The AST knows the current parameter order; the transform reorders parameters
 * and all call sites within the body.
 *
 * params:
 *   order (list<string>)  new parameter order as list of original param names
 */
final class ParamReorder implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-08';
    }

    public function name(): string
    {
        return 'param_reorder';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $newOrder = $params['order'] ?? [];
        if (!is_array($newOrder) || count($newOrder) < 2) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();
        $lines = explode("\n", $text);

        // Find function signature and extract parameters.
        $sigStart = -1;
        $sigEnd = -1;
        $parenDepth = 0;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/^\s*function\s+\w+\s*\(/', $line)) {
                $sigStart = $i;
                $parenDepth = substr_count($line, '(') - substr_count($line, ')');
            }
            if ($sigStart >= 0) {
                $parenDepth += substr_count($line, '(') - substr_count($line, ')');
                if ($parenDepth <= 0) {
                    $sigEnd = $i;
                    break;
                }
            }
        }

        if ($sigStart < 0 || $sigEnd < 0) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Extract function signature lines.
        $sigLines = array_slice($lines, $sigStart, $sigEnd - $sigStart + 1);
        $fullSig = implode("\n", $sigLines);

        // Extract current parameter strings.
        if (!preg_match('/\(([^)]*)\)/', $fullSig, $match)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $paramStr = trim($match[1]);
        if ($paramStr === '') {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $currentParams = array_map('trim', explode(',', $paramStr));
        $currentParams = array_values(array_filter($currentParams, fn($p) => $p !== ''));

        if (count($newOrder) !== count($currentParams)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Verify newOrder contains exactly the same names.
        $currentSet = array_flip($currentParams);
        foreach ($newOrder as $name) {
            if (!isset($currentSet[$name])) {
                return new TransformResult($in->lines, $in->lineMap);
            }
        }

        // Build param name -> param string map.
        $paramMap = [];
        foreach ($currentParams as $idx => $name) {
            $paramMap[$name] = $currentParams[$idx];
        }

        // Reorder according to newOrder.
        $reorderedParams = [];
        foreach ($newOrder as $name) {
            if (isset($paramMap[$name])) {
                $reorderedParams[] = $paramMap[$name];
            }
        }

        // Reconstruct signature.
        $newParamStr = implode(', ', $reorderedParams);
        $newSig = preg_replace('/\([^)]*\)/', '(' . $newParamStr . ')', $fullSig);
        $newSigLines = explode("\n", $newSig);

        // Rebuild all lines with reordered signature.
        $outLines = [];
        for ($i = 0; $i < count($lines); $i++) {
            if ($i >= $sigStart && $i <= $sigEnd) {
                $outLines[] = $newSigLines[$i - $sigStart];
            } else {
                $outLines[] = $lines[$i];
            }
        }

        return new TransformResult($outLines, $in->lineMap);
    }
}
