<?php

declare(strict_types=1);

namespace Gen\Lib;

/**
 * Lightweight PHP token-stream helpers shared by transforms and the verifier.
 *
 * All WS/CM transforms lean on these so they never split a string literal,
 * heredoc, or multi-line comment. The verifier uses the normalization helpers
 * for positive-proof (members equal after the declared pipeline) and
 * negative-assurance (no accidental >=40 normalized-token match).
 */
final class PhpTokens
{
    /**
     * token_get_all over a bare PHP fragment (no <?php tag of its own).
     * A leading "<?php " with no newline keeps token line numbers aligned to
     * the fragment's own 1-based lines. The open tag is dropped.
     *
     * @return list<array{0:int,1:string,2:int}|string>
     */
    public static function rawTokens(string $fragment): array
    {
        $tokens = @token_get_all('<?php ' . $fragment);
        array_shift($tokens); // drop the injected T_OPEN_TAG
        return $tokens;
    }

    /**
     * Normalized token stream as a list of strings.
     *
     * Options (all default false except comments-stripping):
     *   comments (bool, default true)  strip // and /* comments
     *   vars     (bool)  T_VARIABLE       -> '$V'
     *   numbers  (bool)  T_LNUMBER/DNUMBER-> 'NUM'
     *   strings  (bool)  encapsed string  -> 'STR'
     *   names    (bool)  T_STRING         -> 'NAME'
     *   case_conv(bool)   lowercase all identifiers + names (for RN-05)
     *   typehint (bool)  strip T_STRING type-hint tokens (for TY-01/02)
     *
     * @param array<string,bool> $opts
     * @return list<string>
     */
    public static function normalize(string $fragment, array $opts = []): array
    {
        $stripComments = $opts['comments'] ?? true;
        $absVars       = $opts['vars'] ?? false;
        $absNumbers    = $opts['numbers'] ?? false;
        $absStrings    = $opts['strings'] ?? false;
        $absNames      = $opts['names'] ?? false;
        $caseConv      = $opts['case_conv'] ?? false;
        $typeHint      = $opts['typehint'] ?? false;

        $out = [];
        foreach (self::rawTokens($fragment) as $t) {
            if (is_string($t)) {
                $out[] = $t;
                continue;
            }
            [$id, $text] = $t;
            if ($id === T_WHITESPACE) {
                continue;
            }
            if ($stripComments && ($id === T_COMMENT || $id === T_DOC_COMMENT)) {
                continue;
            }
            // Strip type-hint tokens (e.g., 'int', 'float', 'string', 'array', 'bool' etc.)
            if ($typeHint && ($id === T_STRING || $id === T_ARRAY || $id === T_NS_SEPARATOR) && self::isTypeHintToken($text)) {
                continue;
            }
            switch ($id) {
                case T_VARIABLE:
                    $out[] = $absVars ? '$V' : ($caseConv ? strtolower($text) : $text);
                    break;
                case T_LNUMBER:
                case T_DNUMBER:
                    $out[] = $absNumbers ? 'NUM' : $text;
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $out[] = $absStrings ? 'STR' : $text;
                    break;
                case T_STRING:
                    $text2 = ($caseConv && !$typeHint) ? strtolower($text) : $text;
                    $out[] = $absNames ? 'NAME' : $text2;
                    break;
                default:
                    $out[] = $text;
            }
        }
        return $out;
    }

    private static function isTypeHintToken(string $text): bool
    {
        static $types = [
            'int'=>true, 'float'=>true, 'string'=>true, 'bool'=>true,
            'void'=>true, 'null'=>true, 'true'=>true, 'false'=>true,
            'mixed'=>true, 'object'=>true, 'iterable'=>true, 'resource'=>true,
            'static'=>true, 'self'=>true, 'parent'=>true, 'array'=>true,
            'never'=>true, 'closure'=>true, 'generator'=>true,
        ];
        return isset($types[strtolower($text)]);
    }

    /**
     * Map normalized_by stage names (from expected.json) to normalize() opts.
     *
     * @param list<string> $stages
     * @return array<string,bool>
     */
    public static function optsForStages(array $stages): array
    {
        $opts = ['comments' => false];
        foreach ($stages as $stage) {
            switch ($stage) {
                case 'comments':
                    $opts['comments'] = true;
                    break;
                case 'identifiers':
                    $opts['vars'] = true;
                    break;
                case 'literals':
                    $opts['numbers'] = true;
                    $opts['strings'] = true;
                    break;
                case 'whitespace':
                    // whitespace is always stripped by normalize()
                    break;
                case 'case_convention':
                    $opts['case_conv'] = true;
                    break;
                case 'types':
                    // "types" stage triggers type-hint stripping for TY-01/TY-02
                    $opts['typehint'] = true;
                    break;
                // namespaces/controlflow/api/semantic: no token-level
                // canonicalization here; those clusters rely on behavioral proof.
            }
        }
        return $opts;
    }

    /** The strict Type-2 normalization used for negative assurance and hashing. */
    public static function type2(string $fragment): array
    {
        return self::normalize($fragment, [
            'comments' => true,
            'vars'     => true,
            'numbers'  => true,
            'strings'  => true,
            'names'    => false,
        ]);
    }

    /**
     * For each 1-based line boundary i (between line i and i+1), whether it is
     * SAFE to insert a blank line there (i.e. not inside a multi-line string,
     * heredoc, or comment). Whitespace-token newlines are always safe.
     *
     * @return array<int,bool> keys 1..lineCount-1
     */
    public static function safeLineBoundaries(string $fragment): array
    {
        $lineCount = substr_count($fragment, "\n") + 1;
        $unsafe = [];
        foreach (self::rawTokens($fragment) as $t) {
            if (is_string($t)) {
                continue;
            }
            [$id, $text, $line] = $t;
            if ($id === T_WHITESPACE) {
                continue;
            }
            $nl = substr_count($text, "\n");
            for ($b = $line; $b < $line + $nl; $b++) {
                $unsafe[$b] = true;
            }
        }
        $safe = [];
        for ($i = 1; $i < $lineCount; $i++) {
            $safe[$i] = !isset($unsafe[$i]);
        }
        return $safe;
    }

    /**
     * Longest common contiguous run between two normalized token streams.
     *
     * @param list<string> $a
     * @param list<string> $b
     */
    public static function longestCommonRun(array $a, array $b): int
    {
        $n = count($a);
        $m = count($b);
        if ($n === 0 || $m === 0) {
            return 0;
        }
        $prev = array_fill(0, $m + 1, 0);
        $best = 0;
        for ($i = 1; $i <= $n; $i++) {
            $curr = array_fill(0, $m + 1, 0);
            for ($j = 1; $j <= $m; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $curr[$j] = $prev[$j - 1] + 1;
                    if ($curr[$j] > $best) {
                        $best = $curr[$j];
                    }
                }
            }
            $prev = $curr;
        }
        return $best;
    }

    /** Token-level edit distance (Levenshtein over token lists), capped for speed. */
    public static function tokenEditDistance(array $a, array $b): int
    {
        $n = count($a);
        $m = count($b);
        if ($n === 0) {
            return $m;
        }
        if ($m === 0) {
            return $n;
        }
        $prev = range(0, $m);
        for ($i = 1; $i <= $n; $i++) {
            $curr = [$i];
            for ($j = 1; $j <= $m; $j++) {
                $cost = ($a[$i - 1] === $b[$j - 1]) ? 0 : 1;
                $curr[$j] = min(
                    $prev[$j] + 1,
                    $curr[$j - 1] + 1,
                    $prev[$j - 1] + $cost
                );
            }
            $prev = $curr;
        }
        return $prev[$m];
    }
}
