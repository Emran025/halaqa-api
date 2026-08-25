<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranEdition extends Model
{
    protected $fillable = ['code', 'name_ar', 'script_name', 'version', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function surahs(): HasMany
    {
        return $this->hasMany(QuranSurah::class, 'edition_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(QuranPage::class, 'edition_id');
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'edition_id');
    }
}
