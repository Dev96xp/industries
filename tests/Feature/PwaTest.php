<?php

use App\Models\User;

beforeEach(function () {
    config([
        'app.name' => 'Nucleus Industries',
        'app.short_name' => 'Nucleus',
        'app.theme_color' => '#2b2d30',
    ]);
});

it('serves the manifest with the correct content type and fields', function () {
    $response = $this->get('/manifest.webmanifest');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJson([
            'name' => 'Nucleus Industries',
            'short_name' => 'Nucleus',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#2b2d30',
            'theme_color' => '#2b2d30',
        ]);

    $icons = collect($response->json('icons'));

    expect(mb_strlen($response->json('short_name')))->toBeLessThanOrEqual(12)
        ->and($icons->pluck('src')->all())->toContain('/icons/icon-192.png', '/icons/icon-512.png', '/icons/icon-maskable-512.png')
        ->and($icons->firstWhere('purpose', 'maskable')['src'])->toBe('/icons/icon-maskable-512.png');
});

it('is reachable without logging in', function () {
    $this->get('/manifest.webmanifest')->assertOk();
});

it('ships every icon in the expected size', function (string $file, int $size) {
    $path = public_path("icons/{$file}");

    expect(file_exists($path))->toBeTrue();

    [$width, $height] = getimagesize($path);

    expect([$width, $height])->toBe([$size, $size]);
})->with([
    ['icon-192.png', 192],
    ['icon-512.png', 512],
    ['icon-maskable-512.png', 512],
    ['apple-touch-icon.png', 180],
]);

it('includes the pwa tags in the layouts', function (string $page) {
    $user = User::factory()->create();

    $response = $page === 'welcome'
        ? $this->get('/')
        : $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<meta name="theme-color" content="#2b2d30">', false)
        ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false)
        ->assertSee('<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">', false)
        ->assertSee('<meta name="apple-mobile-web-app-title" content="Nucleus">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">', false)
        ->assertSee("navigator.serviceWorker.register('/sw.js')", false)
        ->assertDontSee('<title>Laravel</title>', false);
})->with(['welcome', 'dashboard']);

it('has a network-only service worker that only caches the offline page', function () {
    $sw = file_get_contents(public_path('sw.js'));

    expect($sw)->toContain("const VERSION = '")
        ->toContain("const OFFLINE_URL = '/offline.html'")
        ->toContain("addEventListener('activate'")
        ->toContain('caches.delete(key)')
        ->and(file_exists(public_path('offline.html')))->toBeTrue();
});
