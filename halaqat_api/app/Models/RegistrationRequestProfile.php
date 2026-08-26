<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequestProfile extends Model
{
    protected $primaryKey = 'registration_request_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'registration_request_id', 'gender', 'birth_date', 'country', 'city', 'residence',
        'phone', 'phone_zone', 'whatsapp_phone', 'whatsapp_zone', 'memorization_level',
        'review_level', 'memorized_juz_count', 'memorized_surah_ids', 'last_completed_unit',
        'previous_memorization_notes', 'stop_reasons', 'profile_bio',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'memorized_juz_count' => 'decimal:1',
            'memorized_surah_ids' => 'array',
            'last_completed_unit' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RegistrationRequest::class, 'registration_request_id');
    }
}
