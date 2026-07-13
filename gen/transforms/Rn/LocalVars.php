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
 * RN-01 local_vars — consistently rename local variables (Type-2).
 *
 * The AST identifies which identifiers are genuine local variables (excluding
 * parameters and $this); the rename is then applied at the token level so every
 * occurrence changes consistently and formatting/line-count is untouched (no
 * accidental whitespace axis). Renaming a name to one that collides with an
 * existing variable or a param is rejected — that would blur the clone.
 *
 * params:
 *   renames (array<string,string>)  {oldName: newName} WITHOUT the leading '$'
 */
final class LocalVars implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RN-01';
    }

    public function name(): string
    {
        return 'local_vars';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $renames = $params['renames'] ?? [];
        if (!is_array($renames) || $renames === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text    = $in->text();
        $locals  = array_flip($this->ast->localVariableNames($text));
        $params2 = array_flip($this->ast->paramNames($text));
        $existing = $locals + $params2;

        foreach ($renames as $old => $new) {
            if (!isset($locals[$old])) {
                throw new \RuntimeException("RN-01: '\${$old}' is not a local variable in the payload");
            }
            if (isset($existing[$new])) {
                throw new \RuntimeException("RN-01: rename target '\${$new}' collides with an existing symbol");
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$new)) {
                throw new \RuntimeException("RN-01: invalid rename target '\${$new}'");
            }
        }

        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            if ($t[0] === T_VARIABLE) {
                $bare = ltrim($t[1], '$');
                if (isset($renames[$bare])) {
                    $rebuilt .= '$' . $renames[$bare];
                    continue;
                }
            }
            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        // Line count is unchanged; keep the incoming map.
        return new TransformResult($lines, $in->lineMap);
    }
}
