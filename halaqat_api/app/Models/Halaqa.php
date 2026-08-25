<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Halaqa extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'teacher_id', 'name', 'description', 'gender', 'country', 'residence',
        'avatar_path', 'status', 'max_students', 'timezone',
    ];

    protected function casts(): array
    {
        return ['id' => 'string', 'max_students' => 'integer'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(HalaqaMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isTeacher()) {
            return $query->where('teacher_id', $user->id);
        }

        return $query->where('status', 'active')->where('gender', $user->gender)->where('country', $user->country);
    }
}
