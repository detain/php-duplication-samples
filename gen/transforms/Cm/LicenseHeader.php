<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-09 license_header — license banner before region.
 *
 * Adds a license header block before the cloned symbol.
 * Token-stream altering (Type-2 after removal).
 *
 * params:
 *   license (string) license type text (default 'MIT License')
 */
final class LicenseHeader implements Transform
{
    public function code(): string
    {
        return 'CM-09';
    }

    public function name(): string
    {
        return 'license_header';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $license = (string)($params['license'] ?? 'MIT License');

        // Find the indentation of the first non-blank payload line.
        $indent = '';
        foreach ($in->lines as $line) {
            if (trim($line) !== '') {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                break;
            }
        }

        $header = [
            $indent . '/*',
            $indent . ' * Copyright 2024 All rights reserved.',
            $indent . ' * ' . $license,
            $indent . ' */',
            $indent . '',
        ];

        $outLines = array_merge($header, $in->lines);
        $outMap   = array_merge(array_fill(0, count($header), -1), $in->lineMap);

        return new TransformResult($outLines, $outMap);
    }
}
