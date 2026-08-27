<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMushafState extends Model
{
    protected $table = 'session_mushaf_states';

    protected $primaryKey = 'session_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['session_id', 'edition_id', 'page_number', 'surah_id', 'ayah_id', 'range_from_page', 'range_from_ayah_id', 'range_to_page', 'range_to_ayah_id', 'updated_by_user_id', 'version', 'last_client_operation_id'];

    protected function casts(): array
    {
        return ['edition_id' => 'integer', 'page_number' => 'integer', 'surah_id' => 'integer', 'ayah_id' => 'integer', 'range_from_page' => 'integer', 'range_from_ayah_id' => 'integer', 'range_to_page' => 'integer', 'range_to_ayah_id' => 'integer', 'version' => 'integer'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(QuranEdition::class, 'edition_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'ayah_id', 'id');
    }

    public function rangeFromAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'range_from_ayah_id', 'id');
    }

    public function rangeToAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'range_to_ayah_id', 'id');
    }
}
