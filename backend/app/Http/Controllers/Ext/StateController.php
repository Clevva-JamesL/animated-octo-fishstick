<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathResource;
use App\Http\Resources\StreamSessionResource;
use App\Support\TwitchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $channel = TwitchContext::channel($request);
        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);

        $recentDeaths = $channel->deaths()
            ->when($session, fn ($query) => $query->where('stream_session_id', $session->id))
            ->latest('died_at')
            ->limit(25)
            ->get();

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
            'recent_deaths' => DeathResource::collection($recentDeaths),
        ]);
    }
}
