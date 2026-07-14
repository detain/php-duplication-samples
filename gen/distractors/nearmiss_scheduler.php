<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function nextRun(string $expression, ?string $baseTime = null): ?string
    {
        $parts = explode(' ', $expression);
        if (count($parts) < 5) {
            return null;
        }

        $base = $baseTime !== null ? strtotime($baseTime) : time();
        $next = $this->findNextMatch($parts, $base);

        return $next !== null ? date('Y-m-d H:i:s', $next) : null;
    }

    public function isDue(string $expression, ?string $lastRun = null): bool
    {
        $next = $this->nextRun($expression, $lastRun);
        if ($next === null) {
            return false;
        }
        return strtotime($next) <= time();
    }

    public function validate(string $expression): bool
    {
        $parts = explode(' ', $expression);
        if (count($parts) !== 5) {
            return false;
        }

        [$min, $hour, $day, $month, $dow] = $parts;

        if (!$this->validateField($min, 0, 59) ||
            !$this->validateField($hour, 0, 23) ||
            !$this->validateField($day, 1, 31) ||
            !$this->validateField($month, 1, 12) ||
            !$this->validateField($dow, 0, 6)) {
            return false;
        }

        return true;
    }

    protected function findNextMatch(array $parts, int $base): ?int
    {
        [$min, $hour, $day, $month, $dow] = $parts;

        for ($i = 0; $i < 525600; $i++) {
            $check = $base + ($i * 60);
            if ($this->matchesCron($check, $min, $hour, $day, $month, $dow)) {
                return $check;
            }
        }

        return null;
    }

    protected function matchesCron(int $ts, string $min, string $hour, string $day, string $month, string $dow): bool
    {
        $m = (int) date('i', $ts);
        $h = (int) date('H', $ts);
        $d = (int) date('j', $ts);
        $mon = (int) date('n', $ts);
        $w = (int) date('w', $ts);

        return $this->fieldMatches($m, $min) &&
               $this->fieldMatches($h, $hour) &&
               $this->fieldMatches($d, $day) &&
               $this->fieldMatches($mon, $month) &&
               $this->fieldMatches($w, $dow);
    }

    protected function fieldMatches(int $value, string $field): bool
    {
        if ($field === '*') {
            return true;
        }

        if (str_contains($field, ',')) {
            foreach (explode(',', $field) as $item) {
                if ($this->fieldMatches($value, trim($item))) {
                    return true;
                }
            }
            return false;
        }

        if (str_contains($field, '/')) {
            [$range, $step] = explode('/', $field);
            $step = (int) $step;
            if ($range === '*') {
                return $value % $step === 0;
            }
            if (str_contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                $start = (int) $start;
                $end = (int) $end;
                for ($v = $start; $v <= $end; $v += $step) {
                    if ($v === $value) {
                        return true;
                    }
                }
                return false;
            }
            return ((int) $range <= $value) && ($value <= 59) && ($value % $step === 0);
        }

        if (str_contains($field, '-')) {
            [$start, $end] = explode('-', $field);
            return $value >= (int) $start && $value <= (int) $end;
        }

        return $value === (int) $field;
    }

    protected function validateField(string $field, int $min, int $max): bool
    {
        if ($field === '*') {
            return true;
        }

        if (str_contains($field, ',')) {
            foreach (explode(',', $field) as $item) {
                if (!$this->validateField(trim($item), $min, $max)) {
                    return false;
                }
            }
            return true;
        }

        if (str_contains($field, '/')) {
            $parts = explode('/', $field);
            if (count($parts) !== 2) {
                return false;
            }
            [$range, $step] = $parts;
            if ($range !== '*' && !$this->validateField($range, $min, $max)) {
                return false;
            }
            return is_numeric($step) && (int) $step > 0;
        }

        if (str_contains($field, '-')) {
            $parts = explode('-', $field);
            if (count($parts) !== 2) {
                return false;
            }
            [$start, $end] = $parts;
            return is_numeric($start) && is_numeric($end) &&
                   (int) $start >= $min && (int) $end <= $max &&
                   (int) $start <= (int) $end;
        }

        return is_numeric($field) && (int) $field >= $min && (int) $field <= $max;
    }
    // <<<END-NEARMISS>>>
}
