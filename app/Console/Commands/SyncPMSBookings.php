<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\RateLimitExceededException;
use App\Jobs\SyncBookingJob;
use App\Services\PMSService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

class SyncPMSBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-bookings {--updated-after=}';

    public function __construct(
        private readonly PMSService $syncService,
    )
    {
        parent::__construct();
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize bookings from the Property Management System (PMS) API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $updatedAfter = $this->option('updated-after') ??
                $this->getLastSyncTime() ??
                Carbon::now()->subMonth()->toDateTimeString();
            $bookingIds = $this->syncService->getBookingIds($updatedAfter);

            if (!isset($bookingIds['data'])) {
                $this->error("No bookings data found to sync.");
                return Command::FAILURE;
            }

            $bookingIds = collect($bookingIds['data']);

            $total = $bookingIds->count();

            if ($total === 0) {
                $this->info('No new bookings found to sync.');
                return Command::SUCCESS;
            }

            $this->info("Found {$total} booking(s) to sync.");

            $chunked = $bookingIds->chunk(100);

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $chunked->each(function ($chunk) use ($bar) {
                foreach ($chunk as $bookingId) {
                    SyncBookingJob::dispatch($bookingId);
                    $bar->advance();
                }
            });

            $bar->finish();

            $this->newLine(2);
            $this->info("All batch jobs dispatched successfully.");

            $this->updateLastSyncTime();

            return Command::SUCCESS;
        } catch (RateLimitExceededException $e) {
            $this->warn("Rate limit exceeded: " . $e->getMessage());
            $this->info("Retrying after {$e->getRetryAfter()} seconds...");

            sleep($e->getRetryAfter());
            return $this->handle();
        } catch (ConnectionException $e) {
            $this->error("Connection error: " . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error syncing bookings: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function updateLastSyncTime(): void
    {
        Cache::put('pms_last_sync', Carbon::now()->toDateTimeString(), 60 * 24 * 30);
    }


    private function getLastSyncTime(): ?string
    {
        return Cache::get('pms_last_sync');
    }
}
