<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Comprehensive date/time handling utility combining date formatting,
 * calendar calculations, and timezone conversion capabilities.
 */
final class DateTimeHelper
{
    private const MONTH_NAMES_EN = ['', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    private const DAY_NAMES_EN = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    // ==================== FORMAT METHODS ====================

    public static function formatDateTime(\DateTimeInterface $date, string $format = 'Y-m-d H:i:s'): string
    {
        return $date->format($format);
    }

    public static function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public static function formatTime(\DateTimeInterface $date): string
    {
        return $date->format('H:i:s');
    }

    public static function formatDateForDisplay(\DateTimeInterface $date): string
    {
        return $date->format('M j, Y');
    }

    public static function formatDateTimeForDisplay(\DateTimeInterface $date): string
    {
        return $date->format('M j, Y g:i A');
    }

    public static function formatTimeForDisplay(\DateTimeInterface $date): string
    {
        return $date->format('g:i A');
    }

    public static function formatRelative(\DateTimeInterface $date): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($date);

        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';

        return 'just now';
    }

    public static function formatIso8601(\DateTimeInterface $date): string
    {
        return $date->format(\DateTimeInterface::ATOM);
    }

    public static function formatRfc7231(\DateTimeInterface $date): string
    {
        return $date->format('D, d M Y H:i:s \G\M\T');
    }

    public static function formatTimezone(\DateTimeInterface $date, string $timezone): string
    {
        $date->setTimezone(new \DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s T');
    }

    // ==================== PARSE METHODS ====================

    public static function parseDate(string $date, string $format = 'Y-m-d'): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat($format, $date);
        return $parsed ?: null;
    }

    public static function parseDateTime(string $date, string $format = 'Y-m-d H:i:s'): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat($format, $date);
        return $parsed ?: null;
    }

    // ==================== PERIOD METHODS ====================

    public static function startOfDay(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-d') . ' 00:00:00'
        );
    }

    public static function endOfDay(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-d') . ' 23:59:59'
        );
    }

    public static function startOfMonth(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m') . '-01 00:00:00'
        );
    }

    public static function endOfMonth(\DateTimeInterface $date): \DateTimeImmutable
    {
        $lastDay = $date->format('t');
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m') . "-{$lastDay} 23:59:59"
        );
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

    // ==================== CALENDAR METHODS ====================

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

    public static function getDayOfWeek(\DateTimeInterface $date): int
    {
        return (int) $date->format('N');
    }

    public static function isWeekend(\DateTimeInterface $date): bool
    {
        return (int) $date->format('N') >= 6;
    }

    public static function isBusinessDay(\DateTimeInterface $date): bool
    {
        return (int) $date->format('N') < 6;
    }

    public static function toIsoWeekDate(\DateTimeInterface $date): string
    {
        return $date->format('o-W');
    }

    public static function fromIsoWeekDate(int $year, int $week): \DateTimeImmutable
    {
        return new \DateTimeImmutable("{$year}-W{$week}-1");
    }

    public static function getMonthName(int $month): string
    {
        return self::MONTH_NAMES_EN[$month] ?? '';
    }

    public static function getDayName(int $day): string
    {
        return self::DAY_NAMES_EN[$day - 1] ?? '';
    }

    // ==================== BUSINESS DAY METHODS ====================

    public static function addBusinessDays(\DateTimeInterface $date, int $days): \DateTimeImmutable
    {
        $result = new \DateTimeImmutable($date->format('Y-m-d'));
        $added = 0;

        while ($added < $days) {
            $result = $result->modify('+1 day');
            if (self::isBusinessDay($result)) {
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
            if (self::isBusinessDay($result)) {
                $subtracted++;
            }
        }

        return $result;
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

    // ==================== TIMEZONE METHODS ====================

    public static function convertToTimezone(\DateTimeInterface $date, string $timezone): \DateTimeImmutable
    {
        $tz = new \DateTimeZone($timezone);
        return (new \DateTimeImmutable($date->format('Y-m-d H:i:s')))->setTimezone($tz);
    }

    public static function formatInTimezone(\DateTimeInterface $date, string $timezone, string $format = 'Y-m-d H:i:s T'): string
    {
        $converted = self::convertToTimezone($date, $timezone);
        return $converted->format($format);
    }

    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, \DateTimeZone::listIdentifiers(), true);
    }

    public static function getTimezoneAbbreviation(string $timezone): ?string
    {
        if (!self::isValidTimezone($timezone)) {
            return null;
        }

        $tz = new \DateTimeZone($timezone);
        $now = new \DateTimeImmutable('now', $tz);
        return $now->format('T');
    }

    // ==================== AGE METHODS ====================

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
