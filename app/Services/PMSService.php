<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Booking\BookingDTO;
use App\DTOs\Guest\GuestDTO;
use App\DTOs\Room\RoomDTO;
use App\DTOs\RoomType\RoomTypeDTO;
use App\Exceptions\InvalidDataException;
use App\Exceptions\RateLimitExceededException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PMSService
{
    private string $baseUrl;
    private const int REQUESTS_PER_SECOND = 2;

    public function __construct()
    {
        $this->baseUrl = config('pms.base_url');
    }

    /**
     * @throws ConnectionException|RateLimitExceededException
     */
    public function getBookingIds(?string $updatedAfter = null): array
    {
        $query = [];

        if (!!$updatedAfter) {
            $query['updated_at.gt'] = $updatedAfter;
        }

        return $this->get('/bookings', $query) ?: [];
    }

    /**
     * @throws ConnectionException|RateLimitExceededException|InvalidDataException
     */
    public function getBooking(int $id): BookingDTO
    {
        $data = $this->get("/bookings/{$id}");

        $this->validateData(
            $data,
            ['external_id', 'room_id', 'room_type_id', 'status', 'guest_ids', 'arrival_date', 'departure_date']
        );

        return new BookingDTO($data);
    }

    /**
     * @throws ConnectionException|RateLimitExceededException|InvalidDataException
     */
    public function getRoom(int $id): RoomDTO
    {
        $data = $this->get("/rooms/{$id}");

        $this->validateData(
            $data,
            ['id', 'number', 'floor']
        );

        return new RoomDTO($data);
    }

    /**
     * @throws ConnectionException|RateLimitExceededException|InvalidDataException
     */
    public function getRoomType(int $id): RoomTypeDTO
    {
        $data = $this->get("/room-types/{$id}");

        $this->validateData(
            $data,
            ['id', 'name', 'description']
        );

        return new RoomTypeDTO($data);
    }

    /**
     * @throws ConnectionException|RateLimitExceededException|InvalidDataException
     */
    public function getGuest(int $id): GuestDTO
    {
        $data = $this->get("/guests/{$id}");

        $this->validateData(
            $data,
            ['id', 'first_name', 'last_name', 'email']
        );

        return new GuestDTO($data);
    }

    /**
     * @throws RateLimitExceededException|ConnectionException
     */
    protected function get(string $endpoint, array $query = [], int $attempt = 0): ?array
    {
        $this->rateLimit($endpoint);

        $response = Http::timeout(10)->get($this->baseUrl . $endpoint, $query);

        if ($response->status() === Response::HTTP_TOO_MANY_REQUESTS) {
            if ($attempt >= 10) {
                throw new Exception("Exceeded max retries due to rate limiting on {$endpoint}");
            }

            $retryAfter = (int) $response->header('Retry-After', 2);

            $backoffFactor = min(pow(2, $attempt), 30);
            $waitTime = $retryAfter * $backoffFactor;
            $nextAttempt = $attempt + 1;

            Log::warning("PMS API rate limit exceeded on {$endpoint}. Retrying after {$waitTime}s (attempt {$nextAttempt}/10).");

            throw new RateLimitExceededException(
                "Rate limit exceeded",
                $waitTime,
                $endpoint,
                $query,
                $nextAttempt
            );
        }

        if ($response->failed()) {
            Log::error("PMS API request failed for {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception("Failed to fetch {$endpoint}");
        }

        return $response->json() ?? [];
    }

    private function rateLimit(string $endpoint = null): void
    {
        $key = 'pms-api-rate-limit' . ($endpoint ? ':' . md5($endpoint) : '');

        RateLimiter::hit($key, 1);

        if (RateLimiter::tooManyAttempts($key, self::REQUESTS_PER_SECOND)) {
            $seconds = RateLimiter::availableIn($key);
            throw new RateLimitExceededException(
                "Rate limit exceeded for proactive rate limiting",
                $seconds,
                $endpoint
            );
        }
    }

    /**
     * @throws InvalidDataException
     */
    private function validateData(
        array $data,
        array $requiredFields,
    ): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidDataException("Missing required field: {$field}");
            }
        }
    }
}
