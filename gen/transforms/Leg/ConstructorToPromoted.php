<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

final class ConstructorToPromoted implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'LEG-08';
    }

    public function name(): string
    {
        return 'constructor_to_promoted';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $ast = $this->ast->parse($text);

        $finder = new NodeFinder();
        $constructors = $finder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        foreach ($constructors as $constructor) {
            if ($constructor->name->toLowerString() !== '__construct') {
                continue;
            }

            $assigns = $this->findPropertyAssignments($constructor);
            if ($assigns === []) {
                continue;
            }

            $rebuilt = $this->promoteProperties($text, $constructor, $assigns);
            $lines = explode("\n", $rebuilt);
            return new TransformResult($lines, $in->lineMap);
        }

        return new TransformResult($in->lines, $in->lineMap);
    }

    private function findPropertyAssignments(Node\Stmt\ClassMethod $constructor): array
    {
        $assigns = [];
        foreach ($constructor->getStmts() ?? [] as $stmt) {
            if ($stmt instanceof Node\Expression\Assign &&
                $stmt->expr instanceof Node\Expr\PropertyFetch &&
                $stmt->expr->name instanceof Node\Identifier) {

                $propName = $stmt->expr->name->toLowerString();
                if (preg_match('/^\$this->(\w+)$/', '$this->' . $stmt->expr->name->toString(), $m)) {
                    $propName = $m[1];
                }

                if ($stmt->var instanceof Node\Expr\PropertyFetch &&
                    $stmt->var->name instanceof Node\Identifier) {

                    $targetProp = $stmt->var->name->toString();
                    if (preg_match('/^\w+$/', $targetProp)) {
                        $assigns[$targetProp] = [
                            'param' => $stmt->expr->name->toString(),
                            'line' => $stmt->getLine(),
                        ];
                    }
                }
            }
        }
        return $assigns;
    }

    private function promoteProperties(string $text, Node\Stmt\ClassMethod $constructor, array $assigns): string
    {
        return $text;
    }
}
