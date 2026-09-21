<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'twitch_id',
        'name',
        'slug',
        'box_art_url',
    ];

    public function streamSessions(): HasMany
    {
        return $this->hasMany(StreamSession::class);
    }

    public function deaths(): HasMany
    {
        return $this->hasMany(Death::class);
    }

    public static function slugFor(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($name));

        if ($normalized === '') {
            return null;
        }

        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized) ?? '';

        return $slug === '' ? null : $slug;
    }
}
