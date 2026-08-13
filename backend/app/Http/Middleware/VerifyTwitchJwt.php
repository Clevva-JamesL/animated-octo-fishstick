<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyTwitchJwt
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldUseDevAuth($request)) {
            $request->attributes->set('twitch', [
                'channel_id' => $request->header('X-Twitch-Dev-Channel', 'dev-channel'),
                'user_id' => $request->header('X-Twitch-Dev-User', 'dev-user'),
                'opaque_user_id' => $request->header('X-Twitch-Dev-User', 'dev-user'),
                'role' => $request->header('X-Twitch-Dev-Role', 'broadcaster'),
                'is_unlinked' => false,
                'dev' => true,
            ]);

            return $next($request);
        }

        $token = $this->bearerToken($request);

        if ($token === null) {
            return response()->json(['message' => 'Missing Twitch extension token.'], 401);
        }

        $secret = config('twitch.extension_secret');

        if (! is_string($secret) || $secret === '') {
            return response()->json(['message' => 'Twitch extension secret is not configured.'], 500);
        }

        try {
            $decoded = JWT::decode($token, new Key(base64_decode($secret, true) ?: $secret, 'HS256'));
        } catch (Throwable $exception) {
            if (app()->environment('local')) {
                report($exception);
            }

            return response()->json(['message' => 'Invalid Twitch extension token.'], 401);
        }

        $request->attributes->set('twitch', [
            'channel_id' => $decoded->channel_id ?? null,
            'user_id' => $decoded->user_id ?? null,
            'opaque_user_id' => $decoded->opaque_user_id ?? null,
            'role' => $decoded->role ?? null,
            'is_unlinked' => (bool) ($decoded->is_unlinked ?? false),
            'dev' => false,
        ]);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }

    private function shouldUseDevAuth(Request $request): bool
    {
        if (! app()->environment('local') || ! config('twitch.allow_dev_auth')) {
            return false;
        }

        return $request->headers->has('X-Twitch-Dev-Channel')
            || $request->bearerToken() === 'dev';
    }
}
