<?php

use App\Models\CompanySetting;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    $company = CompanySetting::current();
    $heroPhoto = Photo::hero()->first();
    $aboutPhoto = Photo::about()->first();
    $featuredPhotos = Photo::featured()->limit(6)->get();

    return view('welcome', compact('company', 'heroPhoto', 'aboutPhoto', 'featuredPhotos'));
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'not.client'])
    ->name('dashboard');

Route::middleware(['auth', 'not.client'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth'])->group(function () {
    Volt::route('client/profile', 'client.profile')->name('client.profile');
    Volt::route('worker/timeclock', 'worker.timeclock')->name('worker.timeclock');
});

Route::middleware(['auth', 'permission:manage photos'])->group(function () {
    Volt::route('admin/photos', 'admin.photos')->name('admin.photos');
});

Route::middleware(['auth', 'permission:manage company settings'])->group(function () {
    Volt::route('admin/company-settings', 'admin.company-settings')->name('admin.company-settings');
});

Route::middleware(['auth', 'permission:manage users'])->group(function () {
    Volt::route('admin/users', 'admin.users')->name('admin.users');
});

Route::middleware(['auth', 'permission:manage projects'])->group(function () {
    Volt::route('admin/projects', 'admin.projects.index')->name('admin.projects');
    Volt::route('admin/projects/create', 'admin.projects.create')->name('admin.projects.create');
    Volt::route('admin/projects/{project}/edit', 'admin.projects.edit')->name('admin.projects.edit');

    Route::get('admin/projects/{project}/report', function (Project $project) {
        $project->load(['client', 'incomes', 'expenses']);

        return view('admin.projects.report', [
            'project' => $project,
            'company' => CompanySetting::current(),
            'incomes' => $project->incomes,
            'expenses' => $project->expenses,
            'totalIncome' => $project->incomes->sum('amount'),
            'totalExpenses' => $project->expenses->sum('amount'),
            'net' => $project->incomes->sum('amount') - $project->expenses->sum('amount'),
        ]);
    })->name('admin.projects.report');
});

Route::middleware(['auth', 'permission:manage time entries'])->group(function () {
    Volt::route('admin/time-entries', 'admin.time-entries.index')->name('admin.time-entries');
});

Route::middleware(['auth', 'permission:manage quotes'])->group(function () {
    Volt::route('admin/quotes', 'admin.quotes.index')->name('admin.quotes');
    Volt::route('admin/quotes/create', 'admin.quotes.create')->name('admin.quotes.create');
    Volt::route('admin/quotes/{quote}/edit', 'admin.quotes.edit')->name('admin.quotes.edit');

    Route::get('admin/quotes/{quote}/report', function (Quote $quote) {
        $quote->load('items');

        return view('admin.quotes.report', [
            'quote' => $quote,
            'company' => CompanySetting::current(),
        ]);
    })->name('admin.quotes.report');
});

require __DIR__.'/auth.php';
