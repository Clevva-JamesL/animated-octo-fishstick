<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamSession extends Model
{
    protected $fillable = [
        'channel_id',
        'game',
        'run',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function deaths(): HasMany
    {
        return $this->hasMany(Death::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
