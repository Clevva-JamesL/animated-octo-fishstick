<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeathCategory
{
    public const TYPES = ['boss', 'character'];

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'category_type' => ['nullable', 'string', Rule::in(self::TYPES)],
            'category_value' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    public static function pair(?string $type, ?string $value): array
    {
        $type = is_string($type) ? trim($type) : '';
        $value = is_string($value) ? trim($value) : '';

        if ($type === '' && $value === '') {
            return [null, null];
        }

        if ($type === '' || $value === '') {
            throw ValidationException::withMessages([
                'category_value' => 'Set both a tag type (boss or character) and a name, or leave both empty.',
            ]);
        }

        return [strtolower($type), $value];
    }
}
