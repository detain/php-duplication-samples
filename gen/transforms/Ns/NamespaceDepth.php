<?php

declare(strict_types=1);

namespace Gen\Transforms\Ns;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * NS-02 namespace_depth — change the namespace hierarchy (Type-2).
 *
 * This transform moves a class from one namespace hierarchy to another.
 * For example: Acme\Foo\Bar -> Acme\Baz\Bar
 *
 * params:
 *   from_namespace (string)  current namespace prefix
 *   to_namespace (string)    new namespace prefix
 */
final class NamespaceDepth implements Transform
{
    public function __construct()
    {
    }

    public function code(): string
    {
        return 'NS-02';
    }

    public function name(): string
    {
        return 'namespace_depth';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $fromNs = $params['from_namespace'] ?? '';
        $toNs = $params['to_namespace'] ?? '';

        if ($fromNs === '' || $toNs === '' || $fromNs === $toNs) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();
        $lines = explode("\n", $text);

        $outLines = [];
        foreach ($lines as $line) {
            // Replace namespace declaration.
            if (preg_match('/^namespace\s+' . preg_quote($fromNs, '/') . '([\\;])/', $line)) {
                $line = preg_replace('/^namespace\s+' . preg_quote($fromNs, '/') . '/', 'namespace ' . $toNs, $line);
            }

            // Replace use statements.
            if (preg_match('/^use\s+' . preg_quote($fromNs, '/') . '/', $line)) {
                $line = preg_replace('/^use\s+' . preg_quote($fromNs, '/') . '/', 'use ' . $toNs, $line);
            }

            // Replace fully-qualified references to the old namespace.
            $line = str_replace('\\' . $fromNs . '\\', '\\' . $toNs . '\\', $line);

            $outLines[] = $line;
        }

        return new TransformResult($outLines, $in->lineMap);
    }
}
