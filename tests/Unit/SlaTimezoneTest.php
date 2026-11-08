<?php

namespace Tests\Unit;

use App\Enums\SlaStatus;
use App\Models\Ticket;
use App\Services\BusinessHoursService;
use App\Services\SlaCalculationService;
use App\Services\SlaService;
use Carbon\Carbon;
use Tests\TestCase;

class SlaTimezoneTest extends TestCase
{
    protected SlaCalculationService $calculationService;
    protected BusinessHoursService $businessHours;
    protected SlaService $slaService;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::forever('app_settings', [
            'business_timezone'         => 'Asia/Dhaka',
            'timezone'                  => 'Asia/Dhaka',
            'business_working_days'     => '0,1,2,3,4',
            'business_start_time'       => '09:00',
            'business_end_time'         => '17:00',
            'business_holidays'         => '',
            'sla_urgent_first_response' => 60,
            'sla_urgent_resolution'     => 240,
            'sla_high_first_response'   => 120,
            'sla_high_resolution'       => 480,
            'sla_medium_first_response' => 240,
            'sla_medium_resolution'     => 1440,
            'sla_low_first_response'    => 480,
            'sla_low_resolution'        => 2880,
        ]);

        $this->businessHours = app(BusinessHoursService::class);
        $this->calculationService = app(SlaCalculationService::class);
        $this->slaService = app(SlaService::class);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Cache::forget('app_settings');
        parent::tearDown();
    }

    /**
     * SLA Deadlines are calculated in business timezone (Asia/Dhaka) and stored/returned in UTC.
     */
    public function test_sla_deadline_calculated_in_dhaka_and_stored_in_utc(): void
    {
        // Sunday 10:00 AM in Asia/Dhaka (+06:00) = Sunday 04:00 AM in UTC
        $dhakaStart = Carbon::create(2026, 11, 8, 10, 0, 0, 'Asia/Dhaka');
        $utcStart = $dhakaStart->copy()->setTimezone('UTC');

        // Working hours: 09:00 to 17:00, working days: Sun-Thu (0,1,2,3,4)
        $config = [
            'timezone'     => 'Asia/Dhaka',
            'working_days' => [7, 1, 2, 3, 4],
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'holidays'     => [],
        ];

        // 60 minutes duration (Urgent first response target)
        $deadlineUtc = $this->calculationService->calculateDeadline($utcStart, 60, $config);

        // Result MUST be returned in UTC for database storage
        $this->assertSame('UTC', $deadlineUtc->getTimezone()->getName());
        $this->assertSame('2026-11-08 05:00:00', $deadlineUtc->format('Y-m-d H:i:s'));

        // When converted to business timezone (Dhaka), it must match 11:00 AM Dhaka
        $deadlineDhaka = $deadlineUtc->copy()->setTimezone('Asia/Dhaka');
        $this->assertSame('2026-11-08 11:00:00', $deadlineDhaka->format('Y-m-d H:i:s'));
    }

    /**
     * SLA Deadline spanning across end-of-day rolls over to next business day morning.
     */
    public function test_sla_deadline_rollover_to_next_business_day(): void
    {
        // Sunday 16:30 (4:30 PM) Dhaka = Sunday 10:30 AM UTC
        // 30 minutes remaining in Sunday business hours (closes at 17:00)
        $dhakaStart = Carbon::create(2026, 11, 8, 16, 30, 0, 'Asia/Dhaka');
        $utcStart = $dhakaStart->copy()->setTimezone('UTC');

        $config = [
            'timezone'     => 'Asia/Dhaka',
            'working_days' => [7, 1, 2, 3, 4],
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'holidays'     => [],
        ];

        // 60 minutes duration: 30 mins used today (to 17:00), remaining 30 mins roll over to Monday 09:30 AM Dhaka
        $deadlineUtc = $this->calculationService->calculateDeadline($utcStart, 60, $config);

        // Monday 09:30 AM Dhaka = Monday 03:30 AM UTC
        $this->assertSame('UTC', $deadlineUtc->getTimezone()->getName());
        $this->assertSame('2026-11-09 03:30:00', $deadlineUtc->format('Y-m-d H:i:s'));

        $deadlineDhaka = $deadlineUtc->copy()->setTimezone('Asia/Dhaka');
        $this->assertSame('2026-11-09 09:30:00', $deadlineDhaka->format('Y-m-d H:i:s'));
    }

    /**
     * Ticket created on non-working day (weekend: Friday in Dhaka) advances to next working day.
     */
    public function test_sla_deadline_created_on_weekend_advances_to_sunday(): void
    {
        // Friday 14:00 (2:00 PM) Dhaka = Friday 08:00 AM UTC
        // Friday and Saturday are non-working days in Dhaka
        $dhakaStart = Carbon::create(2026, 11, 6, 14, 0, 0, 'Asia/Dhaka'); // Nov 6, 2026 is Friday
        $utcStart = $dhakaStart->copy()->setTimezone('UTC');

        $config = [
            'timezone'     => 'Asia/Dhaka',
            'working_days' => [7, 1, 2, 3, 4],
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'holidays'     => [],
        ];

        // 60 minutes: advances past Friday & Saturday to Sunday 09:00 AM Dhaka, then adds 60 min -> Sunday 10:00 AM Dhaka
        $deadlineUtc = $this->calculationService->calculateDeadline($utcStart, 60, $config);

        // Sunday 10:00 AM Dhaka = Sunday 04:00 AM UTC
        $this->assertSame('UTC', $deadlineUtc->getTimezone()->getName());
        $this->assertSame('2026-11-08 04:00:00', $deadlineUtc->format('Y-m-d H:i:s'));

        $deadlineDhaka = $deadlineUtc->copy()->setTimezone('Asia/Dhaka');
        $this->assertSame('2026-11-08 10:00:00', $deadlineDhaka->format('Y-m-d H:i:s'));
    }

    /**
     * String start time (from DB or raw input) is parsed as UTC and correctly converted to business timezone.
     */
    public function test_string_start_time_parsed_as_utc_and_not_misinterpreted_as_local(): void
    {
        $config = [
            'timezone'     => 'Asia/Dhaka',
            'working_days' => [7, 1, 2, 3, 4],
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'holidays'     => [],
        ];

        // 04:00:00 UTC = 10:00:00 Dhaka
        $utcString = '2026-11-08 04:00:00';
        $deadlineUtc = $this->calculationService->calculateDeadline($utcString, 60, $config);

        // Must be 05:00:00 UTC (11:00:00 Dhaka), NOT 04:00:00 UTC
        $this->assertSame('2026-11-08 05:00:00', $deadlineUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-08 11:00:00', $deadlineUtc->copy()->setTimezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
    }

    /**
     * Timezone display helpers properly convert UTC timestamps to Asia/Dhaka.
     */
    public function test_dhaka_display_helpers_format_consistently(): void
    {
        $utcTimestamp = Carbon::parse('2026-11-08 04:00:00', 'UTC');

        // to_business_timezone() converts to Asia/Dhaka
        $dhakaCarbon = to_business_timezone($utcTimestamp);
        $this->assertNotNull($dhakaCarbon);
        $this->assertSame('Asia/Dhaka', $dhakaCarbon->getTimezone()->getName());
        $this->assertSame('10:00 AM', $dhakaCarbon->format('h:i A'));
        $this->assertSame('Nov 08, 2026 10:00 AM', $dhakaCarbon->format('M d, Y h:i A'));

        // format_datetime() returns formatted string directly in business timezone
        $this->assertSame('Nov 08, 2026 10:00 AM', format_datetime($utcTimestamp));
        $this->assertSame('Nov 08, 2026 at 10:00 AM', format_datetime($utcTimestamp, 'M d, Y \a\t h:i A'));

        // Idempotency: passing an already-converted Carbon does NOT double-shift
        $doubleConverted = to_business_timezone($dhakaCarbon);
        $this->assertSame('10:00 AM', $doubleConverted->format('h:i A'));

        // Null safety
        $this->assertNull(to_business_timezone(null));
        $this->assertSame('', format_datetime(null));
    }

    /**
     * Ticket model business timezone accessors return Dhaka times while preserving UTC attributes.
     */
    public function test_ticket_model_business_accessors_and_utc_storage(): void
    {
        $ticket = new Ticket();
        $ticket->setRawAttributes([
            'created_at'            => '2026-11-08 04:00:00',
            'first_response_due_at' => '2026-11-08 05:00:00',
            'resolution_due_at'     => '2026-11-08 08:00:00',
        ]);

        // Underlying model attributes MUST remain in UTC
        $this->assertSame('UTC', $ticket->created_at->getTimezone()->getName());
        $this->assertSame('04:00 AM', $ticket->created_at->format('h:i A'));

        $this->assertSame('UTC', $ticket->first_response_due_at->getTimezone()->getName());
        $this->assertSame('05:00 AM', $ticket->first_response_due_at->format('h:i A'));

        // Business accessors MUST return Asia/Dhaka
        $this->assertSame('Asia/Dhaka', $ticket->created_at_business->getTimezone()->getName());
        $this->assertSame('10:00 AM', $ticket->created_at_business->format('h:i A'));

        $this->assertSame('Asia/Dhaka', $ticket->first_response_due_at_business->getTimezone()->getName());
        $this->assertSame('11:00 AM', $ticket->first_response_due_at_business->format('h:i A'));

        $this->assertSame('Asia/Dhaka', $ticket->resolution_due_at_business->getTimezone()->getName());
        $this->assertSame('02:00 PM', $ticket->resolution_due_at_business->format('h:i A'));
    }

    /**
     * SLA status evaluation behaves consistently regardless of whether $now is UTC or Dhaka.
     */
    public function test_sla_status_checks_across_timezones(): void
    {
        $ticket = new Ticket();
        $ticket->setRawAttributes([
            'priority'              => 'urgent',
            'created_at'            => '2026-11-08 04:00:00',
            'first_response_due_at' => '2026-11-08 05:00:00',
            'resolution_due_at'     => '2026-11-08 08:00:00',
        ]);

        // 10:15 AM Dhaka = 04:15 AM UTC -> ON TRACK (45 mins remaining)
        $nowUtc = Carbon::parse('2026-11-08 04:15:00', 'UTC');
        $nowDhaka = Carbon::parse('2026-11-08 10:15:00', 'Asia/Dhaka');

        $this->assertSame(SlaStatus::ON_TRACK, $this->slaService->getFirstResponseStatus($ticket, $nowUtc));
        $this->assertSame(SlaStatus::ON_TRACK, $this->slaService->getFirstResponseStatus($ticket, $nowDhaka));

        // 10:55 AM Dhaka = 04:55 AM UTC -> APPROACHING BREACH (5 mins <= 12 mins threshold)
        $approachingDhaka = Carbon::parse('2026-11-08 10:55:00', 'Asia/Dhaka');
        $this->assertSame(SlaStatus::APPROACHING_BREACH, $this->slaService->getFirstResponseStatus($ticket, $approachingDhaka));

        // 11:05 AM Dhaka = 05:05 AM UTC -> BREACHED (overdue by 5 mins)
        $breachedDhaka = Carbon::parse('2026-11-08 11:05:00', 'Asia/Dhaka');
        $this->assertSame(SlaStatus::BREACHED, $this->slaService->getFirstResponseStatus($ticket, $breachedDhaka));

        // When staff responds on time
        $ticket->first_responded_at = Carbon::parse('2026-11-08 04:30:00', 'UTC');
        $this->assertSame(SlaStatus::COMPLETED, $this->slaService->getFirstResponseStatus($ticket, $breachedDhaka));
    }

    /**
     * Dynamic business timezone change adjusts display without modifying UTC database values.
     */
    public function test_dynamic_business_timezone_adaptation(): void
    {
        $utcCreated = Carbon::parse('2026-11-08 14:00:00', 'UTC');

        // With Asia/Dhaka (+06:00), 14:00 UTC = 20:00 (8:00 PM) Dhaka
        $this->assertSame('08:00 PM', format_datetime($utcCreated, 'h:i A'));

        // Change configured business timezone to America/New_York (UTC-5 in standard time)
        \Illuminate\Support\Facades\Cache::forever('app_settings', [
            'business_timezone' => 'America/New_York',
        ]);

        // 14:00 UTC = 09:00 AM New York
        $this->assertSame('America/New_York', business_timezone());
        $this->assertSame('09:00 AM', format_datetime($utcCreated, 'h:i A'));

        // Change to Europe/London (UTC+0 in standard time)
        \Illuminate\Support\Facades\Cache::forever('app_settings', [
            'business_timezone' => 'Europe/London',
        ]);

        // 14:00 UTC = 02:00 PM London
        $this->assertSame('Europe/London', business_timezone());
        $this->assertSame('02:00 PM', format_datetime($utcCreated, 'h:i A'));
    }
}
