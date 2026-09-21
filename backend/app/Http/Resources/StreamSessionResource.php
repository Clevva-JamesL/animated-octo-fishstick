<?php

namespace App\Http\Resources;

use App\Models\StreamSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StreamSession */
class StreamSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $catalog = $this->catalogGame;

        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'twitch_game_id' => $catalog?->twitch_id,
            'game' => $this->game,
            'box_art_url' => $catalog?->box_art_url,
            'run' => $this->run,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'active' => $this->ended_at === null,
        ];
    }
}
