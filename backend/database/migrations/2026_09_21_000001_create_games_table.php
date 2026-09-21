<?php

use App\Models\Death;
use App\Models\Game;
use App\Models\StreamSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug');
            $table->string('box_art_url', 512)->nullable();
            $table->timestamps();
        });

        $this->createCustomSlugIndex();

        Schema::table('stream_sessions', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('channel_id')->constrained()->nullOnDelete();
        });

        Schema::table('deaths', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('stream_session_id')->constrained()->nullOnDelete();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('deaths', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_id');
        });

        Schema::table('stream_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_id');
        });

        Schema::dropIfExists('games');
    }

    private function createCustomSlugIndex(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        DB::statement('create unique index games_custom_slug_unique on games (slug) where twitch_id is null');
    }

    private function backfill(): void
    {
        $names = StreamSession::query()
            ->whereNotNull('game')
            ->where('game', '!=', '')
            ->pluck('game')
            ->merge(
                Death::query()
                    ->whereNotNull('game')
                    ->where('game', '!=', '')
                    ->pluck('game')
            );

        $bySlug = [];

        foreach ($names as $name) {
            $trimmed = trim((string) $name);
            $slug = Game::slugFor($trimmed);

            if ($slug === null || isset($bySlug[$slug])) {
                continue;
            }

            $bySlug[$slug] = Game::query()->create([
                'twitch_id' => null,
                'name' => $trimmed,
                'slug' => $slug,
            ]);
        }

        if ($bySlug === []) {
            return;
        }

        foreach (StreamSession::query()->whereNotNull('game')->where('game', '!=', '')->cursor() as $session) {
            $slug = Game::slugFor($session->game);
            if ($slug !== null && isset($bySlug[$slug])) {
                $session->game_id = $bySlug[$slug]->id;
                $session->save();
            }
        }

        foreach (Death::query()->whereNotNull('game')->where('game', '!=', '')->cursor() as $death) {
            $slug = Game::slugFor($death->game);
            if ($slug !== null && isset($bySlug[$slug])) {
                $death->game_id = $bySlug[$slug]->id;
                $death->save();
            }
        }
    }
};
