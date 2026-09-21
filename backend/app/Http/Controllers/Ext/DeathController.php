<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathResource;
use App\Models\Channel;
use App\Models\Death;
use App\Services\GameResolver;
use App\Services\TwitchExtensionPubSub;
use App\Support\DeathCategory;
use App\Support\TwitchClipUrl;
use App\Support\TwitchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeathController extends Controller
{
    public function __construct(private readonly GameResolver $games) {}

    public function store(Request $request, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            ...DeathCategory::rules(),
        ]);

        [$categoryType, $categoryValue] = DeathCategory::pair(
            $validated['category_type'] ?? null,
            $validated['category_value'] ?? null,
        );

        $channel = TwitchContext::channel($request);
        $session = TwitchContext::requireCurrentSession($channel);

        $death = $channel->deaths()->create([
            'stream_session_id' => $session->id,
            'game_id' => $session->game_id,
            'game' => $session->game,
            'run' => $session->run,
            'note' => $validated['note'] ?? null,
            'category_type' => $categoryType,
            'category_value' => $categoryValue,
            'died_at' => now(),
            'created_by_twitch_id' => TwitchContext::actorId($request),
        ]);

        return $this->payload($channel, $death, $pubSub, 'death.created', 201);
    }

    public function update(Request $request, Death $death, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);
        $this->assertDeathOnChannel($death, $channel);

        $validated = $request->validate([
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'game' => ['sometimes', 'nullable', 'string', 'max:120'],
            'run' => ['sometimes', 'nullable', 'string', 'max:120'],
            'died_at' => ['sometimes', 'date'],
            'twitch_game_id' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^\d+$/'],
            'box_art_url' => ['sometimes', 'nullable', 'string', 'max:512'],
            ...DeathCategory::rules(),
        ]);

        if ($request->exists('twitch_game_id') || $request->exists('game')) {
            $catalog = $this->games->resolve(
                $validated['twitch_game_id'] ?? null,
                $validated['game'] ?? null,
                $validated['box_art_url'] ?? null,
            );
            $death->game_id = $catalog?->id;
            $death->game = $catalog?->name;
        }

        if (array_key_exists('run', $validated)) {
            $death->run = $validated['run'];
        }

        if (array_key_exists('note', $validated)) {
            $death->note = $validated['note'];
        }

        if (array_key_exists('died_at', $validated)) {
            $death->died_at = $validated['died_at'];
        }

        if ($request->exists('category_type') || $request->exists('category_value')) {
            [$categoryType, $categoryValue] = DeathCategory::pair(
                $validated['category_type'] ?? null,
                $validated['category_value'] ?? null,
            );
            $death->category_type = $categoryType;
            $death->category_value = $categoryValue;
        }

        $death->save();

        return $this->payload($channel, $death->fresh(), $pubSub, 'death.updated');
    }

    public function destroy(Request $request, Death $death, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);
        $this->assertDeathOnChannel($death, $channel);

        $death->delete();

        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);
        $pubSub->broadcastState($channel, $session, $counts, null, 'death.deleted');

        return response()->json([
            'ok' => true,
            'counts' => $counts,
        ]);
    }

    public function attachClip(Request $request, Death $death, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);
        $this->assertDeathOnChannel($death, $channel);

        $validated = $request->validate([
            'clip_url' => ['required', 'string', 'max:512'],
        ]);

        $parsed = TwitchClipUrl::parse($validated['clip_url']);

        if ($parsed === null) {
            throw ValidationException::withMessages([
                'clip_url' => 'Paste a Twitch clip URL (clips.twitch.tv or twitch.tv/…/clip/…).',
            ]);
        }

        $death->clip_id = $parsed['id'];
        $death->clip_url = $parsed['url'];
        $death->save();

        return $this->payload($channel, $death->fresh(), $pubSub, 'death.updated');
    }

    public function detachClip(Request $request, Death $death, TwitchExtensionPubSub $pubSub): JsonResponse
    {
        TwitchContext::assertBroadcasterOrMod($request);

        $channel = TwitchContext::channel($request);
        $this->assertDeathOnChannel($death, $channel);

        $death->clip_id = null;
        $death->clip_url = null;
        $death->save();

        return $this->payload($channel, $death->fresh(), $pubSub, 'death.updated');
    }

    private function assertDeathOnChannel(Death $death, Channel $channel): void
    {
        if ($death->channel_id !== $channel->id) {
            throw new NotFoundHttpException('Death not found for this channel.');
        }
    }

    private function payload(
        Channel $channel,
        Death $death,
        TwitchExtensionPubSub $pubSub,
        string $event,
        int $status = 200,
    ): JsonResponse {
        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);
        $pubSub->broadcastState($channel, $session, $counts, $death, $event);

        return response()->json([
            'death' => new DeathResource($death),
            'counts' => $counts,
        ], $status);
    }
}
