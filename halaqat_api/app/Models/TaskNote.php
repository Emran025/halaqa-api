<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskNote extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'session_task_id', 'author_id', 'note', 'ayah_id', 'edition_id', 'word_index'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(SessionTask::class, 'session_task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
