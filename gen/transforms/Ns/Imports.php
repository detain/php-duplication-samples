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
 * NS-01 imports — switch between use statements, FQCN, and aliases (Type-2).
 *
 * This transform changes how a class is referenced: either as a fully-qualified
 * name (no use statement), or with a use statement and short name, or with
 * an alias. The payload must contain at least one class instantiation or
 * type reference.
 *
 * params:
 *   mode (string)        'use_short', 'use_alias', 'fqcn' (default: 'use_short')
 *   target_class (string)  the fully-qualified class name to manage
 *   alias (string)      alias name when mode='use_alias'
 */
final class Imports implements Transform
{
    public function __construct()
    {
    }

    public function code(): string
    {
        return 'NS-01';
    }

    public function name(): string
    {
        return 'imports';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $mode = $params['mode'] ?? 'use_short';
        $targetClass = $params['target_class'] ?? '';
        $alias = $params['alias'] ?? '';

        if ($targetClass === '') {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $shortName = $alias !== '' ? $alias : self::shortName($targetClass);
        $text = $in->text();
        $lines = explode("\n", $text);

        $outLines = [];
        $hasUseStatement = false;
        $useLineIdx = -1;
        $hasFullyQualified = false;

        // Analyze current state.
        foreach ($lines as $idx => $line) {
            if (preg_match('/^use\s+' . preg_quote($targetClass, '/') . '\s*;/', $line)) {
                $hasUseStatement = true;
                $useLineIdx = $idx;
            }
            if (strpos($line, '\\' . $shortName) !== false || strpos($line, $targetClass) !== false) {
                $hasFullyQualified = true;
            }
        }

        foreach ($lines as $idx => $line) {
            $modified = $line;

            if ($mode === 'use_short') {
                // Add use statement if not present, replace FQCN with short name.
                if (!$hasUseStatement) {
                    // Insert use statement after the first <?php or namespace line.
                    if ($idx === 0 && strpos($line, '<?php') !== false) {
                        $modified = $line . "\nuse " . $targetClass . ';';
                    } elseif (strpos($line, 'namespace ') !== false) {
                        $modified = $line . "\nuse " . $targetClass . ';';
                    }
                }
                // Replace FQCN with short name.
                $modified = str_replace('\\' . $shortName, $shortName, $modified);
                $modified = str_replace($targetClass, $shortName, $modified);
            } elseif ($mode === 'use_alias') {
                $aliasName = $alias !== '' ? $alias : 'Aliased' . $shortName;
                if (!$hasUseStatement) {
                    if (strpos($line, '<?php') !== false) {
                        $modified = $line . "\nuse " . $targetClass . ' as ' . $aliasName . ';';
                    } elseif (strpos($line, 'namespace ') !== false) {
                        $modified = $line . "\nuse " . $targetClass . ' as ' . $aliasName . ';';
                    }
                }
                $modified = str_replace('\\' . $aliasName, $aliasName, $modified);
                $modified = str_replace($targetClass, $aliasName, $modified);
            } elseif ($mode === 'fqcn') {
                // Remove use statement, replace short name with FQCN.
                if ($hasUseStatement && strpos($line, 'use ') === 0) {
                    // Skip this line (remove use statement).
                    continue;
                }
                $modified = str_replace('\\' . $shortName, $targetClass, $modified);
                $modified = str_replace($shortName, $targetClass, $modified);
            }

            $outLines[] = $modified;
        }

        return new TransformResult($outLines, $in->lineMap);
    }

    private static function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}
