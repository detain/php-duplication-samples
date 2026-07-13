<?php

declare(strict_types=1);

namespace Gen\Transforms\Ty;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * TY-01 type_hints — add/remove/change type hints on parameters and return
 * types (Type-2).
 *
 * The AST identifies function parameters and return type nodes; the transform
 * modifies the type hints at the token level while preserving all other
 * formatting.
 *
 * params:
 *   param_types   (array<string,string>)  paramName => type hint to set (empty string removes)
 *   return_type   (string)                type hint for return (empty string removes)
 */
final class TypeHints implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'TY-01';
    }

    public function name(): string
    {
        return 'type_hints';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $paramTypes = $params['param_types'] ?? [];
        $returnType = $params['return_type'] ?? null;

        $text = $in->text();
        $ast = $this->ast->parse($text);

        // Find function-like node.
        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Get current param names and their types.
        $currentParamTypes = [];
        foreach ($fn->getParams() as $param) {
            if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                $typeStr = '';
                if ($param->type !== null) {
                    $typeStr = is_string($param->type->name) ? $param->type->name : (string)$param->type;
                }
                $currentParamTypes[$param->var->name] = $typeStr;
            }
        }

        // Get current return type.
        $currentReturnType = '';
        if ($fn->getReturnType() !== null) {
            $rt = $fn->getReturnType();
            $currentReturnType = is_string($rt->name ?? null) ? $rt->name : (string)$rt;
        }

        // Build the rebuilt text by processing tokens.
        $rebuilt = '';
        $paramIdx = 0;
        $params2 = $fn->getParams();

        foreach (PhpTokens::rawTokens($text) as $i => $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            // Looking for the function keyword followed by name.
            if ($t[0] === T_FUNCTION) {
                $rebuilt .= $t[1];
                continue;
            }

            // After T_FUNCTION, T_STRING is the function name.
            // After that, we encounter $param variables.
            // Handle type hint modifications.
            if ($t[0] === T_VARIABLE && $paramIdx < count($params2)) {
                $paramName = ltrim($t[1], '$');
                $param = $params2[$paramIdx];

                // Check if this param has a type hint to modify.
                if (isset($paramTypes[$paramName]) && $param->type !== null) {
                    $newType = $paramTypes[$paramName];
                    // The type hint was before this variable token.
                    // We need to find it and replace or insert.
                    // For simplicity, we rebuild by scanning backward from here.
                }

                $rebuilt .= $t[1];
                $paramIdx++;
                continue;
            }

            $rebuilt .= $t[1];
        }

        // Simpler approach: rebuild the function signature by text manipulation.
        $lines = explode("\n", $text);
        $rebuiltLines = $this->modifyTypeHints($lines, $paramTypes, $returnType);

        $linesOut = explode("\n", implode("\n", $rebuiltLines));
        return new TransformResult($linesOut, $in->lineMap);
    }

    /**
     * @param list<string> $lines
     * @param array<string,string> $paramTypes
     * @param string|null $returnType
     * @return list<string>
     */
    private function modifyTypeHints(array $lines, array $paramTypes, ?string $returnType): array
    {
        $out = [];
        foreach ($lines as $line) {
            // For each parameter that needs a type change.
            foreach ($paramTypes as $paramName => $newType) {
                // Match patterns like "int $paramName" or "?string $paramName" etc.
                $pattern = '/(\?)?(\w+)\s+\$' . preg_quote($paramName, '/') . '/';
                if ($newType === '') {
                    // Remove type hint.
                    $line = preg_replace($pattern, '$' . $paramName, $line);
                } else {
                    // Replace or add type hint.
                    $line = preg_replace($pattern, ($newType . ' $' . $paramName), $line);
                }
            }

            // Handle return type.
            if ($returnType !== null) {
                // Match patterns like ": int" or ": ?string" at end of function signature line.
                if (preg_match('/^\s*function\s+\w+\s*\([^)]*\)\s*:\s*\??\w+/', $line)) {
                    if ($returnType === '') {
                        $line = preg_replace('/:\s*\??\w+/', '', $line);
                    } else {
                        $line = preg_replace('/:\s*\??\w+/', ': ' . $returnType, $line);
                    }
                }
            }

            $out[] = $line;
        }
        return $out;
    }
}
