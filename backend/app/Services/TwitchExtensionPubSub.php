<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Death;
use App\Models\StreamSession;
use App\Support\TwitchContext;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TwitchExtensionPubSub
{
    /**
     * @param  array{stream:int,game:int,run:int}  $counts
     */
    public function broadcastState(
        Channel $channel,
        ?StreamSession $session,
        array $counts,
        ?Death $death = null,
        string $event = 'state.updated',
    ): void {
        $payload = [
            'event' => $event,
            'counts' => $counts,
            'session' => $session ? [
                'id' => $session->id,
                'game' => $session->game,
                'run' => $session->run,
                'started_at' => $session->started_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
            ] : null,
            'death' => $death ? [
                'id' => $death->id,
                'game' => $death->game,
                'run' => $death->run,
                'note' => $death->note,
                'died_at' => $death->died_at?->toIso8601String(),
                'clip_url' => $death->clip_url,
            ] : null,
        ];

        $this->send($channel->twitch_user_id, $payload);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function send(string $channelId, array $message): void
    {
        $clientId = config('twitch.extension_client_id');
        $secret = config('twitch.extension_secret');

        if (! is_string($clientId) || $clientId === '' || ! is_string($secret) || $secret === '') {
            Log::debug('Skipping Twitch PubSub broadcast; extension credentials not configured.');

            return;
        }

        $key = base64_decode($secret, true) ?: $secret;
        $now = time();

        $token = JWT::encode([
            'exp' => $now + 60,
            'user_id' => $channelId,
            'role' => 'external',
            'channel_id' => $channelId,
            'pubsub_perms' => [
                'send' => ['broadcast'],
            ],
        ], $key, 'HS256');

        try {
            $response = Http::withHeaders([
                'Client-Id' => $clientId,
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post('https://api.twitch.tv/helix/extensions/pubsub', [
                'message' => json_encode($message, JSON_THROW_ON_ERROR),
                'broadcaster_id' => $channelId,
                'target' => ['broadcast'],
            ]);

            if (! $response->successful()) {
                Log::warning('Twitch PubSub broadcast failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Twitch PubSub broadcast exception.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function broadcastChannel(Channel $channel, string $event = 'state.updated', ?Death $death = null): void
    {
        $session = TwitchContext::currentSession($channel);
        $counts = TwitchContext::counts($channel, $session);
        $this->broadcastState($channel, $session, $counts, $death, $event);
    }
}
