<?php

declare(strict_types=1);

namespace Gen\Transforms\Ns;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * NS-01 namespace_inject — inject or remove use statements,
 * modify FQCN references (Type-2).
 *
 * This transform can add or remove use statements and change how
 * classes are referenced (fully-qualified vs short name).
 *
 * params:
 *   operation (string)       'add_use', 'remove_use', 'fqcn_to_short', 'short_to_fqcn'
 *   class_name (string)      fully-qualified class name
 *   alias (string)          optional alias name for use statements
 */
final class NamespaceInject implements Transform
{
    public function code(): string
    {
        return 'NS-01';
    }

    public function name(): string
    {
        return 'namespace_inject';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $operation = $params['operation'] ?? 'add_use';
        $className = $params['class_name'] ?? '';
        $alias = $params['alias'] ?? '';

        if ($className === '') {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $shortName = $this->shortName($className);
        $lines = $in->lines;
        $outLines = [];

        if ($operation === 'add_use') {
            // Add a use statement and replace FQCN with short name.
            $useLine = 'use ' . $className;
            if ($alias !== '') {
                $useLine .= ' as ' . $alias . ';';
            } else {
                $useLine .= ';';
            }

            $inserted = false;
            foreach ($lines as $idx => $line) {
                $outLines[] = $line;
                // Insert after namespace or opening php tag.
                if (!$inserted && (strpos($line, 'namespace ') !== false || strpos($line, '<?php') !== false)) {
                    // Check if there's already a use statement for this class.
                    $alreadyHasUse = false;
                    foreach ($lines as $checkLine) {
                        if (strpos($checkLine, 'use ' . $className) !== false) {
                            $alreadyHasUse = true;
                            break;
                        }
                    }
                    if (!$alreadyHasUse) {
                        $outLines[] = $useLine;
                        $inserted = true;
                    }
                }
            }

            // Replace FQCN with short name or alias.
            $replaceName = $alias !== '' ? $alias : $shortName;
            foreach ($outLines as $idx => $line) {
                $outLines[$idx] = str_replace('\\' . $className, $replaceName, $line);
                $outLines[$idx] = str_replace($className, $replaceName, $outLines[$idx]);
            }
        } elseif ($operation === 'remove_use') {
            // Remove use statement and replace short name with FQCN.
            foreach ($lines as $line) {
                if (strpos($line, 'use ' . $className) !== false) {
                    continue; // Skip this line (removing use statement).
                }
                // Replace short name with FQCN.
                $line = str_replace(' ' . $shortName, ' ' . $className, $line);
                $line = str_replace('(' . $shortName, '(' . $className, $line);
                $line = str_replace(',' . $shortName, ',' . $className, $line);
                $outLines[] = $line;
            }
        } elseif ($operation === 'fqcn_to_short') {
            // Keep use statement but replace FQCN with short name.
            $replaceName = $alias !== '' ? $alias : $shortName;
            foreach ($lines as $line) {
                $line = str_replace('\\' . $className, $replaceName, $line);
                $outLines[] = $line;
            }
        } elseif ($operation === 'short_to_fqcn') {
            // Remove use statement and replace short name with FQCN.
            foreach ($lines as $line) {
                if (strpos($line, 'use ' . $className) !== false) {
                    continue;
                }
                $line = str_replace(' ' . $shortName, ' ' . $className, $line);
                $line = str_replace('(' . $shortName, '(' . $className, $line);
                $line = str_replace(',' . $shortName, ',' . $className, $line);
                $outLines[] = $line;
            }
        } else {
            return new TransformResult($in->lines, $in->lineMap);
        }

        return new TransformResult($outLines, $in->lineMap);
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}
