<?php

namespace App\Http\Controllers\Ext;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array{channel_id:?string,user_id:?string,opaque_user_id:?string,role:?string,is_unlinked:bool,dev:bool} $twitch */
        $twitch = $request->attributes->get('twitch', []);

        return response()->json([
            'ok' => true,
            'channel_id' => $twitch['channel_id'] ?? null,
            'role' => $twitch['role'] ?? null,
            'user_id' => $twitch['user_id'] ?? $twitch['opaque_user_id'] ?? null,
            'message' => 'Extension backend is reachable. Domain models come next.',
        ]);
    }
}
