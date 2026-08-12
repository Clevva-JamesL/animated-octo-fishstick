<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\StreamSessionResource;
use App\Services\TwitchExtensionPubSub;
use App\Support\TwitchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function store(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $request->validate([
            'game' => ['nullable', 'string', 'max:120'],
            'run' => ['nullable', 'string', 'max:120'],
        ]);

        $channel = TwitchContext::channel($request);

        $session = DB::transaction(function () use ($channel, $validated) {
            $channel->streamSessions()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            return $channel->streamSessions()->create([
                'game' => $validated['game'] ?? null,
                'run' => $validated['run'] ?? null,
                'started_at' => now(),
            ]);
        });

        $pubSub->broadcastChannel($channel, 'session.started');

        return response()->json([
            'session' => new StreamSessionResource($session),
            'counts' => TwitchContext::counts($channel, $session),
        ], 201);
    }

    public function updateCurrent(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $request->validate([
            'game' => ['sometimes', 'nullable', 'string', 'max:120'],
            'run' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $channel = TwitchContext::channel($request);
        $session = TwitchContext::requireCurrentSession($channel);

        $session->fill($validated);
        $session->save();

        $pubSub->broadcastChannel($channel, 'session.updated');

        return response()->json([
            'session' => new StreamSessionResource($session->fresh()),
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
}
