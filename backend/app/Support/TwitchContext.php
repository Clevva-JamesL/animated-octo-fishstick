<?php

namespace App\Support;

use App\Models\Channel;
use App\Models\Death;
use App\Models\StreamSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TwitchContext
{
    /**
     * @return array{channel_id:?string,user_id:?string,opaque_user_id:?string,role:?string,is_unlinked:bool,dev:bool}
     */
    public static function from(Request $request): array
    {
        /** @var array{channel_id:?string,user_id:?string,opaque_user_id:?string,role:?string,is_unlinked:bool,dev:bool} $twitch */
        $twitch = $request->attributes->get('twitch', []);

        if (($twitch['channel_id'] ?? null) === null || $twitch['channel_id'] === '') {
            throw ValidationException::withMessages([
                'channel_id' => 'Twitch channel id is missing from the extension token.',
            ]);
        }

        return $twitch;
    }

    public static function channel(Request $request): Channel
    {
        $twitch = self::from($request);

        return Channel::query()->firstOrCreate(
            ['twitch_user_id' => (string) $twitch['channel_id']],
            ['allow_viewer_clips' => true],
        );
    }

    public static function actorId(Request $request): ?string
    {
        $twitch = self::from($request);

        return $twitch['user_id'] ?? $twitch['opaque_user_id'] ?? null;
    }

    public static function role(Request $request): string
    {
        return (string) (self::from($request)['role'] ?? 'viewer');
    }

    public static function assertBroadcasterOrMod(Request $request): void
    {
        $role = self::role($request);

        if (! in_array($role, ['broadcaster', 'moderator'], true)) {
            throw new AccessDeniedHttpException('Broadcaster or moderator role required.');
        }
    }

    public static function currentSession(Channel $channel): ?StreamSession
    {
        return $channel->streamSessions()
            ->whereNull('ended_at')
            ->latest('id')
            ->first();
    }

    public static function requireCurrentSession(Channel $channel): StreamSession
    {
        $session = self::currentSession($channel);

        if ($session === null) {
            throw new NotFoundHttpException('No active stream session. Start one first.');
        }

        return $session;
    }

    /**
     * @return array{stream:int,game:int,run:int}
     */
    public static function counts(Channel $channel, ?StreamSession $session): array
    {
        if ($session === null) {
            return ['stream' => 0, 'game' => 0, 'run' => 0];
        }

        $stream = Death::query()
            ->where('stream_session_id', $session->id)
            ->count();

        $game = $session->game
            ? Death::query()
                ->where('channel_id', $channel->id)
                ->where('game', $session->game)
                ->count()
            : $stream;

        $run = ($session->game && $session->run)
            ? Death::query()
                ->where('channel_id', $channel->id)
                ->where('game', $session->game)
                ->where('run', $session->run)
                ->count()
            : $stream;

        return [
            'stream' => $stream,
            'game' => $game,
            'run' => $run,
        ];
    }
}
