<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id', 'name', 'certificate_type', 'certificate_type_other',
        'riwayah', 'issuing_place', 'issuing_date', 'storage_disk',
        'storage_path', 'mime_type', 'file_size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'issuing_date' => 'date:Y-m-d',
            'file_size_bytes' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
