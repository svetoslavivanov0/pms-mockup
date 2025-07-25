<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'external_id',
        'arrival_date',
        'departure_date',
        'room_id',
        'room_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'arrival_date' => 'datetime',
        'departure_date' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type');
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class);
    }
}
