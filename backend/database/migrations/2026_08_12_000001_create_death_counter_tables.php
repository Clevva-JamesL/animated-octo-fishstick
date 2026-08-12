<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_user_id')->unique();
            $table->boolean('allow_viewer_clips')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('stream_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('game')->nullable();
            $table->string('run')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'ended_at']);
        });

        Schema::create('deaths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stream_session_id')->constrained()->cascadeOnDelete();
            $table->string('game')->nullable();
            $table->string('run')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('died_at');
            $table->string('clip_url')->nullable();
            $table->string('clip_id')->nullable();
            $table->string('created_by_twitch_id')->nullable();
            $table->string('category_type')->nullable();
            $table->string('category_value')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'game']);
            $table->index(['channel_id', 'game', 'run']);
            $table->index(['stream_session_id', 'died_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deaths');
        Schema::dropIfExists('stream_sessions');
        Schema::dropIfExists('channels');
    }
};
