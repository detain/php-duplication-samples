<?php

declare(strict_types=1);

namespace App\Helpers;

class TimezoneHelper
{
    public static function convertToTimezone(\DateTimeInterface $date, string $timezone): \DateTimeImmutable
    {
        $tz = new \DateTimeZone($timezone);
        return (new \DateTimeImmutable($date->format('Y-m-d H:i:s')))->setTimezone($tz);
    }

    public static function getTimezoneOffset(string $timezone, ?\DateTimeInterface $date = null): int
    {
        $tz = new \DateTimeZone($timezone);
        $date = $date ?? new \DateTimeImmutable();
        return $tz->getOffset($date);
    }

    public static function getTimezoneName(string $offset, bool $abbr = false): ?string
    {
        $time = new \DateTimeImmutable();
        $time->setTimezone(new \DateTimeZone('UTC'));

        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $tz = new \DateTimeZone($identifier);
            if ($tz->getOffset($time) === (int) $offset) {
                $dt = new \DateTimeImmutable(null, $tz);
                if ($abbr) {
                    return $dt->format('T');
                }
                return $identifier;
            }
        }

        return null;
    }

    public static function listTimezones(string $filter = 'all'): array
    {
        $timezones = \DateTimeZone::listIdentifiers();

        if ($filter === 'all') {
            return $timezones;
        }

        $filtered = [];
        foreach ($timezones as $tz) {
            if (str_starts_with($tz, $filter)) {
                $filtered[] = $tz;
            }
        }

        return $filtered;
    }

    public static function getCountryTimezones(string $countryCode): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($countryCode));
    }

    public static function formatInTimezone(\DateTimeInterface $date, string $timezone, string $format = 'Y-m-d H:i:s T'): string
    {
        $converted = self::convertToTimezone($date, $timezone);
        return $converted->format($format);
    }

    public static function getUtcOffset(): string
    {
        $now = new \DateTimeImmutable();
        return $now->format('P');
    }

    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, \DateTimeZone::listIdentifiers(), true);
    }

    public static function getTimezonesByOffset(int $offsetHours): array
    {
        $offsetSeconds = $offsetHours * 3600;
        $result = [];

        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $tz = new \DateTimeZone($identifier);
            $testDate = new \DateTimeImmutable('now', $tz);
            if ($tz->getOffset($testDate) === $offsetSeconds) {
                $result[] = $identifier;
            }
        }

        return $result;
    }

    public static function guessTimezoneFromIp(string $ip): ?string
    {
        $ipApiEndpoints = [
            "http://ip-api.com/json/{$ip}?fields=timezone",
        ];

        foreach ($ipAPEndpoints as $url) {
            try {
                $response = @file_get_contents($url);
                if ($response === false) {
                    continue;
                }

                $data = json_decode($response, true);
                if (isset($data['timezone']) && self::isValidTimezone($data['timezone'])) {
                    return $data['timezone'];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
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

    public static function getTimezoneCoordinates(string $timezone): ?array
    {
        $coordinates = [
            'America/New_York' => ['lat' => 40.7128, 'lon' => -74.0060],
            'America/Los_Angeles' => ['lat' => 34.0522, 'lon' => -118.2437],
            'Europe/London' => ['lat' => 51.5074, 'lon' => -0.1278],
            'Asia/Tokyo' => ['lat' => 35.6762, 'lon' => 139.6503],
            'Australia/Sydney' => ['lat' => -33.8688, 'lon' => 151.2093],
        ];

        return $coordinates[$timezone] ?? null;
    }

    public static function calculateTravelTime(string $originTz, string $destTz, \DateTimeInterface $departure): array
    {
        $departureUtc = self::convertToTimezone($departure, 'UTC');
        $arrivalUtc = self::convertToTimezone($departure, $destTz);

        $flightHours = 2;
        $layoverHours = 2;

        $totalHours = $flightHours + $layoverHours;
        $arrivalLocal = $arrivalUtc->modify("+{$totalHours} hours");

        return [
            'departure_utc' => $departureUtc->format('c'),
            'arrival_local' => $arrivalLocal->format('c'),
            'flight_duration_hours' => $flightHours,
            'total_travel_hours' => $totalHours,
        ];
    }
}
