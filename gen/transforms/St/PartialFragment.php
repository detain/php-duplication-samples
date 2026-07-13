<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-07 partial_fragment — only 40-60% of the region is shared (Type-3).
 *
 * The head and tail of the method differ; the shared fragment is a proper
 * sub-range. This is achieved by trimming lines from the start and/or end
 * of the payload region. The caller is responsible for ensuring the
 * resulting region still forms a valid syntactic unit.
 *
 * params:
 *   head_trim (int)   lines to trim from the start (default: 0)
 *   tail_trim (int)   lines to trim from the end (default: 0)
 *
 * NOTE: This transform intentionally produces a partial fragment that is NOT
 * a complete function. The SetBuilder treats this specially when rendering;
 * the fragment's line numbers in the ground truth will reflect the trimmed
 * region, but the syntax may be incomplete. See notes in expected.json.
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
        $tailTrim = min($tailTrim, $maxTrim);

        $outLines = array_slice($in->lines, $headTrim, $totalLines - $headTrim - $tailTrim);
        $outMap = array_slice($in->lineMap, $headTrim, $totalLines - $headTrim - $tailTrim);

        return new TransformResult($outLines, $outMap);
    }
}
