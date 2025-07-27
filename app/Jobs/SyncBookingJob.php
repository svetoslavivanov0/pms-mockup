<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\BaseDTO;
use App\DTOs\Booking\BookingDTO;
use App\Exceptions\RateLimitExceededException;
use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\PMSService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBookingJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;
    use Dispatchable;
    use SerializesModels;

    public function __construct(private readonly int $bookingExternalId)
    {
    }

    public function handle(
        PMSService $pmsService,
        BookingRepository $bookingRepository,
        GuestRepository $guestRepository,
        RoomRepository $roomRepository,
        RoomTypeRepository $roomTypeRepository,
    ): void
    {
        try {
            /** @var BookingDTO $bookingDTO */
            $bookingDTO = $this->fetchWithRateLimiting(fn() => $pmsService->getBooking($this->bookingExternalId));
            $roomDTO = $this->fetchWithRateLimiting(fn() => $pmsService->getRoom($bookingDTO->roomId));
            $roomTypeDTO = $this->fetchWithRateLimiting(fn() => $pmsService->getRoomType($bookingDTO->roomTypeId));

            $guestDTOs = [];
            foreach ($bookingDTO->guestIds as $guestId) {
                $guestDTOs[] = $this->fetchWithRateLimiting(fn() => $pmsService->getGuest($guestId));
            }

            DB::transaction(function () use ($bookingDTO, $roomDTO, $roomTypeDTO, $guestDTOs, $bookingRepository, $guestRepository, $roomRepository, $roomTypeRepository) {
                $roomRepository->updateOrCreate($roomDTO);
                $roomTypeRepository->updateOrCreate($roomTypeDTO);

                $guestIds = [];
                foreach ($guestDTOs as $guestDTO) {
                    $guestIds[] = $guestRepository->updateOrCreate($guestDTO)->id;
                }

                $booking = $bookingRepository->updateOrCreate($bookingDTO);
                $bookingRepository->syncGuests($booking, $guestIds);
            });
        } catch (Exception $e) {
            Log::error('Failed to sync booking: ' . $e->getMessage(), [
                'booking_id' => $this->bookingExternalId,
                'exception' => $e,
            ]);

            $this->fail($e);
        }
    }

    protected function fetchWithRateLimiting(callable $apiCall): BaseDTO
    {
        try {
            return $apiCall();
        } catch (RateLimitExceededException $e) {
            Log::info('Rate limit exceeded, waiting before retry: ' . $e->getMessage(), [
                'booking_id' => $this->bookingExternalId,
                'retry_after' => $e->getRetryAfter(),
                'endpoint' => $e->getEndpoint(),
            ]);

            sleep($e->getRetryAfter());

            return $this->fetchWithRateLimiting($apiCall);
        }
    }
}
