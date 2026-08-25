<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MistakeType extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'mistake_types';

    protected $fillable = ['id', 'code', 'label_ar', 'label_en', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['id' => 'integer', 'is_active' => 'boolean'];
    }
}
