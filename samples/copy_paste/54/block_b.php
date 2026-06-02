<?php

declare(strict_types=1);

namespace App\Helpers;

class CalendarHelper
{
    public static function getDaysInMonth(int $year, int $month): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    public static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    public static function getWeekNumber(\DateTimeInterface $date): int
    {
        return (int) $date->format('W');
    }

    public static function getDayOfYear(\DateTimeInterface $date): int
    {
        return (int) $date->format('z') + 1;
    }

    public static function getQuarter(\DateTimeInterface $date): int
    {
        return (int) ceil((int) $date->format('n') / 3);
    }

    public static function addBusinessDays(\DateTimeInterface $date, int $days): \DateTimeImmutable
    {
        $result = new \DateTimeImmutable($date->format('Y-m-d'));
        $added = 0;

        while ($added < $days) {
            $result = $result->modify('+1 day');
            $dayOfWeek = (int) $result->format('N');

            if ($dayOfWeek < 6) {
                $added++;
            }
        }

        return $result;
    }

    public static function subtractBusinessDays(\DateTimeInterface $date, int $days): \DateTimeImmutable
    {
        $result = new \DateTimeImmutable($date->format('Y-m-d'));
        $subtracted = 0;

        while ($subtracted < $days) {
            $result = $result->modify('-1 day');
            $dayOfWeek = (int) $result->format('N');

            if ($dayOfWeek < 6) {
                $subtracted++;
            }
        }

        return $result;
    }

    public static function isBusinessDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N');
        return $dayOfWeek < 6;
    }

    public static function getBusinessDaysBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $current = new \DateTimeImmutable($start->format('Y-m-d'));
        $endImmutable = new \DateTimeImmutable($end->format('Y-m-d'));
        $businessDays = 0;

        while ($current < $endImmutable) {
            $current = $current->modify('+1 day');
            if (self::isBusinessDay($current)) {
                $businessDays++;
            }
        }

        return $businessDays;
    }

    public static function getMonthName(int $month, string $locale = 'en'): string
    {
        $months = [
            'en' => ['', 'January', 'February', 'March', 'April', 'May', 'June',
                     'July', 'August', 'September', 'October', 'November', 'December'],
            'es' => ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                     'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        ];

        return $months[$locale][$month] ?? '';
    }

    public static function getDayName(int $day, string $locale = 'en'): string
    {
        $days = [
            'en' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'es' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ];

        return $days[$locale][$day - 1] ?? '';
    }

    public static function getWeekStart(\DateTimeInterface $date): \DateTimeImmutable
    {
        $dayOfWeek = (int) $date->format('N');
        return (new \DateTimeImmutable($date->format('Y-m-d')))->modify('-' . ($dayOfWeek - 1) . ' days');
    }

    public static function getWeekEnd(\DateTimeInterface $date): \DateTimeImmutable
    {
        $dayOfWeek = (int) $date->format('N');
        return (new \DateTimeImmutable($date->format('Y-m-d')))->modify('+' . (7 - $dayOfWeek) . ' days');
    }

    public static function getDayOfWeek(\DateTimeInterface $date): int
    {
        return (int) $date->format('N');
    }

    public static function isWeekend(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N');
        return $dayOfWeek >= 6;
    }

    public static function toIsoWeekDate(\DateTimeInterface $date): string
    {
        return $date->format('o-W');
    }

    public static function fromIsoWeekDate(int $year, int $week): \DateTimeImmutable
    {
        return new \DateTimeImmutable("{$year}-W{$week}-1");
    }

    public static function getAge(\DateTimeInterface $birthDate, ?\DateTimeInterface $now = null): int
    {
        $now = $now ?? new \DateTimeImmutable();
        return (int) $birthDate->diff($now)->y;
    }

    public static function getNextBirthday(\DateTimeInterface $birthDate, ?\DateTimeInterface $now = null): \DateTimeImmutable
    {
        $now = $now ?? new \DateTimeImmutable();
        $thisYear = new \DateTimeImmutable($now->format('Y') . '-' . $birthDate->format('m-d'));

        if ($thisYear >= $now) {
            return $thisYear;
        }

        return $thisYear->modify('+1 year');
    }
}
