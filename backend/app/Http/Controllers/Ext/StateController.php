<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathCategoryGroupResource;
use App\Http\Resources\DeathResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\StreamSessionResource;
use App\Models\Channel;
use App\Models\Death;
use App\Models\StreamSession;
use App\Support\TwitchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $channel = TwitchContext::channel($request);
        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);

        $streamDeaths = $this->listedDeaths($channel, $session, 'stream');

        return response()->json([
            'ok' => true,
            'channel' => [
                'id' => $channel->id,
                'twitch_user_id' => $channel->twitch_user_id,
                'allow_viewer_clips' => $channel->allow_viewer_clips,
            ],
            'role' => TwitchContext::role($request),
            'user_id' => TwitchContext::actorId($request),
            'session' => $session ? new StreamSessionResource($session) : null,
            'counts' => $counts,
            'recent_games' => GameResource::collection($channel->recentCatalogGames()),
            'recent_deaths' => DeathResource::collection($streamDeaths),
            'deaths' => [
                'stream' => DeathResource::collection($streamDeaths),
                'game' => DeathResource::collection($this->listedDeaths($channel, $session, 'game')),
                'run' => DeathResource::collection($this->listedDeaths($channel, $session, 'run')),
            ],
            'categories' => DeathCategoryGroupResource::collection(
                $this->categoryGroups($channel, $session),
            ),
        ]);
    }

    /**
     * @return Collection<int, \App\Models\Death>
     */
    private function listedDeaths(Channel $channel, ?StreamSession $session, string $scope): Collection
    {
        if ($session === null) {
            return new Collection;
        }

        $query = $channel->deaths()->latest('died_at')->limit(25);

        return match ($scope) {
            'game' => $session->game_id
                ? $query->where('game_id', $session->game_id)->get()
                : $query->where('stream_session_id', $session->id)->get(),
            'run' => ($session->game_id && $session->run)
                ? $query->where('game_id', $session->game_id)->forRun($session->run)->get()
                : $query->where('stream_session_id', $session->id)->get(),
            default => $query->where('stream_session_id', $session->id)->get(),
        };
    }

    /**
     * @return list<array{type:string,value:string,count:int,deaths:Collection<int, \App\Models\Death>}>
     */
    private function categoryGroups(Channel $channel, ?StreamSession $session): array
    {
        if ($session === null) {
            return [];
        }

        $summaries = $this->taggedDeathsQuery($channel, $session)
            ->select('category_type', 'category_value')
            ->selectRaw('count(*) as death_count')
            ->selectRaw('max(died_at) as last_died_at')
            ->groupBy('category_type', 'category_value')
            ->orderByDesc('last_died_at')
            ->limit(15)
            ->get();

        return $summaries->map(function ($row) use ($channel, $session): array {
            $deaths = $this->taggedDeathsQuery($channel, $session)
                ->where('category_type', $row->category_type)
                ->where('category_value', $row->category_value)
                ->latest('died_at')
                ->limit(25)
                ->get();

            return [
                'type' => (string) $row->category_type,
                'value' => (string) $row->category_value,
                'count' => (int) $row->death_count,
                'deaths' => $deaths,
            ];
        })->all();
    }

    /**
     * @return Builder<\App\Models\Death>
     */
    private function taggedDeathsQuery(Channel $channel, StreamSession $session): Builder
    {
        $query = Death::query()
            ->where('channel_id', $channel->id)
            ->whereNotNull('category_type')
            ->whereNotNull('category_value');

        if ($session->game_id) {
            return $query->where('game_id', $session->game_id);
        }

        return $query->where('stream_session_id', $session->id);
    }
}
