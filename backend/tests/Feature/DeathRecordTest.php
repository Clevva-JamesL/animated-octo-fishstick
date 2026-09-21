<?php

namespace Tests\Feature;

use App\Models\Death;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeathRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_undo_deletes_a_death_and_drops_counts(): void
    {
        $this->beginExtSession();

        $created = $this->postJson('/api/ext/deaths', ['note' => 'Malenia'], $this->extHeaders())
            ->assertCreated()
            ->json('death.id');

        $this->deleteJson('/api/ext/deaths/'.$created, [], $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('counts.stream', 0);

        $this->assertDatabaseMissing('deaths', ['id' => $created]);
    }

    public function test_cannot_delete_another_channel_death(): void
    {
        $this->beginExtSession('111');
        $id = $this->postJson('/api/ext/deaths', [], $this->extHeaders('111'))
            ->assertCreated()
            ->json('death.id');

        $this->beginExtSession('222');

        $this->deleteJson('/api/ext/deaths/'.$id, [], $this->extHeaders('222'))
            ->assertNotFound();

        $this->assertDatabaseHas('deaths', ['id' => $id]);
    }

    public function test_viewer_cannot_mutate_deaths(): void
    {
        $this->beginExtSession();
        $id = $this->postJson('/api/ext/deaths', [], $this->extHeaders())
            ->assertCreated()
            ->json('death.id');

        $viewer = $this->extHeaders('12345', 'viewer');

        $this->deleteJson('/api/ext/deaths/'.$id, [], $viewer)->assertForbidden();
        $this->patchJson('/api/ext/deaths/'.$id, ['note' => 'nope'], $viewer)->assertForbidden();
        $this->postJson('/api/ext/deaths/'.$id.'/clip', [
            'clip_url' => 'https://clips.twitch.tv/AmazonianEncouragingLyrebirdSwiftRage',
        ], $viewer)->assertForbidden();
    }

    public function test_patch_note_and_game_keeps_catalog_identity(): void
    {
        $this->beginExtSession();
        $id = $this->postJson('/api/ext/deaths', ['note' => 'fall'], $this->extHeaders())
            ->assertCreated()
            ->json('death.id');

        $this->patchJson('/api/ext/deaths/'.$id, [
            'note' => 'Malenia',
            'twitch_game_id' => '512953',
            'game' => 'ELDEN RING',
        ], $this->extHeaders())->assertOk();

        $death = Death::query()->findOrFail($id);
        $this->assertSame('Malenia', $death->note);
        $this->assertSame('512953', Game::query()->find($death->game_id)?->twitch_id);
        $this->assertSame('ELDEN RING', $death->game);
    }

    public function test_attach_and_remove_clip(): void
    {
        $this->beginExtSession();
        $id = $this->postJson('/api/ext/deaths', [], $this->extHeaders())
            ->assertCreated()
            ->json('death.id');

        $this->postJson('/api/ext/deaths/'.$id.'/clip', [
            'clip_url' => 'https://www.twitch.tv/someone/clip/AmazonianEncouragingLyrebirdSwiftRage?filter=clips',
        ], $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('death.clip_id', 'AmazonianEncouragingLyrebirdSwiftRage')
            ->assertJsonPath('death.clip_url', 'https://clips.twitch.tv/AmazonianEncouragingLyrebirdSwiftRage');

        $this->deleteJson('/api/ext/deaths/'.$id.'/clip', [], $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('death.clip_url', null)
            ->assertJsonPath('death.clip_id', null);
    }

    public function test_invalid_clip_url_is_rejected(): void
    {
        $this->beginExtSession();
        $id = $this->postJson('/api/ext/deaths', [], $this->extHeaders())
            ->assertCreated()
            ->json('death.id');

        $this->postJson('/api/ext/deaths/'.$id.'/clip', [
            'clip_url' => 'https://example.com/not-a-clip',
        ], $this->extHeaders())->assertUnprocessable();
    }

    public function test_state_groups_deaths_by_stream_game_and_run(): void
    {
        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', ['note' => 'first'], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', ['note' => 'second'], $this->extHeaders())->assertCreated();

        $this->getJson('/api/ext/state', $this->extHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'deaths.stream')
            ->assertJsonCount(2, 'deaths.game')
            ->assertJsonCount(2, 'deaths.run')
            ->assertJsonPath('deaths.stream.0.note', 'second')
            ->assertJsonPath('recent_deaths.0.note', 'second');
    }

    public function test_can_tag_a_death_as_boss_or_character(): void
    {
        $this->beginExtSession();

        $this->postJson('/api/ext/deaths', [
            'note' => 'phase 2',
            'category_type' => 'boss',
            'category_value' => 'Malenia',
        ], $this->extHeaders())
            ->assertCreated()
            ->assertJsonPath('death.category_type', 'boss')
            ->assertJsonPath('death.category_value', 'Malenia');

        $id = $this->postJson('/api/ext/deaths', [
            'category_type' => 'character',
            'category_value' => 'Samurai',
        ], $this->extHeaders())->assertCreated()->json('death.id');

        $this->patchJson('/api/ext/deaths/'.$id, [
            'category_type' => 'boss',
            'category_value' => 'Malenia',
        ], $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('death.category_type', 'boss')
            ->assertJsonPath('death.category_value', 'Malenia');

        $this->patchJson('/api/ext/deaths/'.$id, [
            'category_type' => null,
            'category_value' => null,
        ], $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('death.category_type', null)
            ->assertJsonPath('death.category_value', null);
    }

    public function test_incomplete_or_invalid_tag_is_rejected(): void
    {
        $this->beginExtSession();

        $this->postJson('/api/ext/deaths', [
            'category_type' => 'boss',
        ], $this->extHeaders())->assertUnprocessable();

        $this->postJson('/api/ext/deaths', [
            'category_value' => 'Malenia',
        ], $this->extHeaders())->assertUnprocessable();

        $this->postJson('/api/ext/deaths', [
            'category_type' => 'weapon',
            'category_value' => 'Rivers of Blood',
        ], $this->extHeaders())->assertUnprocessable();
    }

    public function test_state_groups_tagged_deaths_for_the_current_game(): void
    {
        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [
            'category_type' => 'boss',
            'category_value' => 'Margit',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [
            'category_type' => 'boss',
            'category_value' => 'Malenia',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/sessions', [
            'twitch_game_id' => '512953',
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders())->assertCreated();

        $this->postJson('/api/ext/deaths', [
            'note' => 'again',
            'category_type' => 'boss',
            'category_value' => 'Malenia',
        ], $this->extHeaders())->assertCreated();

        $this->getJson('/api/ext/state', $this->extHeaders())
            ->assertOk()
            ->assertJsonPath('counts.stream', 1)
            ->assertJsonPath('counts.game', 3)
            ->assertJsonCount(2, 'categories')
            ->assertJsonPath('categories.0.type', 'boss')
            ->assertJsonPath('categories.0.value', 'Malenia')
            ->assertJsonPath('categories.0.count', 2)
            ->assertJsonPath('categories.1.value', 'Margit')
            ->assertJsonPath('categories.1.count', 1);
    }

    private function beginExtSession(string $channel = '12345'): void
    {
        $this->postJson('/api/ext/sessions', [
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders($channel))->assertCreated();
    }
}
