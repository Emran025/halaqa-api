<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingType extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label_ar', 'label_en', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['id' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
