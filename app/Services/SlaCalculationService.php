<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use InvalidArgumentException;

class SlaCalculationService
{
    protected BusinessHoursService $businessHours;

    public function __construct(BusinessHoursService $businessHours)
    {
        $this->businessHours = $businessHours;
    }

    /**
     * Calculate an SLA deadline using business hours.
     *
     * @param Carbon|string $startTime
     * @param int $durationMinutes
     * @param array{
     *     timezone?: string,
     *     working_days?: string|array,
     *     start_time?: string,
     *     end_time?: string,
     *     holidays?: string|array
     * }|null $customConfig
     * @return Carbon
     */
    public function calculateDeadline(Carbon|string $startTime, int $durationMinutes, ?array $customConfig = null): Carbon
    {
        if ($durationMinutes < 0) {
            throw new InvalidArgumentException("SLA duration minutes cannot be negative.");
        }

        // Resolve configuration
        $tz = $this->businessHours->getTimezone($customConfig['timezone'] ?? null);
        $hours = $this->businessHours->getWorkingHours(
            $customConfig['start_time'] ?? null,
            $customConfig['end_time'] ?? null
        );
        $workingDays = is_array($customConfig['working_days'] ?? null)
            ? $customConfig['working_days']
            : $this->businessHours->getWorkingDays($customConfig['working_days'] ?? null);
        $holidays = is_array($customConfig['holidays'] ?? null)
            ? $customConfig['holidays']
            : $this->businessHours->getHolidays($customConfig['holidays'] ?? null);

        // Convert start time to the business timezone
        $startCarbon = is_string($startTime)
            ? Carbon::parse($startTime, config('app.timezone', 'UTC'))->setTimezone($tz)
            : $startTime->copy()->setTimezone($tz);

        // If duration is 0, advance to valid working moment and return
        if ($durationMinutes === 0) {
            return $this->businessHours->advanceToWorkingMoment($startCarbon, $hours, $workingDays, $holidays)
                ->setTimezone(config('app.timezone', 'UTC'));
        }

        // 1. Advance to first valid moment within business hours
        $current = $this->businessHours->advanceToWorkingMoment($startCarbon, $hours, $workingDays, $holidays);
        $remainingMinutes = $durationMinutes;
        $maxLoops = 1000;
        $loop = 0;

        while ($remainingMinutes > 0 && $loop++ < $maxLoops) {
            // Ensure $current is inside business hours
            $current = $this->businessHours->advanceToWorkingMoment($current, $hours, $workingDays, $holidays);

            // Determine today's end of working hours
            $workEnd = $current->copy()->setTimeFromTimeString($hours['end']);

            // Calculate working minutes remaining today
            $minutesAvailableToday = $current->diffInMinutes($workEnd);

            if ($remainingMinutes <= $minutesAvailableToday) {
                // Deadline falls within today's remaining working hours
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                // Use all available minutes today, and advance to next working day
                $remainingMinutes -= $minutesAvailableToday;
                $current->addDay()->setTimeFromTimeString($hours['start']);
            }
        }

        if ($remainingMinutes > 0) {
            throw new InvalidArgumentException("Exceeded maximum calculation iterations ({$maxLoops}).");
        }

        return $current->setTimezone(config('app.timezone', 'UTC'));
    }

    /**
     * Get configured SLA targets in minutes for a given priority.
     *
     * @return array{first_response: int, resolution: int}
     */
    public function getTargetsForPriority(string $priority): array
    {
        $priority = strtolower($priority);

        $defaults = [
            'urgent' => ['first_response' => 60,   'resolution' => 240],   // 1 hour / 4 hours
            'high'   => ['first_response' => 120,  'resolution' => 480],   // 2 hours / 8 hours
            'medium' => ['first_response' => 240,  'resolution' => 1440],  // 4 hours / 24 hours (3 business days)
            'low'    => ['first_response' => 480,  'resolution' => 2880],  // 8 hours / 48 hours (6 business days)
        ];

        $fallback = $defaults[$priority] ?? $defaults['medium'];

        $firstResponse = (int) setting("sla_{$priority}_first_response", $fallback['first_response']);
        $resolution = (int) setting("sla_{$priority}_resolution", $fallback['resolution']);

        return [
            'first_response' => max(1, $firstResponse),
            'resolution'     => max(1, $resolution),
        ];
    }

    /**
     * Calculate first response deadline for a ticket.
     */
    public function calculateFirstResponseDeadline(Ticket $ticket, ?Carbon $fromTime = null): Carbon
    {
        $targets = $this->getTargetsForPriority($ticket->priority);
        $from = $fromTime ?? $ticket->created_at ?? now();

        return $this->calculateDeadline($from, $targets['first_response']);
    }

    /**
     * Calculate resolution deadline for a ticket.
     */
    public function calculateResolutionDeadline(Ticket $ticket, ?Carbon $fromTime = null): Carbon
    {
        $targets = $this->getTargetsForPriority($ticket->priority);
        $from = $fromTime ?? $ticket->created_at ?? now();

        return $this->calculateDeadline($from, $targets['resolution']);
    }
}

