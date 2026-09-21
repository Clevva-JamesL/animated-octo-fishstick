<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @return Collection<int, Game>
     */
    public function recentCatalogGames(int $limit = 8): Collection
    {
        $sessionIds = $this->streamSessions()
            ->whereNotNull('game_id')
            ->latest('id')
            ->pluck('game_id');

        $deathIds = $this->deaths()
            ->whereNotNull('game_id')
            ->latest('id')
            ->pluck('game_id');

        $ids = $sessionIds->concat($deathIds)->unique()->values()->take($limit);

        if ($ids->isEmpty()) {
            return new Collection;
        }

        $games = Game::query()->whereIn('id', $ids)->get()->keyBy('id');

        return new Collection(
            $ids->map(fn ($id) => $games->get($id))->filter()->values()->all()
        );
    }
}
