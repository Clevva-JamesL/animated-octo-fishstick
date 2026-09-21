<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class DeathCategoryGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, \App\Models\Death> $deaths */
        $deaths = $this->resource['deaths'];

        return [
            'type' => $this->resource['type'],
            'value' => $this->resource['value'],
            'count' => $this->resource['count'],
            'deaths' => DeathResource::collection($deaths),
        ];
    }
}
