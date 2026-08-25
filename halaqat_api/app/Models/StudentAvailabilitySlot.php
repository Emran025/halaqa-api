<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAvailabilitySlot extends Model
{
    protected $fillable = [
        'student_id', 'day_of_week', 'available_from', 'available_to', 'is_preferred',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_preferred' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
