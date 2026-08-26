<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationRequestAvailability extends Model
{
    protected $table = 'registration_request_availability';

    protected $primaryKey = 'registration_request_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['registration_request_id', 'timezone', 'preferred_session_duration_minutes'];

    protected function casts(): array
    {
        return ['preferred_session_duration_minutes' => 'integer'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RegistrationRequest::class, 'registration_request_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(RegistrationRequestAvailabilitySlot::class, 'registration_request_id');
    }
}
