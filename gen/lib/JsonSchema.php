<?php

declare(strict_types=1);

namespace Gen\Lib;

/**
 * Minimal, dependency-free JSON Schema validator supporting the draft-07 subset
 * used by testsets/schema/*.json: type (incl. unions and null), required,
 * properties, additionalProperties (bool or schema), items, enum, const,
 * pattern, minimum/maximum, minLength, minItems.
 *
 * Returns a list of human-readable error strings ([] == valid).
 */
final class JsonSchema
{
    /** @return list<string> */
    public static function validateFile(string $dataFile, string $schemaFile): array
    {
        $data = json_decode((string)file_get_contents($dataFile), true);
        if ($data === null && trim((string)file_get_contents($dataFile)) !== 'null') {
            return ["{$dataFile}: not valid JSON"];
        }
        $schema = json_decode((string)file_get_contents($schemaFile), true);
        if (!is_array($schema)) {
            return ["{$schemaFile}: not valid JSON schema"];
        }
        return self::validate($data, $schema, '$');
    }

    /**
     * @param mixed $data
     * @param array<string,mixed> $schema
     * @return list<string>
     */
    public static function validate(mixed $data, array $schema, string $path = '$'): array
    {
        $errors = [];

        if (isset($schema['const']) && $data !== $schema['const']) {
            $errors[] = "{$path}: must equal " . json_encode($schema['const']);
        }

        if (isset($schema['enum'])) {
            $ok = false;
            foreach ($schema['enum'] as $opt) {
                if ($data === $opt) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $errors[] = "{$path}: " . json_encode($data) . " not in enum " . json_encode($schema['enum']);
            }
        }

        if (isset($schema['type'])) {
            $types = (array)$schema['type'];
            if (!self::matchesType($data, $types)) {
                $errors[] = "{$path}: expected type " . implode('|', $types) . ", got " . self::typeName($data);
                return $errors; // no point checking deeper if the base type is wrong
            }
        }

        if (is_string($data)) {
            if (isset($schema['minLength']) && mb_strlen($data) < (int)$schema['minLength']) {
                $errors[] = "{$path}: string shorter than minLength {$schema['minLength']}";
            }
            if (isset($schema['pattern']) && !preg_match('/' . str_replace('/', '\/', $schema['pattern']) . '/', $data)) {
                $errors[] = "{$path}: '{$data}' does not match pattern {$schema['pattern']}";
            }
        }

        if (is_int($data) || is_float($data)) {
            if (isset($schema['minimum']) && $data < $schema['minimum']) {
                $errors[] = "{$path}: {$data} < minimum {$schema['minimum']}";
            }
            if (isset($schema['maximum']) && $data > $schema['maximum']) {
                $errors[] = "{$path}: {$data} > maximum {$schema['maximum']}";
            }
        }

        if (is_array($data) && self::isObject($data, $schema)) {
            foreach ((array)($schema['required'] ?? []) as $req) {
                if (!array_key_exists($req, $data)) {
                    $errors[] = "{$path}: missing required property '{$req}'";
                }
            }
            $props = $schema['properties'] ?? [];
            foreach ($props as $name => $propSchema) {
                if (array_key_exists($name, $data)) {
                    $errors = array_merge($errors, self::validate($data[$name], $propSchema, "{$path}.{$name}"));
                }
            }
            $addl = $schema['additionalProperties'] ?? true;
            if ($addl === false) {
                foreach (array_keys($data) as $key) {
                    if (!isset($props[$key])) {
                        $errors[] = "{$path}: unexpected property '{$key}'";
                    }
                }
            } elseif (is_array($addl)) {
                foreach ($data as $key => $val) {
                    if (!isset($props[$key])) {
                        $errors = array_merge($errors, self::validate($val, $addl, "{$path}.{$key}"));
                    }
                }
            }
        }

        if (is_array($data) && self::isList($data) && (in_array('array', (array)($schema['type'] ?? []), true) || isset($schema['items']))) {
            if (isset($schema['minItems']) && count($data) < (int)$schema['minItems']) {
                $errors[] = "{$path}: array has fewer than minItems {$schema['minItems']}";
            }
            if (isset($schema['items'])) {
                foreach ($data as $i => $item) {
                    $errors = array_merge($errors, self::validate($item, $schema['items'], "{$path}[{$i}]"));
                }
            }
        }

        return $errors;
    }

    /** @param list<string> $types */
    private static function matchesType(mixed $data, array $types): bool
    {
        foreach ($types as $type) {
            switch ($type) {
                case 'string':  if (is_string($data)) return true; break;
                case 'integer': if (is_int($data)) return true; break;
                case 'number':  if (is_int($data) || is_float($data)) return true; break;
                case 'boolean': if (is_bool($data)) return true; break;
                case 'null':    if ($data === null) return true; break;
                case 'object':  if (is_array($data) && (self::isAssoc($data) || $data === [])) return true; break;
                case 'array':   if (is_array($data) && (self::isList($data) || $data === [])) return true; break;
            }
        }
        return false;
    }

    private static function typeName(mixed $d): string
    {
        if ($d === null) return 'null';
        if (is_bool($d)) return 'boolean';
        if (is_int($d)) return 'integer';
        if (is_float($d)) return 'number';
        if (is_string($d)) return 'string';
        if (is_array($d)) return self::isList($d) ? 'array' : 'object';
        return gettype($d);
    }

    /** Whether we should treat $data as a JSON object under this schema. */
    private static function isObject(array $data, array $schema): bool
    {
        $types = (array)($schema['type'] ?? []);
        if (in_array('object', $types, true)) {
            return true;
        }
        if (isset($schema['properties']) || isset($schema['required']) || isset($schema['additionalProperties'])) {
            return self::isAssoc($data) || $data === [];
        }
        return self::isAssoc($data);
    }

    private static function isAssoc(array $a): bool
    {
        if ($a === []) {
            return false;
        }
        return array_keys($a) !== range(0, count($a) - 1);
    }

    private static function isList(array $a): bool
    {
        return $a === [] || array_keys($a) === range(0, count($a) - 1);
    }
}
