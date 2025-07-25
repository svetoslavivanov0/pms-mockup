<?php

use App\Enums\Booking\BookingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->timestamp('arrival_date');
            $table->timestamp('departure_date');
            $table->foreignId('room_id')
                ->references('id')
                ->on('rooms')
                ->onDelete('cascade');
            $table->foreignId('room_type')
                ->references('id')
                ->on('room_types')
                ->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
