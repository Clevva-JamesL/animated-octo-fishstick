<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\StreamSessionResource;
use App\Models\Channel;
use App\Models\StreamSession;
use App\Support\TwitchContext;
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
                ? $query->where('game_id', $session->game_id)->where('run', $session->run)->get()
                : $query->where('stream_session_id', $session->id)->get(),
            default => $query->where('stream_session_id', $session->id)->get(),
        };
    }
}
