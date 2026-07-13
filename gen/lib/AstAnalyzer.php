<?php

declare(strict_types=1);

namespace Gen\Lib;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Thin nikic/php-parser wrapper used by the RN/LT/ST transforms.
 *
 * Design note (single-axis fidelity): these transforms use the AST only to
 * *decide* what to edit (which tokens are local variables, numeric literals, or
 * statement boundaries). The edit itself is then applied at the token/line
 * level so that formatting is preserved exactly and no second (whitespace) axis
 * is introduced. Pretty-printing the AST would reformat everything and violate
 * the minimal-pair principle (D5).
 *
 * A bare fragment is parsed with a leading "<?php " (no newline) so reported
 * line numbers align 1:1 with the fragment's own lines.
 */
final class AstAnalyzer
{
    /**
     * Parse a payload fragment. The fragment is a function/method definition and
     * may start with a visibility modifier (method-mount form), which is only
     * legal inside a class — so it is wrapped in a throwaway class. The wrapper
     * prefix carries no newline, so reported line numbers still align 1:1 with
     * the fragment's own lines.
     *
     * @return list<Node\Stmt>
     */
    public function parse(string $fragment): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse('<?php class GenWrap { ' . $fragment . ' }');
        return $ast ?? [];
    }

    /**
     * Names (without leading '$') of parameters declared by any function-like
     * node in the fragment.
     *
     * @return list<string>
     */
    public function paramNames(string $fragment): array
    {
        $ast = $this->parse($fragment);
        $finder = new NodeFinder();
        $names = [];
        foreach ($finder->findInstanceOf($ast, Node\FunctionLike::class) as $fn) {
            /** @var Node\FunctionLike $fn */
            foreach ($fn->getParams() as $param) {
                if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $names[$param->var->name] = true;
                }
            }
        }
        return array_keys($names);
    }

    /**
     * Local variable names (without '$'), i.e. every plain Variable use that is
     * not a parameter and not $this. Order is first-appearance.
     *
     * @return list<string>
     */
    public function localVariableNames(string $fragment): array
    {
        $ast = $this->parse($fragment);
        $finder = new NodeFinder();
        $params = array_flip($this->paramNames($fragment));
        $names = [];
        foreach ($finder->findInstanceOf($ast, Node\Expr\Variable::class) as $var) {
            /** @var Node\Expr\Variable $var */
            if (!is_string($var->name)) {
                continue; // variable-variable
            }
            if ($var->name === 'this' || isset($params[$var->name])) {
                continue;
            }
            $names[$var->name] = true;
        }
        return array_keys($names);
    }

    /**
     * Distinct numeric-literal source values appearing in the fragment.
     *
     * @return list<string>
     */
    public function numericLiterals(string $fragment): array
    {
        $ast = $this->parse($fragment);
        $finder = new NodeFinder();
        $vals = [];
        foreach ($finder->find($ast, static fn(Node $n): bool =>
            $n instanceof Node\Scalar\Int_ || $n instanceof Node\Scalar\Float_) as $n) {
            $raw = $n->getAttribute('rawValue');
            if (is_string($raw)) {
                $vals[$raw] = true;
            } else {
                $vals[(string)$n->value] = true;
            }
        }
        return array_keys($vals);
    }

    /**
     * 1-based (fragment-relative) end lines of the top-level statements inside
     * the first function-like body — the safe places to insert a statement.
     *
     * @return list<int>
     */
    public function bodyStatementEndLines(string $fragment): array
    {
        $ast = $this->parse($fragment);
        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return [];
        }
        $stmts = $fn->getStmts() ?? [];
        $lines = [];
        foreach ($stmts as $stmt) {
            $lines[] = $stmt->getEndLine();
        }
        return $lines;
    }
}
