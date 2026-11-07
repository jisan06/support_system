<?php

namespace App\Services;

use Carbon\Carbon;
use DateTimeZone;
use InvalidArgumentException;

class BusinessHoursService
{
    /**
     * Get the configured business timezone.
     */
    public function getTimezone(?string $override = null): string
    {
        $tz = $override ?? setting('business_timezone', config('app.timezone', 'UTC'));

        if (!in_array($tz, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException("Invalid business timezone [{$tz}].");
        }

        return $tz;
    }

    /**
     * Get configured working days as array of ISO day numbers (1=Monday, 7=Sunday).
     *
     * @return array<int>
     */
    public function getWorkingDays(?string $override = null): array
    {
        $raw = $override ?? setting('business_working_days', '1,2,3,4,5');

        if (empty($raw)) {
            throw new InvalidArgumentException("At least one working day must be configured.");
        }

        // Support comma, space, or semicolon separated values
        $parts = preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        $days = [];

        foreach ($parts as $part) {
            $num = (int) $part;
            if ($num === 0) {
                $num = 7; // Convert 0 (Sunday in some conventions) to 7 (ISO-8601 Sunday)
            }
            if ($num >= 1 && $num <= 7) {
                $days[] = $num;
            }
        }

        $days = array_values(array_unique($days));

        if (empty($days)) {
            throw new InvalidArgumentException("At least one working day must be configured.");
        }

        return $days;
    }

    /**
     * Get working hours for the day ['start' => '09:00', 'end' => '17:00'].
     *
     * @return array{start: string, end: string}
     */
    public function getWorkingHours(?string $overrideStart = null, ?string $overrideEnd = null): array
    {
        $start = $overrideStart ?? setting('business_start_time', '09:00');
        $end = $overrideEnd ?? setting('business_end_time', '17:00');

        $this->validateTimes($start, $end);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get holidays as an array of 'YYYY-MM-DD' date strings.
     *
     * @return array<string>
     */
    public function getHolidays(?string $override = null): array
    {
        $raw = $override ?? setting('business_holidays', '');

        if (empty(trim($raw))) {
            return [];
        }

        $lines = preg_split('/[\r\n,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $holidays = [];

        foreach ($lines as $line) {
            $cleaned = trim($line);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleaned)) {
                $holidays[] = $cleaned;
            }
        }

        return array_values(array_unique($holidays));
    }

    /**
     * Validate business hours configuration.
     * Throws InvalidArgumentException if invalid.
     */
    public function validateTimes(string $start, string $end): void
    {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start)) {
            throw new InvalidArgumentException("Invalid start time format [{$start}]. Expected HH:MM.");
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end)) {
            throw new InvalidArgumentException("Invalid end time format [{$end}]. Expected HH:MM.");
        }

        if (strcmp($start, $end) >= 0) {
            throw new InvalidArgumentException("Working end time [{$end}] must be after start time [{$start}].");
        }
    }

    /**
     * Check if a given date is a working day (working day of week and not a holiday).
     */
    public function isWorkingDay(Carbon $date, ?array $workingDays = null, ?array $holidays = null): bool
    {
        $workingDays = $workingDays ?? $this->getWorkingDays();
        $holidays = $holidays ?? $this->getHolidays();

        // Carbon dayOfWeekIso: 1 (Monday) through 7 (Sunday)
        if (!in_array($date->dayOfWeekIso, $workingDays, true)) {
            return false;
        }

        if (in_array($date->format('Y-m-d'), $holidays, true)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the datetime is within working hours on a working day.
     */
    public function isWithinWorkingHours(Carbon $date, ?array $hours = null, ?array $workingDays = null, ?array $holidays = null): bool
    {
        if (!$this->isWorkingDay($date, $workingDays, $holidays)) {
            return false;
        }

        $hours = $hours ?? $this->getWorkingHours();
        $start = $date->copy()->setTimeFromTimeString($hours['start']);
        $end = $date->copy()->setTimeFromTimeString($hours['end']);

        return $date->gte($start) && $date->lt($end);
    }

    /**
     * Advance a datetime to the next valid working moment.
     * If the datetime is already within business hours, returns a copy of it.
     * If before opening, returns opening time today.
     * If after closing or on a non-working day, returns opening time of the next working day.
     */
    public function advanceToWorkingMoment(Carbon $date, ?array $hours = null, ?array $workingDays = null, ?array $holidays = null): Carbon
    {
        $hours = $hours ?? $this->getWorkingHours();
        $workingDays = $workingDays ?? $this->getWorkingDays();
        $holidays = $holidays ?? $this->getHolidays();

        $current = $date->copy();
        $maxDays = 365;
        $counter = 0;

        while ($counter < $maxDays) {
            if ($this->isWorkingDay($current, $workingDays, $holidays)) {
                $workStart = $current->copy()->setTimeFromTimeString($hours['start']);
                $workEnd = $current->copy()->setTimeFromTimeString($hours['end']);

                if ($current->lt($workStart)) {
                    // Before opening hours today
                    return $workStart;
                }

                if ($current->lt($workEnd)) {
                    // Currently within opening hours
                    return $current;
                }
            }

            // After closing or non-working day -> move to next calendar day at start time
            $current->addDay()->setTimeFromTimeString($hours['start']);
            $counter++;
        }

        throw new InvalidArgumentException("Unable to find a valid working day within {$maxDays} days.");
    }
}

