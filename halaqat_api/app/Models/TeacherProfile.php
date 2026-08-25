<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'teacher_code', 'qualification', 'experience_years',
        'bio', 'available_time', 'max_halaqas',
    ];

    protected function casts(): array
    {
        return ['experience_years' => 'integer', 'max_halaqas' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TeacherDocument::class, 'teacher_id', 'user_id');
    }
}
