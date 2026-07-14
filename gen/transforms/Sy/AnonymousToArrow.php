<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class AnonymousToArrow implements Transform
{
    public function code(): string
    {
        return 'SY-02';
    }

    public function name(): string
    {
        return 'anonymous_to_arrow';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';

        foreach (PhpTokens::rawTokens($text) as $i => $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            if ($t[0] === T_FUNCTION) {
                $nextIdx = $i + 1;
                $usePart = '';
                $afterUse = '';
                $bodyStart = null;

                while ($nextIdx < count(PhpTokens::rawTokens($text))) {
                    $next = PhpTokens::rawTokens($text)[$nextIdx] ?? '';
                    if (is_string($next)) {
                        if ($next === '{') {
                            $bodyStart = $nextIdx;
                            break;
                        }
                        $usePart .= $next;
                    } else {
                        $usePart .= $next[1] ?? '';
                    }
                    $nextIdx++;
                }

                if ($bodyStart !== null) {
                    $useVars = [];
                    if (preg_match('/use\s*\(([^)]*)\)/', $usePart, $m)) {
                        $varsRaw = $m[1];
                        preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $varsRaw, $vm);
                        $useVars = $vm[1] ?? [];
                    }

                    $bodyTokens = [];
                    $braceDepth = 0;
                    $returnExpr = '';
                    $bodyEndIdx = null;

                    for ($bi = $bodyStart + 1; $bi < count(PhpTokens::rawTokens($text)); $bi++) {
                        $bt = PhpTokens::rawTokens($text)[$bi];
                        if (is_string($bt)) {
                            if ($bt === '{') {
                                $braceDepth++;
                            } elseif ($bt === '}') {
                                $braceDepth--;
                                if ($braceDepth === 0) {
                                    $bodyEndIdx = $bi;
                                    break;
                                }
                            }
                            $bodyTokens[] = $bt;
                        } else {
                            $bodyTokens[] = $bt[1] ?? '';
                        }
                    }

                    $bodyStr = implode('', $bodyTokens);
                    $bodyStr = trim($bodyStr);

                    if (preg_match('/^\s*return\s+(.+?);\s*$/s', $bodyStr, $rm)) {
                        $returnExpr = trim($rm[1]);
                    } else {
                        $returnExpr = '{ ' . $bodyStr . ' }';
                    }

                    $rebuilt .= 'fn(';
                    if (preg_match('/function\s*\(([^)]*)\)/', $usePart === '' ? '' : $usePart, $pm)) {
                        $params = trim($pm[1]);
                        if ($params !== '') {
                            $rebuilt .= $params;
                        }
                    }
                    $rebuilt .= ') => ' . $returnExpr;

                    if ($bodyEndIdx !== null) {
                        for ($xi = $i + 1; $xi <= $bodyEndIdx; $xi++) {
                            $xt = PhpTokens::rawTokens($text)[$xi];
                            if (!is_string($xt)) {
                                continue;
                            }
                        }
                        $i = $bodyEndIdx;
                        continue;
                    }
                }
            }

            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
