<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CacheNewRegistration
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        $ip = request()->ip();
        $city = null;

        if ($ip && ! in_array($ip, ['127.0.0.1', '::1'])) {
            try {
                $geo = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=city")->json();
                $city = $geo['city'] ?? null;
            } catch (\Throwable) {
                // Geolocation failed — skip city silently
            }
        }

        $nameParts = explode(' ', trim($user->name));
        $lastName = count($nameParts) > 1 ? end($nameParts) : $user->name;

        Cache::put('latest_registration', [
            'id' => $user->id,
            'last_name' => $lastName,
            'city' => $city,
            'at' => now()->toISOString(),
        ], now()->addMinutes(10));
    }
}
