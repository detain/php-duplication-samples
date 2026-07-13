<?php

declare(strict_types=1);

namespace Gen\Transforms\Rn;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RN-02 params — consistently rename function/method parameter names (Type-2).
 *
 * The AST identifies which identifiers are genuine parameters; the rename is
 * then applied at the token level so every occurrence changes consistently and
 * formatting/line-count is untouched. Renaming a name to one that collides with
 * an existing variable or param is rejected — that would blur the clone.
 *
 * params:
 *   renames (array<string,string>)  {oldName: newName} WITHOUT the leading '$'
 */
final class Params implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RN-02';
    }

    public function name(): string
    {
        return 'params';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $renames = $params['renames'] ?? [];
        if (!is_array($renames) || $renames === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text    = $in->text();
        $params2 = array_flip($this->ast->paramNames($text));
        $locals  = array_flip($this->ast->localVariableNames($text));
        $existing = $locals + $params2;

        foreach ($renames as $old => $new) {
            if (!isset($params2[$old])) {
                throw new \RuntimeException("RN-02: '\${$old}' is not a parameter in the payload");
            }
            if (isset($existing[$new])) {
                throw new \RuntimeException("RN-02: rename target '\${$new}' collides with an existing symbol");
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$new)) {
                throw new \RuntimeException("RN-02: invalid rename target '\${$new}'");
            }
        }

        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            // Only rename T_VARIABLE tokens that match parameter names.
            if ($t[0] === T_VARIABLE) {
                $bare = ltrim($t[1], '$');
                if (isset($renames[$bare]) && isset($params2[$bare])) {
                    $rebuilt .= '$' . $renames[$bare];
                    continue;
                }
            }
            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
