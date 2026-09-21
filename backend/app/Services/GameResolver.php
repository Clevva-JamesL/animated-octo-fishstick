<?php

namespace App\Services;

use App\Models\Game;

class GameResolver
{
    public function resolve(?string $twitchGameId, ?string $name, ?string $boxArtUrl = null): ?Game
    {
        $twitchGameId = $this->normalizeTwitchId($twitchGameId);
        $name = $this->normalizeName($name);
        $boxArtUrl = $this->normalizeName($boxArtUrl);

        if ($twitchGameId !== null) {
            $display = $name ?? 'Unknown game';
            $game = Game::query()->firstOrNew(['twitch_id' => $twitchGameId]);
            $game->name = $display;
            $game->slug = Game::slugFor($display);
            if ($boxArtUrl !== null) {
                $game->box_art_url = $boxArtUrl;
            }
            $game->save();

            return $game;
        }

        if ($name === null) {
            return null;
        }

        $slug = Game::slugFor($name);

        if ($slug === null) {
            return null;
        }

        $game = Game::query()
            ->whereNull('twitch_id')
            ->where('slug', $slug)
            ->first();

        if ($game !== null) {
            $game->name = $name;
            if ($boxArtUrl !== null) {
                $game->box_art_url = $boxArtUrl;
            }
            $game->save();

            return $game;
        }

        return Game::query()->create([
            'twitch_id' => null,
            'name' => $name,
            'slug' => $slug,
            'box_art_url' => $boxArtUrl,
        ]);
    }

    private function normalizeTwitchId(?string $twitchGameId): ?string
    {
        if ($twitchGameId === null) {
            return null;
        }

        $twitchGameId = trim($twitchGameId);

        return $twitchGameId === '' ? null : $twitchGameId;
    }

    private function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
