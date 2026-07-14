<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function diff(string $date1, string $date2, string $unit = 'days'): int
    {
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);
        if ($ts1 === false || $ts2 === false) {
            return 0;
        }

        $diff = abs($ts2 - $ts1);
        return match ($unit) {
            'seconds' => $diff,
            'minutes' => (int) round($diff / 60),
            'hours' => (int) round($diff / 3600),
            'days' => (int) round($diff / 86400),
            'weeks' => (int) round($diff / 604800),
            'months' => (int) round($diff / 2592000),
            'years' => (int) round($diff / 31536000),
            default => 0,
        };
    }

    public function add(string $date, int $amount, string $unit = 'days'): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $newTimestamp = match ($unit) {
            'seconds' => $timestamp + $amount,
            'minutes' => $timestamp + ($amount * 60),
            'hours' => $timestamp + ($amount * 3600),
            'days' => $timestamp + ($amount * 86400),
            'weeks' => $timestamp + ($amount * 604800),
            'months' => $this->addMonths($timestamp, $amount),
            'years' => $this->addMonths($timestamp, $amount * 12),
            default => $timestamp,
        };

        return date('Y-m-d H:i:s', $newTimestamp);
    }

    public function format(string $date, string $format = 'Y-m-d'): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }
        return date($format, $timestamp);
    }

    public function timezoneConvert(string $date, string $fromTz, string $toTz): string
    {
        $dt = new \DateTime($date, new \DateTimeZone($fromTz));
        $dt->setTimezone(new \DateTimeZone($toTz));
        return $dt->format('Y-m-d H:i:s');
    }

    protected function addMonths(int $timestamp, int $months): int
    {
        $year = (int) date('Y', $timestamp);
        $month = (int) date('m', $timestamp);
        $day = (int) date('d', $timestamp);

        $month += $months;
        $year += intdiv($month - 1, 12);
        $month = (($month - 1) % 12) + 1;

        $lastDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if ($day > $lastDayOfMonth) {
            $day = $lastDayOfMonth;
        }

        return mktime((int) date('H', $timestamp), (int) date('i', $timestamp), (int) date('s', $timestamp), $month, $day, $year);
    }
    // <<<END-NEARMISS>>>
}
