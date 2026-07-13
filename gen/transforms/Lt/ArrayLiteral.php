<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-03 array_literal — transform array contents by swapping, adding,
 * or removing elements (Type-2).
 *
 * This works on array() and [] syntax. Elements can be reordered,
 * replaced, or new elements can be injected.
 *
 * params:
 *   operation (string)      'swap', 'add', 'remove', 'replace'
 *   index1 (int)            first element index (0-based)
 *   index2 (int)            second element index (for swap)
 *   value (string)          value to add or replace with
 *   count (int)             number of elements to remove (for remove)
 */
final class ArrayLiteral implements Transform
{
    public function code(): string
    {
        return 'LT-03';
    }

    public function name(): string
    {
        return 'array_literal';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $operation = $params['operation'] ?? 'swap';
        $index1 = (int)($params['index1'] ?? 0);
        $index2 = (int)($params['index2'] ?? 1);
        $value = $params['value'] ?? 'null';
        $count = (int)($params['count'] ?? 1);

        $text = $in->text();
        $lines = explode("\n", $text);

        $outLines = [];
        foreach ($lines as $line) {
            if ($operation === 'swap') {
                $line = $this->swapElements($line, $index1, $index2);
            } elseif ($operation === 'add') {
                $line = $this->addElement($line, $index1, $value);
            } elseif ($operation === 'remove') {
                $line = $this->removeElement($line, $index1, $count);
            } elseif ($operation === 'replace') {
                $line = $this->replaceElement($line, $index1, $value);
            }
            $outLines[] = $line;
        }

        return new TransformResult($outLines, $in->lineMap);
    }

    private function swapElements(string $line, int $idx1, int $idx2): string
    {
        // Match array literal content.
        if (!preg_match('/(\[.*\]|\barray\(.*\))/', $line, $match)) {
            return $line;
        }

        $arrayStr = $match[0];
        $elements = $this->parseArrayElements($arrayStr);

        if (count($elements) <= max($idx1, $idx2)) {
            return $line;
        }

        // Swap elements.
        $tmp = $elements[$idx1];
        $elements[$idx1] = $elements[$idx2];
        $elements[$idx2] = $tmp;

        $newArray = $this->buildArrayLiteral($arrayStr, $elements);
        return str_replace($arrayStr, $newArray, $line);
    }

    private function addElement(string $line, int $index, string $value): string
    {
        if (!preg_match('/(\[.*\]|\barray\(.*\))/', $line, $match)) {
            return $line;
        }

        $arrayStr = $match[0];
        $elements = $this->parseArrayElements($arrayStr);

        $insertIdx = min($index, count($elements));
        array_splice($elements, $insertIdx, 0, [$value]);

        $newArray = $this->buildArrayLiteral($arrayStr, $elements);
        return str_replace($arrayStr, $newArray, $line);
    }

    private function removeElement(string $line, int $index, int $count): string
    {
        if (!preg_match('/(\[.*\]|\barray\(.*\))/', $line, $match)) {
            return $line;
        }

        $arrayStr = $match[0];
        $elements = $this->parseArrayElements($arrayStr);

        if ($index >= count($elements)) {
            return $line;
        }

        array_splice($elements, $index, max(1, $count));

        $newArray = $this->buildArrayLiteral($arrayStr, $elements);
        return str_replace($arrayStr, $newArray, $line);
    }

    private function replaceElement(string $line, int $index, string $value): string
    {
        if (!preg_match('/(\[.*\]|\barray\(.*\))/', $line, $match)) {
            return $line;
        }

        $arrayStr = $match[0];
        $elements = $this->parseArrayElements($arrayStr);

        if ($index >= count($elements)) {
            return $line;
        }

        $elements[$index] = $value;

        $newArray = $this->buildArrayLiteral($arrayStr, $elements);
        return str_replace($arrayStr, $newArray, $line);
    }

    /**
     * @return array<string>
     */
    private function parseArrayElements(string $arrayStr): array
    {
        $isShort = str_starts_with($arrayStr, '[');
        $open = $isShort ? '[' : 'array(';
        $close = $isShort ? ']' : ')';

        $content = substr($arrayStr, strlen($open), strlen($close) * -1);
        $elements = [];

        $depth = 0;
        $current = '';
        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];
            if ($char === '[' || $char === '(') {
                $depth++;
                $current .= $char;
            } elseif ($char === ']' || $char === ')') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $elements[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $elements[] = trim($current);
        }

        return $elements;
    }

    private function buildArrayLiteral(string $original, array $elements): string
    {
        $isShort = str_starts_with($original, '[');
        $open = $isShort ? '[' : 'array(';
        $close = $isShort ? ']' : ')';

        return $open . implode(', ', $elements) . $close;
    }
}
