<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranRangeUnit extends Model
{
    public $timestamps = false;

    protected $fillable = ['edition_id', 'unit_type_id', 'unit_index', 'from_surah_id', 'from_ayah_id', 'from_page', 'to_surah_id', 'to_ayah_id', 'to_page', 'gap'];

    protected function casts(): array
    {
        return ['id' => 'integer', 'edition_id' => 'integer', 'unit_type_id' => 'integer', 'unit_index' => 'integer', 'from_surah_id' => 'integer', 'from_ayah_id' => 'integer', 'from_page' => 'integer', 'to_surah_id' => 'integer', 'to_ayah_id' => 'integer', 'to_page' => 'integer', 'gap' => 'float'];
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(TrackingUnit::class, 'unit_type_id');
    }
}
