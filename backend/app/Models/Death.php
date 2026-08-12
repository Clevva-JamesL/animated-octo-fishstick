<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Death extends Model
{
    protected $fillable = [
        'channel_id',
        'stream_session_id',
        'game',
        'run',
        'note',
        'died_at',
        'clip_url',
        'clip_id',
        'created_by_twitch_id',
        'category_type',
        'category_value',
    ];

    protected function casts(): array
    {
        return [
            'died_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function streamSession(): BelongsTo
    {
        return $this->belongsTo(StreamSession::class);
    }
}
