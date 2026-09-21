<?php

namespace Tests\Unit;

use App\Support\TwitchClipUrl;
use Tests\TestCase;

class TwitchClipUrlTest extends TestCase
{
    public function test_parses_clips_twitch_tv_and_channel_clip_urls(): void
    {
        $slug = 'AmazonianEncouragingLyrebirdSwiftRage';
        $canonical = 'https://clips.twitch.tv/'.$slug;

        $this->assertSame(
            ['id' => $slug, 'url' => $canonical],
            TwitchClipUrl::parse('https://clips.twitch.tv/'.$slug),
        );
        $this->assertSame(
            ['id' => $slug, 'url' => $canonical],
            TwitchClipUrl::parse('https://www.twitch.tv/someuser/clip/'.$slug.'?filter=clips'),
        );
        $this->assertSame(
            ['id' => $slug, 'url' => $canonical],
            TwitchClipUrl::parse('https://clips.twitch.tv/embed?clip='.$slug),
        );
        $this->assertSame(
            ['id' => $slug, 'url' => $canonical],
            TwitchClipUrl::parse('clips.twitch.tv/'.$slug),
        );
    }

    public function test_rejects_non_clip_urls(): void
    {
        $this->assertNull(TwitchClipUrl::parse(null));
        $this->assertNull(TwitchClipUrl::parse(''));
        $this->assertNull(TwitchClipUrl::parse('https://twitch.tv/someuser'));
        $this->assertNull(TwitchClipUrl::parse('https://example.com/clip/Nope'));
        $this->assertNull(TwitchClipUrl::parse('not a url'));
    }
}
