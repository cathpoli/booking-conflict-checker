<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Required for security and clean data handling.
     */
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
    ];

    /**
     * Relationship: A booking belongs to a user.
     * This is required to handle the "view own bookings" logic[cite: 55].
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}