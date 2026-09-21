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

    private function beginExtSession(string $channel = '12345'): void
    {
        $this->postJson('/api/ext/sessions', [
            'game' => 'Elden Ring',
            'run' => 'RL1',
        ], $this->extHeaders($channel))->assertCreated();
    }
}
