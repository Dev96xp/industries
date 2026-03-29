<?php

use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Storage::fake('public');
});

it('redirects guests away from photo management', function () {
    $this->get(route('admin.photos'))->assertRedirect(route('login'));
});

it('allows authenticated users to view photo management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.photos'))
        ->assertSuccessful();
});

it('can upload photos', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('project.jpg', 800, 600);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->set('uploads', [$file])
        ->call('saveUploads')
        ->assertHasNoErrors();

    expect(Photo::count())->toBe(1);
    Storage::disk('public')->assertExists(Photo::first()->path);
});

it('validates that uploads must be images', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Volt::actingAs($user)
        ->test('admin.photos')
        ->set('uploads', [$file])
        ->call('saveUploads')
        ->assertHasErrors(['uploads.*']);
});

it('can toggle a photo as featured', function () {
    $user = User::factory()->create();
    $photo = Photo::factory()->create(['is_featured' => false]);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('toggleFeatured', $photo->id);

    expect($photo->fresh()->is_featured)->toBeTrue();
});

it('can delete a photo', function () {
    $user = User::factory()->create();
    Storage::disk('public')->put('photos/test.jpg', 'fake-image-content');
    $photo = Photo::factory()->create(['path' => 'photos/test.jpg']);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('deletePhoto', $photo->id);

    expect(Photo::count())->toBe(0);
    Storage::disk('public')->assertMissing('photos/test.jpg');
});

it('shows featured photos on the welcome page', function () {
    Photo::factory()->count(3)->create(['is_featured' => true]);
    Photo::factory()->count(2)->create(['is_featured' => false]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertViewHas('featuredPhotos', fn ($photos) => $photos->count() === 3);
});

it('shows placeholder projects when no featured photos exist', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertViewHas('featuredPhotos', fn ($photos) => $photos->isEmpty());
});

it('can set a photo as the hero', function () {
    $user  = User::factory()->create();
    $photo = Photo::factory()->create(['is_hero' => false]);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('setAsHero', $photo->id);

    expect($photo->fresh()->is_hero)->toBeTrue();
});

it('only one photo can be hero at a time', function () {
    $user   = User::factory()->create();
    $first  = Photo::factory()->create(['is_hero' => true]);
    $second = Photo::factory()->create(['is_hero' => false]);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('setAsHero', $second->id);

    expect($first->fresh()->is_hero)->toBeFalse();
    expect($second->fresh()->is_hero)->toBeTrue();
});

it('shows hero photo on the welcome page', function () {
    $hero = Photo::factory()->create(['is_hero' => true]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertViewHas('heroPhoto', fn ($p) => $p->id === $hero->id);
});

it('can set a photo as the about photo', function () {
    $user  = User::factory()->create();
    $photo = Photo::factory()->create(['is_about' => false]);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('setAsAbout', $photo->id);

    expect($photo->fresh()->is_about)->toBeTrue();
});

it('only one photo can be about at a time', function () {
    $user   = User::factory()->create();
    $first  = Photo::factory()->create(['is_about' => true]);
    $second = Photo::factory()->create(['is_about' => false]);

    Volt::actingAs($user)
        ->test('admin.photos')
        ->call('setAsAbout', $second->id);

    expect($first->fresh()->is_about)->toBeFalse();
    expect($second->fresh()->is_about)->toBeTrue();
});

it('shows about photo on the welcome page', function () {
    $photo = Photo::factory()->create(['is_about' => true]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertViewHas('aboutPhoto', fn ($p) => $p->id === $photo->id);
});
