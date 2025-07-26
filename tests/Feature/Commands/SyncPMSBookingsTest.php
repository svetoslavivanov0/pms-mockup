<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Jobs\SyncBookingJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncPMSBookingsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Cache::put('pms_last_sync', now()->subDays(2)->toDateTimeString());
    }

    #[Test]
    public function it_dispatches_jobs_for_each_booking()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    101,
                    102,
                ]
            ], 200),
        ]);

        $this->artisan('app:sync-bookings')
            ->expectsOutput('Found 2 booking(s) to sync.')
            ->expectsOutput('All batch jobs dispatched successfully.')
            ->assertSuccessful();

        Queue::assertPushed(SyncBookingJob::class, 2);
    }

    #[Test]
    public function it_uses_updated_after_option_when_provided()
    {
        Http::fake(function ($request) {
            $this->assertStringContainsString('updated_at.gt=2025-07-01', $request->url());
            return Http::response(['data' => [1]], 200);
        });

        $this->artisan('app:sync-bookings --updated-after=2025-07-01')
            ->expectsOutput('Found 1 booking(s) to sync.')
            ->assertSuccessful();

        Queue::assertPushed(SyncBookingJob::class, 1);
    }

    #[Test]
    public function it_handles_no_bookings_found()
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);

        $this->artisan('app:sync-bookings')
            ->expectsOutput('No new bookings found to sync.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_missing_data_key_in_api_response()
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->artisan('app:sync-bookings')
            ->expectsOutput('No bookings data found to sync.')
            ->assertExitCode(1);
    }
}
