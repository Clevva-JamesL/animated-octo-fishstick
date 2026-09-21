<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<string, string>
     */
    protected function extHeaders(string $channel = '12345', string $role = 'broadcaster'): array
    {
        $this->app['env'] = 'local';
        config(['twitch.allow_dev_auth' => true]);

        return [
            'Authorization' => 'Bearer dev',
            'Accept' => 'application/json',
            'X-Twitch-Dev-Channel' => $channel,
            'X-Twitch-Dev-Role' => $role,
        ];
    }
}
