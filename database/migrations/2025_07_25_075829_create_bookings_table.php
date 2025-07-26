<?php

declare(strict_types=1);

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
            $table->string('external_id')->unique();
            $table->timestamp('arrival_date');
            $table->timestamp('departure_date');
            $table->foreignId('room_id')
                ->references('external_id')
                ->on('rooms')
                ->onDelete('cascade');
            $table->foreignId('room_type_id')
                ->references('external_id')
                ->on('room_types')
                ->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('external_id');
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
