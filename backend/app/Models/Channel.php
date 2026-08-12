<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'twitch_user_id',
        'allow_viewer_clips',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'allow_viewer_clips' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function streamSessions(): HasMany
    {
        return $this->hasMany(StreamSession::class);
    }

    public function deaths(): HasMany
    {
        return $this->hasMany(Death::class);
    }
}
