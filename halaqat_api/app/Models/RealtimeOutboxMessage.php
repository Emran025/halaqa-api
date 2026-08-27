<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealtimeOutboxMessage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'session_id',
        'recipient_id',
        'event_type',
        'dedupe_key',
        'payload',
        'attempts',
        'last_attempted_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'payload' => 'array',
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
