<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathResource;
use App\Models\Death;
use App\Services\TwitchExtensionPubSub;
use App\Support\TwitchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeathController extends Controller
{
    public function store(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $channel = TwitchContext::channel($request);
        $session = TwitchContext::requireCurrentSession($channel);

        $death = $channel->deaths()->create([
            'stream_session_id' => $session->id,
            'game' => $session->game,
            'run' => $session->run,
            'note' => $validated['note'] ?? null,
            'died_at' => now(),
            'created_by_twitch_id' => TwitchContext::actorId($request),
        ]);

        $counts = TwitchContext::counts($channel, $session);
        $pubSub->broadcastState($channel, $session, $counts, $death, 'death.created');

        return response()->json([
            'death' => new DeathResource($death),
            'counts' => $counts,
            'session' => [
                'id' => $session->id,
                'game' => $session->game,
                'run' => $session->run,
            ],
        ], 201);
    }

    public function update(Request $request, Death $death, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);

        if ($death->channel_id !== $channel->id) {
            throw new NotFoundHttpException('Death not found for this channel.');
        }

        $validated = $request->validate([
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'game' => ['sometimes', 'nullable', 'string', 'max:120'],
            'run' => ['sometimes', 'nullable', 'string', 'max:120'],
            'died_at' => ['sometimes', 'date'],
        ]);

        $death->fill($validated);
        $death->save();

        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);
        $pubSub->broadcastState($channel, $session, $counts, $death, 'death.updated');

        return response()->json([
            'death' => new DeathResource($death->fresh()),
            'counts' => $counts,
        ]);
    }
}
