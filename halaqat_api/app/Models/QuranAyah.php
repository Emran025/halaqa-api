<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranAyah extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'edition_id', 'surah_id', 'number_in_surah', 'text_uthmani', 'text_emlaey', 'page_number', 'juz_number', 'has_sajda'];

    protected function casts(): array
    {
        return ['id' => 'integer', 'edition_id' => 'integer', 'has_sajda' => 'boolean'];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(QuranEdition::class, 'edition_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_id', 'id');
    }
}
