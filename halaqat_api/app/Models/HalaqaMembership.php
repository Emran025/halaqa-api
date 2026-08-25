<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalaqaMembership extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'halaqa_id', 'student_id', 'status', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
