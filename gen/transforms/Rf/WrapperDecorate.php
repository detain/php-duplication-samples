<?php

declare(strict_types=1);

namespace Gen\Transforms\Rf;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RF-03 wrapper_decorate — wrap logic in decorator pattern (Type-3).
 *
 * This transform wraps the clone's body with decorator pattern scaffolding,
 * creating a decorator class that wraps the original logic. The original
 * body becomes a method inside the decorator.
 *
 * params:
 *   decorator_name (string)  name for decorator class (default: generate from context)
 *   method_name (string)     name for wrapped method (default: 'execute')
     */
final class WrapperDecorate implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RF-03';
    }

    public function name(): string
    {
        return 'wrapper_decorate';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $decoratorName = $params['decorator_name'] ?? null;
        $methodName = $params['method_name'] ?? 'execute';
        $lines = $in->lines;
        if ($lines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Generate names if not provided.
        if ($decoratorName === null) {
            $pool = ['ProcessingDecorator', 'ValidationDecorator', 'LoggingDecorator', 'CacheDecorator'];
            $decoratorName = $rng->pick($pool);
        }

        $outLines = [];
        $outMap = [];

        // Class declaration line.
        $outLines[] = 'class ' . $decoratorName . ' {';
        $outMap[] = -1;

        // Constructor stub.
        $outLines[] = '    private $wrapped;';
        $outMap[] = -1;
        $outLines[] = '    public function __construct($wrapped) {';
        $outMap[] = -1;
        $outLines[] = '        $this->wrapped = $wrapped;';
        $outMap[] = -1;
        $outLines[] = '    }';
        $outMap[] = -1;

        // Wrapped method containing the original logic.
        $outLines[] = '    public function ' . $methodName . '() {';
        $outMap[] = -1;

        // Add original lines with increased indent.
        foreach ($lines as $idx => $line) {
            $outLines[] = '        ' . $line;
            $outMap[] = $in->lineMap[$idx] ?? -1;
        }

        // Close wrapped method.
        $outLines[] = '    }';
        $outMap[] = -1;

        // Add decorator-specific enhancement placeholder.
        $outLines[] = '    // DECORATOR: additional behavior can be added here';
        $outMap[] = -1;

        // Close class.
        $outLines[] = '}';
        $outMap[] = -1;

        return new TransformResult($outLines, $outMap);
    }
}
