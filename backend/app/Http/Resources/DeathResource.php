<?php

namespace App\Http\Resources;

use App\Models\Death;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Death */
class DeathResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game' => $this->game,
            'run' => $this->run,
            'note' => $this->note,
            'died_at' => $this->died_at?->toIso8601String(),
            'clip_url' => $this->clip_url,
            'clip_id' => $this->clip_id,
            'category_type' => $this->category_type,
            'category_value' => $this->category_value,
        ];
    }
}
