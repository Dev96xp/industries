<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function download(): BinaryFileResponse
    {
        Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

        $files = Storage::disk('local')->files('Laravel');

        if (empty($files)) {
            abort(404, 'Backup file not found.');
        }

        $latest = collect($files)->sortDesc()->first();
        $fullPath = Storage::disk('local')->path($latest);

        return response()->download($fullPath, basename($fullPath))->deleteFileAfterSend(false);
    }
}
