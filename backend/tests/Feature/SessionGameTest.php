<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_game_spellings_share_one_catalog_row_and_count(): void
    {
        $this->postJson('/api/ext/sessions', [
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/sessions', [
            'game' => 'elden ring!',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [], $this->extHeaders())->assertCreated();

        $this->assertSame(1, Game::query()->count());
        $this->assertSame('eldenring', Game::query()->value('slug'));

        $state = $this->getJson('/api/ext/state', $this->extHeaders());
        $state->assertOk()
            ->assertJsonPath('counts.stream', 1)
            ->assertJsonPath('counts.game', 2)
            ->assertJsonPath('counts.run', 2)
            ->assertJsonPath('session.twitch_game_id', null)
            ->assertJsonCount(1, 'recent_games');
    }

    public function test_twitch_game_id_is_the_identity_not_the_display_name(): void
    {
        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'ELDEN RING',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'Elden Ring',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [], $this->extHeaders())->assertCreated();

        $this->assertSame(1, Game::query()->where('twitch_id', '512953')->count());

        $this->getJson('/api/ext/state', $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('counts.game', 2)
            ->assertJsonPath('session.twitch_game_id', '512953')
            ->assertJsonPath('session.game', 'Elden Ring');
    }
}
