<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranPage extends Model
{
    protected $fillable = ['edition_id', 'page_number', 'page_text'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(QuranEdition::class, 'edition_id');
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'page_number', 'page_number');
    }
}
