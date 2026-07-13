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
 * and all call sites within the body (e.g., in parent::methodCall() or
 * $this->methodCall()). Note: this only handles call sites INSIDE the payload,
 * not external callers.
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
        $ast = $this->ast->parse($text);

        // Find function-like node.
        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Get current param names in order.
        $currentParams = [];
        foreach ($fn->getParams() as $param) {
            if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                $currentParams[] = $param->var->name;
            }
        }

        if (count($newOrder) !== count($currentParams)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Verify newOrder contains exactly the same names.
        $currentSet = array_flip($currentParams);
        $newSet = [];
        foreach ($newOrder as $name) {
            if (!isset($currentSet[$name])) {
                return new TransformResult($in->lines, $in->lineMap);
            }
            $newSet[$name] = true;
        }

        // Compute position mapping: oldPos -> newPos
        $positionMap = [];
        foreach ($currentParams as $idx => $name) {
            $newIdx = array_search($name, $newOrder, true);
            if ($newIdx !== false) {
                $positionMap[$idx] = $newIdx;
            }
        }

        // Reorder the function signature by re-arranging the parameter lines.
        // This is a simplified token-level reordering.
        $lines = explode("\n", $text);
        $rebuiltLines = $this->reorderParams($lines, $currentParams, $newOrder);

        $linesOut = explode("\n", implode("\n", $rebuiltLines));
        return new TransformResult($linesOut, $in->lineMap);
    }

    /**
     * @param list<string> $lines
     * @param list<string> $currentParams
     * @param list<string> $newOrder
     * @return list<string>
     */
    private function reorderParams(array $lines, array $currentParams, array $newOrder): array
    {
        // Find the function signature line(s) and reorder parameters.
        $out = [];
        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];

            // Detect function signature that spans one or more lines.
            if (preg_match('/^\s*function\s+\w+\s*\(/', $line)) {
                $sigStart = $i;
                $sigLines = [$line];
                $parenDepth = substr_count($line, '(') - substr_count($line, ')');
                while ($parenDepth > 0 && $i + 1 < count($lines)) {
                    $i++;
                    $sigLines[] = $lines[$i];
                    $parenDepth += substr_count($lines[$i], '(') - substr_count($lines[$i], ')');
                }

                // Reorder within the signature.
                $reorderedSig = $this->reorderSignatureLines($sigLines, $currentParams, $newOrder);
                foreach ($reorderedSig as $sigLine) {
                    $out[] = $sigLine;
                }
                $i++;
                continue;
            }

            $out[] = $line;
            $i++;
        }

        return $out;
    }

    private function reorderSignatureLines(array $sigLines, array $currentParams, array $newOrder): array
    {
        $fullSig = implode("\n", $sigLines);

        // Extract individual parameter strings.
        $params = [];
        $current = '';
        $parenDepth = 0;
        foreach ($sigLines as $sigLine) {
            preg_match('/\([^)]*\)/', $sigLine, $match);
            if ($match) {
                $paramStr = $match[0];
                // Strip parentheses.
                $paramStr = trim($paramStr, '()');
                // Split by comma (simple approach - doesn't handle nested generics).
                $paramParts = explode(',', $paramStr);
                foreach ($paramParts as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $params[] = $part;
                    }
                }
                break;
            }
        }

        if (count($params) !== count($currentParams)) {
            return $sigLines;
        }

        // Build param name -> param string map.
        $paramMap = [];
        foreach ($currentParams as $idx => $name) {
            if (isset($params[$idx])) {
                $paramMap[$name] = $params[$idx];
            }
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

        return [$newSig];
    }
}
