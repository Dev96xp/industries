<?php

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create();
});

it('uploads a website video and replaces the previous one', function () {
    Volt::actingAs($this->admin)
        ->test('admin.company-settings')
        ->set('video', UploadedFile::fake()->create('first.mp4', 2000, 'video/mp4'))
        ->assertHasNoErrors();

    $firstPath = CompanySetting::current()->website_video_path;
    Storage::disk('public')->assertExists($firstPath);

    Volt::actingAs($this->admin)
        ->test('admin.company-settings')
        ->set('video', UploadedFile::fake()->create('second.mp4', 2000, 'video/mp4'))
        ->assertHasNoErrors();

    $secondPath = CompanySetting::current()->website_video_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertExists($secondPath);
    Storage::disk('public')->assertMissing($firstPath);
});

it('rejects files that are not videos', function () {
    Volt::actingAs($this->admin)
        ->test('admin.company-settings')
        ->set('video', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['video' => 'mimetypes']);

    expect(CompanySetting::current()->website_video_path)->toBeNull();
});

it('removes the website video', function () {
    Storage::disk('public')->put('videos/clip.mp4', 'video');
    CompanySetting::current()->update(['website_video_path' => 'videos/clip.mp4']);

    Volt::actingAs($this->admin)
        ->test('admin.company-settings')
        ->call('removeVideo');

    expect(CompanySetting::current()->website_video_path)->toBeNull();
    Storage::disk('public')->assertMissing('videos/clip.mp4');
});

it('shows the video section on the home page only when a video exists', function () {
    $this->get('/')->assertOk()->assertDontSee('See Our Work');

    CompanySetting::current()->update(['website_video_path' => 'videos/clip.mp4']);

    $this->get('/')
        ->assertOk()
        ->assertSee('See Our Work')
        ->assertSee('videos/clip.mp4');
});
