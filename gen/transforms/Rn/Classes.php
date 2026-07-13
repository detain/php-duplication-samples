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
 * RN-04 classes — rename the class name AND all `new ClassName` instantiations
 * within the clone body (Type-2).
 *
 * The AST locates the class declaration and all New nodes that instantiate it;
 * the token-level rename is then applied consistently so the clone is
 * structurally intact but semantically renamed.
 *
 * params:
 *   rename (string)  new class name
 */
final class Classes implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RN-04';
    }

    public function name(): string
    {
        return 'classes';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $rename = $params['rename'] ?? '';
        if ($rename === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $rename)) {
            throw new \RuntimeException("RN-04: invalid rename target '{$rename}'");
        }

        $text = $in->text();
        $ast = $this->ast->parse($text);

        // Find the class in the wrapped AST.
        $finder = new NodeFinder();
        $class = $finder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);
        if ($class === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $oldName = (string)$class->name->name;
        if ($oldName === $rename) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Collect all class-name parts in 'new OldName(...)' nodes.
        $newNodes = [];
        foreach ($finder->findInstanceOf($ast, Node\Expr\New_::class) as $new) {
            /** @var Node\Expr\New_ $new */
            if ($new->class instanceof Node\Name && $new->class->isUnqualified()) {
                $newNodes[$new->class->toLowerString()] = true;
            }
        }

        $rebuilt = '';
        $prevToken = null;
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                $prevToken = $t;
                continue;
            }
            // Rename 'class' keyword.
            if ($t[0] === T_CLASS) {
                $rebuilt .= $t[1];
                $prevToken = $t;
                continue;
            }
            // Rename class name after T_CLASS.
            if ($prevToken !== null && $prevToken[0] === T_CLASS && $t[0] === T_STRING && $t[1] === $oldName) {
                $rebuilt .= $rename;
                $prevToken = $t;
                continue;
            }
            // Rename 'new ClassName(...)' instantiations.
            if ($t[0] === T_STRING && isset($newNodes[strtolower($t[1])]) && $t[1] === $oldName) {
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
