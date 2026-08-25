<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RegistrationRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'student_id', 'client_operation_id', 'teacher_id', 'teacher_code_snapshot', 'requested_halaqa_id',
        'routing_mode', 'state', 'public_message', 'decision_note', 'decided_by_teacher_id',
        'submitted_at', 'decided_at', 'accepted_at', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'accepted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function requestedHalaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class, 'requested_halaqa_id');
    }

    public function decidedByTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_teacher_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(RegistrationRequestProfile::class, 'registration_request_id');
    }
}
