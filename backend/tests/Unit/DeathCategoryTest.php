<?php

namespace Tests\Unit;

use App\Support\DeathCategory;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeathCategoryTest extends TestCase
{
    public function test_empty_pair_is_null(): void
    {
        $this->assertSame([null, null], DeathCategory::pair(null, null));
        $this->assertSame([null, null], DeathCategory::pair('  ', ''));
    }

    public function test_normalizes_type_and_trims_value(): void
    {
        $this->assertSame(['boss', 'Malenia'], DeathCategory::pair('Boss', '  Malenia  '));
        $this->assertSame(['character', 'Samurai'], DeathCategory::pair('character', 'Samurai'));
    }

    public function test_rejects_incomplete_pair(): void
    {
        $this->expectException(ValidationException::class);
        DeathCategory::pair('boss', null);
    }
}
