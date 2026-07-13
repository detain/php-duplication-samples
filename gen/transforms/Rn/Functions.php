<?php

declare(strict_types=1);

namespace Gen\Transforms\Rn;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * RN-03 functions — rename the cloned function/method name AND all call sites
 * within the clone body (Type-2).
 *
 * The AST locates the function/method declaration and all Name nodes that are
 * function-call callees within the body; the token-level rename is then applied
 * consistently so the clone is structurally intact but semantically renamed.
 *
 * params:
 *   rename (string)  new function/method name (no $)
 */
final class Functions implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RN-03';
    }

    public function name(): string
    {
        return 'functions';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $rename = $params['rename'] ?? '';
        if ($rename === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $rename)) {
            throw new \RuntimeException("RN-03: invalid rename target '{$rename}'");
        }

        $text = $in->text();
        $ast = $this->ast->parse($text);

        // Find the function-like node.
        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Get current function name.
        if ($fn instanceof Node\Stmt\Function_) {
            $oldName = (string)$fn->name->name;
        } elseif ($fn instanceof Node\Stmt\ClassMethod) {
            $oldName = (string)$fn->name->name;
        } else {
            return new TransformResult($in->lines, $in->lineMap);
        }

        if ($oldName === $rename) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Collect all Name nodes that are direct callee in FunctionCall nodes
        // within the body (not use statements, not namespace references).
        $callNames = [];
        foreach ($finder->findInstanceOf($ast, Node\Expr\FuncCall::class) as $call) {
            /** @var Node\Expr\FuncCall $call */
            if ($call->name instanceof Node\Name && $call->name->isUnqualified()) {
                $callNames[$call->name->toLowerString()] = true;
            }
        }

        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            // Rename function definition.
            if ($t[0] === T_FUNCTION) {
                $rebuilt .= $t[1];
                continue;
            }
            // Rename the function name token (after T_FUNCTION).
            if (isset($prevToken) && $prevToken[0] === T_FUNCTION && $t[0] === T_STRING && $t[1] === $oldName) {
                $rebuilt .= $rename;
                $prevToken = $t;
                continue;
            }
            // Rename function call sites.
            if ($t[0] === T_STRING && isset($callNames[strtolower($t[1])]) && $t[1] === $oldName) {
                $rebuilt .= $rename;
                $prevToken = $t;
                continue;
            }
            $rebuilt .= $t[1];
            $prevToken = $t;
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
