<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    $company        = \App\Models\CompanySetting::current();
    $heroPhoto      = \App\Models\Photo::hero()->first();
    $aboutPhoto     = \App\Models\Photo::about()->first();
    $featuredPhotos = \App\Models\Photo::featured()->limit(6)->get();

    return view('welcome', compact('company', 'heroPhoto', 'aboutPhoto', 'featuredPhotos'));
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('admin/photos', 'admin.photos')->name('admin.photos');
    Volt::route('admin/company-settings', 'admin.company-settings')->name('admin.company-settings');
});

require __DIR__.'/auth.php';
