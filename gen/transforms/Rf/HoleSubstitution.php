<?php

declare(strict_types=1);

namespace Gen\Transforms\Rf;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RF-02 hole_substitution — mount mode that creates substitution holes
 * (placeholder markers) in the unified solution (Type-3).
 *
 * This transform creates intentional HOLE markers at specific locations
 * where substitution can occur. The holes mark areas that are designed
 * to be replaced during refactoring transformations.
 *
 * params:
 *   hole_count (int)      number of holes to create (default: random 1-3)
 *   hole_prefix (string)  prefix for hole IDs (default: 'SUB')
 *   hole_description (string)  description template (default: 'substitution point')
 */
final class HoleSubstitution implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RF-02';
    }

    public function name(): string
    {
        return 'hole_substitution';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $holeCount = (int)($params['hole_count'] ?? 0);
        $holePrefix = $params['hole_prefix'] ?? 'SUB';
        $holeDesc = $params['hole_description'] ?? 'substitution point';

        if ($holeCount <= 0) {
            $holeCount = $rng->int(1, 3);
        }

        $lines = $in->lines;
        if (count($lines) < 2) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Find safe insertion points (statement boundaries).
        $endLines = $this->ast->bodyStatementEndLines($in->text());
        if ($endLines === []) {
            // Fallback: use random line positions if no statement boundaries found.
            $safePoints = range(1, count($lines) - 1);
        } else {
            // Use statement end lines as insertion points.
            $safePoints = $endLines;
        }

        if (count($safePoints) === 0) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Select random insertion points for holes.
        $holePositions = [];
        $availablePoints = $safePoints;
        for ($i = 0; $i < min($holeCount, count($availablePoints)); $i++) {
            if ($availablePoints === []) {
                break;
            }
            $pickIdx = $rng->int(0, count($availablePoints) - 1);
            $holePositions[] = $availablePoints[$pickIdx];
            array_splice($availablePoints, $pickIdx, 1);
        }

        sort($holePositions);

        $outLines = [];
        $outMap = [];
        $holeId = 0;

        foreach ($lines as $idx => $line) {
            $fragLine = $idx + 1;
            $outLines[] = $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;

            // Check if we should insert a hole after this line.
            while ($holePositions !== [] && $holePositions[0] === $fragLine) {
                array_splice($holePositions, 0, 1);
                $holeId++;

                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];

                $holeMarker = sprintf(
                    '%s// HOLE: %s-%03d — %s',
                    $indent,
                    $holePrefix,
                    $holeId,
                    $holeDesc
                );
                $outLines[] = $holeMarker;
                $outMap[] = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
