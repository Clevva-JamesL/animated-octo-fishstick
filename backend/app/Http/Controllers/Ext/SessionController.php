<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\StreamSessionResource;
use App\Services\GameResolver;
use App\Services\TwitchExtensionPubSub;
use App\Support\TwitchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function __construct(private readonly GameResolver $games) {}

    public function store(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $this->validatedGameFields($request);

        $channel = TwitchContext::channel($request);
        $catalog = $this->games->resolve(
            $validated['twitch_game_id'] ?? null,
            $validated['game'] ?? null,
            $validated['box_art_url'] ?? null,
        );

        $session = DB::transaction(function () use ($channel, $validated, $catalog) {
            $channel->streamSessions()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            return $channel->streamSessions()->create([
                'game_id' => $catalog?->id,
                'game' => $catalog?->name,
                'run' => $validated['run'] ?? null,
                'started_at' => now(),
            ]);
        });

        $session->setRelation('catalogGame', $catalog);
        $pubSub->broadcastChannel($channel, 'session.started');

        return response()->json([
            'session' => new StreamSessionResource($session),
            'counts' => TwitchContext::counts($channel, $session),
        ], 201);
    }

    public function updateCurrent(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $this->validatedGameFields($request);

        $channel = TwitchContext::channel($request);
        $session = TwitchContext::requireCurrentSession($channel);

        if ($request->exists('twitch_game_id') || $request->exists('game')) {
            $catalog = $this->games->resolve(
                $validated['twitch_game_id'] ?? null,
                $validated['game'] ?? null,
                $validated['box_art_url'] ?? null,
            );
            $session->game_id = $catalog?->id;
            $session->game = $catalog?->name;
            $session->setRelation('catalogGame', $catalog);
        }

        if (array_key_exists('run', $validated)) {
            $session->run = $validated['run'];
        }

        $session->save();

        $pubSub->broadcastChannel($channel, 'session.updated');

        return response()->json([
            'session' => new StreamSessionResource($session->fresh(['catalogGame'])),
            'counts' => TwitchContext::counts($channel, $session->fresh()),
        ]);
    }

    public function endCurrent(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);
        $session = TwitchContext::requireCurrentSession($channel);
        $session->ended_at = now();
        $session->save();

        $pubSub->broadcastChannel($channel, 'session.ended');

        return response()->json([
            'session' => new StreamSessionResource($session),
            'counts' => TwitchContext::counts($channel, null),
        ]);
    }

    /**
     * @return array{game?:?string,run?:?string,twitch_game_id?:?string,box_art_url?:?string}
     */
    private function validatedGameFields(Request $request): array
    {
        return $request->validate([
            'game' => ['sometimes', 'nullable', 'string', 'max:120'],
            'run' => ['sometimes', 'nullable', 'string', 'max:120'],
            'twitch_game_id' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^\d+$/'],
            'box_art_url' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);
    }
}
