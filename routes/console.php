<?php

use App\Models\CompanySetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Run every minute, check internally if backup should run based on DB settings
Schedule::call(function () {
    $settings = CompanySetting::current();

    if (! $settings->backup_enabled) {
        return;
    }

    $expectedTime = $settings->backup_time ?? '02:00';

    if (now()->format('H:i') !== $expectedTime) {
        return;
    }

    $frequency = $settings->backup_frequency ?? 'weekly';

    if ($frequency === 'monthly') {
        if ((int) now()->format('j') !== (int) ($settings->backup_day_of_month ?? 1)) {
            return;
        }
    } else {
        $dayMap = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0,
        ];
        $expectedDay = $dayMap[strtolower($settings->backup_day ?? 'sunday')] ?? 0;

        if ((int) now()->format('w') !== $expectedDay) {
            return;
        }
    }

    config(['backup.notifications.mail.to' => $settings->backup_email ?: config('mail.from.address')]);

    Artisan::call('backup:run', ['--only-db' => true]);

    // Keep only the last 4 backups
    $files = collect(Storage::disk('local')->files('Laravel'))
        ->filter(fn (string $f) => str_ends_with($f, '.zip'))
        ->sortDesc()
        ->values();

    foreach ($files->slice(4) as $old) {
        Storage::disk('local')->delete($old);
    }
})->everyMinute();
