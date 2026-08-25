<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'edition_id', 'name_ar', 'name_en', 'name_en_translation', 'number_of_ayahs', 'first_page_starts_at', 'revelation_type'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(QuranEdition::class, 'edition_id');
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'surah_id', 'id')->whereColumn('edition_id', 'quran_surahs.edition_id');
    }
}
