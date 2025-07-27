<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\DTOs\Booking\BookingDTO;
use App\DTOs\Guest\GuestDTO;
use App\DTOs\Room\RoomDTO;
use App\DTOs\RoomType\RoomTypeDTO;
use App\Exceptions\RateLimitExceededException;
use App\Jobs\SyncBookingJob;
use App\Models\Booking;
use App\Models\Guest;
use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\PMSService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncBookingJobTest extends TestCase
{
    private int $bookingExternalId = 123;

    #[Test]
    public function test_handle_successfully_syncs_booking()
    {
        // Mock DTOs
        $bookingDTO = new BookingDTO([
            'external_id' => $this->bookingExternalId,
            'room_id' => 10,
            'room_type_id' => 20,
            'status' => 'confirmed',
            'guest_ids' => [1, 2],
            'arrival_date' => '2025-08-01',
            'departure_date' => '2025-08-05',
        ]);
        $roomDTO = new RoomDTO(['id' => 10, 'number' => '101', 'floor' => 1]);
        $roomTypeDTO = new RoomTypeDTO(['id' => 20, 'name' => 'Deluxe', 'description' => 'Deluxe room']);
        $guestDTO1 = new GuestDTO(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $guestDTO2 = new GuestDTO(['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);

        $pmsService = Mockery::mock(PMSService::class);
        $bookingRepository = Mockery::mock(BookingRepository::class);
        $guestRepository = Mockery::mock(GuestRepository::class);
        $roomRepository = Mockery::mock(RoomRepository::class);
        $roomTypeRepository = Mockery::mock(RoomTypeRepository::class);

        $pmsService->shouldReceive('getBooking')->with($this->bookingExternalId)->andReturn($bookingDTO);
        $pmsService->shouldReceive('getRoom')->with(10)->andReturn($roomDTO);
        $pmsService->shouldReceive('getRoomType')->with(20)->andReturn($roomTypeDTO);
        $pmsService->shouldReceive('getGuest')->with(1)->andReturn($guestDTO1);
        $pmsService->shouldReceive('getGuest')->with(2)->andReturn($guestDTO2);

        $roomRepository->shouldReceive('updateOrCreate')->once()->with($roomDTO);
        $roomTypeRepository->shouldReceive('updateOrCreate')->once()->with($roomTypeDTO);
        $guestRepository->shouldReceive('updateOrCreate')->twice()->andReturnUsing(function ($guestDTO) {
            $guest = new Guest();
            $guest->id = $guestDTO->externalId; // or $guestDTO->id, based on your DTO
            return $guest;
        });
        $bookingRepository->shouldReceive('updateOrCreate')->once()->with($bookingDTO)->andReturnUsing(function ($bookingDTO) {
            return new Booking();
        });
        $bookingRepository->shouldReceive('syncGuests')->once()->with(Mockery::type('object'), [1, 2]);


        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $jobPartial = Mockery::mock(SyncBookingJob::class . '[fetchWithRateLimiting]', [$this->bookingExternalId]);
        $jobPartial->shouldAllowMockingProtectedMethods();
        $jobPartial->shouldReceive('fetchWithRateLimiting')->andReturnUsing(function ($callable) {
            return $callable();
        });

        $jobPartial->handle($pmsService, $bookingRepository, $guestRepository, $roomRepository, $roomTypeRepository);

        $this->assertTrue(true);
    }

    #[Test]
    public function tests_handle_release_job_on_rate_limit(): void
    {
        $delay = 5;
        $exception = new RateLimitExceededException('hit', $delay, '/bookings/123', [], 1);

        $pms = Mockery::mock(PMSService::class);

        $pms->shouldReceive('getBooking')
            ->with($this->bookingExternalId)
            ->andThrow($exception);

        $bookingRepository = Mockery::mock(BookingRepository::class);
        $guestRepository = Mockery::mock(GuestRepository::class);
        $roomRepository = Mockery::mock(RoomRepository::class);
        $roomTypeRepository = Mockery::mock(RoomTypeRepository::class);

        $job = Mockery::mock(SyncBookingJob::class . '[fetchWithRateLimiting,release]', [$this->bookingExternalId]);

        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('fetchWithRateLimiting')
            ->once()
            ->andThrow($exception);

        $job->handle($pms, $bookingRepository, $guestRepository, $roomRepository, $roomTypeRepository);

        $this->assertTrue(true);
    }

    #[Test]
    public function test_handle_fails_job_on_generic_exception(): void
    {
        $bookingId = 123;
        $exceptionMessage = 'Some generic error';

        $pms = Mockery::mock(PMSService::class);
        $pms->shouldReceive('getBooking')
            ->with($bookingId)
            ->andThrow(new \Exception($exceptionMessage));

        $bookingRepository = Mockery::mock(BookingRepository::class);
        $guestRepository = Mockery::mock(GuestRepository::class);
        $roomRepository = Mockery::mock(RoomRepository::class);
        $roomTypeRepository = Mockery::mock(RoomTypeRepository::class);

        $job = Mockery::mock(SyncBookingJob::class . '[fail]', [$bookingId]);
        $job->shouldReceive('fail')->once()->withArgs(function ($e) use ($exceptionMessage) {
            return $e instanceof \Exception && $e->getMessage() === $exceptionMessage;
        });

        $job->handle($pms, $bookingRepository, $guestRepository, $roomRepository, $roomTypeRepository);

        $this->assertTrue(true);
    }
}
