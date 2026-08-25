<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAvailabilityProfile extends Model
{
    protected $primaryKey = 'student_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['student_id', 'timezone', 'preferred_session_duration_minutes'];

    protected function casts(): array
    {
        return ['preferred_session_duration_minutes' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(StudentAvailabilitySlot::class, 'student_id', 'student_id');
    }
}
