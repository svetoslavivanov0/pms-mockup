<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\InvalidDataException;
use App\Exceptions\RateLimitExceededException;
use App\Services\PMSService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PMSServiceTest extends TestCase
{
    #[Test]
    public function it_fetches_booking_ids_successfully(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [['id' => 1], ['id' => 2]]], 200),
        ]);

        $service = new PMSService();

        $response = $service->getBookingIds();

        $this->assertIsArray($response);
        $this->assertEquals([['id' => 1], ['id' => 2]], $response['data']);
    }

    #[Test]
    public function test_it_throws_rate_limit_exception_on_429()
    {
        $this->expectException(RateLimitExceededException::class);

        Http::fake([
            '*' => Http::response([], 429, ['Retry-After' => 1]),
        ]);

        $service = new PMSService();
        $service->getBookingIds();
    }

    #[Test]
    public function test_getBooking_throws_exception_on_missing_fields()
    {
        Http::fake([
            '*' => Http::response([
                'external_id' => 123,
            ], 200),
        ]);

        $this->expectException(InvalidDataException::class);

        $service = new PMSService();
        $service->getBooking(123);
    }

    #[Test]
    public function test_getGuest_returns_valid_dto()
    {
        $id = rand();
        Http::fake([
            '*' => Http::response([
                'id' => $id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ], 200),
        ]);

        $service = new PMSService();
        $dto = $service->getGuest($id);

        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals('john@example.com', $dto->email);
    }

    #[Test]
    public function test_getRoom_returns_valid_dto()
    {
        $id = rand();
        $number = rand();
        $floor = rand();

        Http::fake([
            '*' => Http::response([
                'id' => $id,
                'number' => $number,
                'floor' => $floor,
            ], 200),
        ]);

        $service = new PMSService();
        $dto = $service->getRoom($id);

        $this->assertEquals($id, $dto->externalId);
        $this->assertEquals($number, $dto->number);
        $this->assertEquals($floor, $dto->floor);
    }

    #[Test]
    public function test_getRoomType_returns_valid_dto()
    {
        $id = rand();

        Http::fake([
            '*' => Http::response([
                'id' => $id,
                'name' => 'test',
                'description' => 'test',
            ], 200),
        ]);

        $service = new PMSService();
        $dto = $service->getRoomType($id);

        $this->assertEquals($id, $dto->externalId);
        $this->assertEquals('test', $dto->name);
        $this->assertEquals('test', $dto->description);
    }

    #[Test]
    public function test_getBooking_returns_valid_dto()
    {
        $id = rand();
        $roomId = rand();
        $roomType = rand();
        $date = Carbon::now()->subMonth();

        Http::fake([
            '*' => Http::response([
                'external_id' => $id,
                'room_id' => $roomId,
                'room_type_id' => $roomType,
                'status' => 'test',
                'notes' => 'test',
                'guest_ids' => [1],
                'arrival_date' => $date,
                'departure_date' => $date,
            ], 200),
        ]);

        $service = new PMSService();
        $dto = $service->getBooking($id);

        $this->assertEquals($id, $dto->externalId);
        $this->assertEquals($roomId, $dto->roomId);
        $this->assertEquals($roomType, $dto->roomTypeId);
        $this->assertEquals('test', $dto->status);
        $this->assertEquals('test', $dto->notes);
        $this->assertEquals([1], $dto->guestIds);
        $this->assertEquals($date, $dto->arrivalDate);
        $this->assertEquals($date, $dto->departureDate);
    }

    #[Test]
    public function test_get_fails_and_logs_on_error_response()
    {
        Http::fake([
            '*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch /rooms');

        $service = new PMSService();
        $service->getRoom(1);
    }
}
