<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mistake extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'tracking_detail_id', 'ayah_id', 'edition_id', 'word_index', 'mistake_type_id', 'source_role', 'note', 'created_by_user_id'];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(TrackingDetail::class, 'tracking_detail_id', 'uuid');
    }

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'ayah_id', 'id');
    }

    public function mistakeType(): BelongsTo
    {
        return $this->belongsTo(MistakeType::class, 'mistake_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
