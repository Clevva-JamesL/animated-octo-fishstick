<?php

namespace App\Support;

final class TwitchClipUrl
{
    /**
     * @return array{id: string, url: string}|null
     */
    public static function parse(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = $parts['path'] ?? '';
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $slug = match (true) {
            $host === 'clips.twitch.tv' && trim($path, '/') === 'embed' => $query['clip'] ?? null,
            $host === 'clips.twitch.tv' => explode('/', trim($path, '/'))[0] ?? null,
            $host === 'player.twitch.tv' => $query['clip'] ?? null,
            in_array($host, ['twitch.tv', 'm.twitch.tv'], true) && preg_match('#/clip/([^/]+)#', $path, $matches) === 1 => $matches[1],
            default => null,
        };

        if (! is_string($slug)) {
            return null;
        }

        $slug = rawurldecode($slug);

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{1,99}$/', $slug) !== 1) {
            return null;
        }

        return [
            'id' => $slug,
            'url' => 'https://clips.twitch.tv/'.$slug,
        ];
    }
}
