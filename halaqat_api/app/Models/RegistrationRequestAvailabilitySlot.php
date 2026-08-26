<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequestAvailabilitySlot extends Model
{
    protected $fillable = [
        'registration_request_id', 'day_of_week', 'available_from', 'available_to', 'is_preferred',
    ];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'is_preferred' => 'boolean'];
    }

    public function requestAvailability(): BelongsTo
    {
        return $this->belongsTo(RegistrationRequestAvailability::class, 'registration_request_id');
    }
}
