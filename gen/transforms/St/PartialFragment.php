<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * ST-07 partial_fragment — only 40-60% of the region is shared (Type-3).
 *
 * The head and tail of the method differ; the shared fragment is a proper
 * sub-range. This is achieved by trimming lines from the start and/or end
 * of the payload region.
 *
 * params:
 *   head_trim (int)   lines to trim from the start (default: 0)
 *   tail_trim (int)   lines to trim from the end (default: 0)
 *
 * Both head_trim and tail_trim are constrained to respect PHP structural
 * boundaries. head_trim will never trim through a method's opening { brace
 * or remove the function signature, as that would leave orphaned statements
 * at class level. tail_trim will never trim through a method's closing }
 * brace or a class's closing } brace. The safe trim distances are calculated
 * based on the position of structural elements (opening/closing braces, first
 * and last statements).
 */
final class PartialFragment implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-07';
    }

    public function name(): string
    {
        return 'partial_fragment';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $headTrim = (int)($params['head_trim'] ?? 0);
        $tailTrim = (int)($params['tail_trim'] ?? 0);

        if ($headTrim < 0 || $tailTrim < 0) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $totalLines = count($in->lines);
        $maxTrim = (int)($totalLines * 0.4); // Don't trim more than 40% total.
        $headTrim = min($headTrim, $maxTrim);

        // Constrain head_trim to structural boundaries.
        if ($headTrim > 0) {
            $headTrim = $this->constrainHeadTrimToStructuralBoundary($in->text(), $headTrim);
        }

        // Constrain tail_trim to structural boundaries.
        if ($tailTrim > 0) {
            $tailTrim = $this->constrainTailTrimToStructuralBoundary($in->text(), $tailTrim);
        }
        $tailTrim = min($tailTrim, $maxTrim);

        $outLines = array_slice($in->lines, $headTrim, $totalLines - $headTrim - $tailTrim);
        $outMap = array_slice($in->lineMap, $headTrim, $totalLines - $headTrim - $tailTrim);

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Constrain head_trim to respect PHP structural boundaries.
     *
     * Uses TOKEN analysis (not just AST) to find the exact column position of the
     * function body's opening { brace. This correctly handles same-line cases like:
     *   public function foo() { $x = 1; return $x; }
     * where the function signature, {, and first statement are all on line 1.
     *
     * The safe trim distance in lines is the number of complete lines BEFORE the
     * opening { brace. If { is on the same line as the function signature, there
     * are 0 lines before it, so no head trim is safe.
     *
     * @return int The constrained head_trim value
     */
    private function constrainHeadTrimToStructuralBoundary(string $fragment, int $headTrim): int
    {
        $parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();
        $wrapped = '<?php class GenWrap { ' . $fragment . ' }';
        $ast = $parser->parse($wrapped);
        if ($ast === null || $ast === []) {
            return 0;
        }

        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return 0;
        }

        // If function has no statements (abstract/interface), no trim safe.
        $stmts = $fn->getStmts();
        if ($stmts === [] || $stmts === null) {
            return 0;
        }

        $functionStartLine = $fn->getStartLine();

        // Find the { using lexer tokens - track parenthesis balance to skip
        // any { in default parameter values.
        // php-parser v5.x: Lexer\Emulative takes a PhpVersion object, not a config array.
        $phpVer = \PhpParser\PhpVersion::fromString('8.1');
        $lexer = new \PhpParser\Lexer\Emulative($phpVer);
        $tokens = $lexer->tokenize($wrapped);

        $braceLine = null;
        $parenBalance = 0;
        $foundFunction = false;

        foreach ($tokens as $token) {
            // php-parser v5.x: tokens are PhpParser\Token objects, not arrays.
            $tokenType = $token->id;
            $tokenText = $token->text;
            $tokenLine = $token->line;

            // Wait for the actual 'function' keyword — not visibility or static modifiers.
            // "public function foo()" has 'public' first, then 'function', then parens.
            if (!$foundFunction) {
                if ($tokenText === 'function') {
                    $foundFunction = true;
                }
                continue;
            }

            // Track parentheses to know when we're inside the parameter list.
            if ($tokenText === '(') {
                $parenBalance++;
            } elseif ($tokenText === ')') {
                $parenBalance--;
            } elseif ($tokenText === '{' && $parenBalance === 0) {
                // Found the function body's opening brace.
                $braceLine = $tokenLine;
                break;
            }
        }

        // If we couldn't find the brace, don't risk trimming.
        if ($braceLine === null) {
            return 0;
        }

        // Safe distance in lines = number of complete lines before the { brace.
        // The { line itself is not safe to trim through.
        $safeDistance = $braceLine - $functionStartLine;

        if ($safeDistance <= 0) {
            // { is on same line as function start — no line-level trim safe.
            return 0;
        }

        // Subtract 1 to exclude the { line itself from trim range.
        return min($headTrim, $safeDistance - 1);
    }

    /**
     * Constrain tail_trim to respect PHP structural boundaries.
     *
     * The safe trim distance is the number of lines between the last statement's
     * end line and the function body's closing brace line. Trimming beyond this
     * would cut through the function's closing } brace, causing PHP syntax errors.
     *
     * @return int The constrained tail_trim value
     */
    private function constrainTailTrimToStructuralBoundary(string $fragment, int $tailTrim): int
    {
        // bodyStatementEndLines returns empty if no function or no statements.
        $stmtEndLines = $this->ast->bodyStatementEndLines($fragment);
        if ($stmtEndLines === []) {
            // No statements found or no function - don't trim at all.
            return 0;
        }

        $lastStmtEndLine = max($stmtEndLines);

        // Find the function body's end line (where the } appears).
        $parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();
        $wrapped = '<?php class GenWrap { ' . $fragment . ' }';
        $ast = $parser->parse($wrapped);
        if ($ast === null || $ast === []) {
            return 0;
        }

        $finder = new NodeFinder();
        $fn = $finder->findFirstInstanceOf($ast, Node\FunctionLike::class);
        if ($fn === null) {
            return 0;
        }

        $functionEndLine = $fn->getEndLine();

        // Safe distance = lines between last statement and function body's }
        // Example: last statement ends at line 20, function ends at line 22
        // safeDistance = 22 - 20 = 2 (line 21 is blank, line 22 is the })
        // We subtract 1 because line 22 (the }) is never safe to trim.
        $safeDistance = $functionEndLine - $lastStmtEndLine;

        if ($safeDistance <= 1) {
            // Only the } is in range, or statements go to the } — no trim safe.
            return 0;
        }

        // Subtract 1 to exclude the } line itself from trim range.
        return min($tailTrim, $safeDistance - 1);
    }
}
