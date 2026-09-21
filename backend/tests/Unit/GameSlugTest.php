<?php

namespace Tests\Unit;

use App\Models\Game;
use Tests\TestCase;

class GameSlugTest extends TestCase
{
    public function test_slug_strips_case_punctuation_and_spaces(): void
    {
        $this->assertSame('eldenring', Game::slugFor('Elden Ring'));
        $this->assertSame('eldenring', Game::slugFor('elden ring!'));
        $this->assertSame('eldenring', Game::slugFor(' ELDEN-RING '));
        $this->assertNull(Game::slugFor('   '));
        $this->assertNull(Game::slugFor(null));
    }
}
