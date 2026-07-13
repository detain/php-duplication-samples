<?php

declare(strict_types=1);

namespace Gen\Transforms\Rn;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RN-04 class_rename — simple rename of the class name
 * by finding 'class' keyword and renaming the next identifier (Type-2).
 *
 * params:
 *   rename (string)  new class name
 */
final class ClassRename implements Transform
{
    public function code(): string
    {
        return 'RN-04';
    }

    public function name(): string
    {
        return 'class_rename';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $rename = $params['rename'] ?? '';
        if ($rename === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $rename)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Find T_CLASS and rename the identifier that follows.
        $rebuilt = '';
        $prevToken = null;
        $foundClass = false;
        $oldName = null;

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                $prevToken = $t;
                continue;
            }

            // Detect T_CLASS.
            if ($t[0] === T_CLASS) {
                $foundClass = true;
                $rebuilt .= $t[1];
                $prevToken = $t;
                continue;
            }

            // After T_CLASS, the next T_STRING is the class name.
            if ($foundClass && $t[0] === T_STRING) {
                $oldName = $t[1];
                $rebuilt .= $rename;
                $foundClass = false;
                $prevToken = $t;
                continue;
            }

            $rebuilt .= $t[1];
            $prevToken = $t;
        }

        // If no class was found, return identity.
        if ($oldName === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // If name didn't change, return identity.
        if ($oldName === $rename) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
