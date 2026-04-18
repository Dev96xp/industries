<?php

use App\Http\Controllers\StripeWebhookController;
use App\Models\CompanySetting;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Quote;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Stripe webhook — no CSRF, no auth
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

Route::get('/quotes/{quote}/approve', function (Quote $quote) {
    if (! request()->hasValidSignature()) {
        abort(403, 'This approval link is invalid or has expired.');
    }

    $alreadyApproved = $quote->status === 'accepted';

    if (! $alreadyApproved) {
        $quote->update(['status' => 'accepted']);
    }

    return view('quotes.approved', [
        'quote' => $quote,
        'company' => CompanySetting::current(),
        'alreadyApproved' => $alreadyApproved,
    ]);
})->name('quotes.approve');

Route::get('/', function () {
    $company = CompanySetting::current();
    $heroPhoto = Photo::hero()->first();
    $aboutPhoto = Photo::about()->first();
    $featuredPhotos = Photo::featured()->limit(6)->get();
    $featuredProjects = Project::featured()->with('photos')->latest()->limit(6)->get();

    return view('welcome', compact('company', 'heroPhoto', 'aboutPhoto', 'featuredPhotos', 'featuredProjects'));
})->name('home');

Route::middleware(['restrict.ip'])->group(function () {
    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified', 'not.client'])
        ->name('dashboard');

    Route::middleware(['auth', 'not.client'])->group(function () {
        Route::redirect('settings', 'settings/profile');

        Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
        Volt::route('settings/password', 'settings.password')->name('settings.password');
        Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
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

    Route::middleware(['auth', 'permission:manage clients'])->group(function () {
        Volt::route('admin/clients', 'admin.clients.index')->name('admin.clients');
        Volt::route('admin/clients/map', 'admin.clients-map')->name('admin.clients.map');
    });

    Route::middleware(['auth', 'role:superadmin'])->group(function () {
        Volt::route('admin/system-settings', 'admin.system-settings')->name('admin.system-settings');
    });
});

Route::middleware(['auth'])->group(function () {
    Volt::route('client/profile', 'client.profile')->name('client.profile');
    Volt::route('client/account', 'client.account')->name('client.account');
    Volt::route('worker/timeclock', 'worker.timeclock')->name('worker.timeclock');

    Route::get('client/quotes/{quote}', function (Quote $quote) {
        abort_if($quote->user_id !== auth()->id(), 403);

        $quote->load('items');

        return view('client.quote', [
            'quote' => $quote,
            'company' => CompanySetting::current(),
        ]);
    })->name('client.quotes.view');
});

Route::middleware(['restrict.ip', 'auth', 'permission:manage projects'])->group(function () {
    Volt::route('admin/projects', 'admin.projects.index')->name('admin.projects');
    Volt::route('admin/projects/kanban', 'admin.projects.kanban')->name('admin.projects.kanban');
    Volt::route('admin/projects/calendar', 'admin.projects.calendar')->name('admin.projects.calendar');
    Volt::route('admin/projects/create', 'admin.projects.create')->name('admin.projects.create');
    Volt::route('admin/projects/{project}/edit', 'admin.projects.edit')->name('admin.projects.edit');
    Volt::route('admin/receipts', 'admin.receipts.index')->name('admin.receipts');
    Volt::route('admin/products', 'admin.products.index')->name('admin.products');
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories');

    Route::get('admin/projects/{project}/tasks/{task}/report', function (Project $project, ProjectTask $task) {
        $task->load(['assignedUser', 'subtasks.assignedUser']);

        return view('admin.projects.task-report', [
            'project' => $project,
            'task' => $task,
            'company' => CompanySetting::current(),
        ]);
    })->name('admin.projects.tasks.report');

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

Route::middleware(['restrict.ip', 'auth', 'permission:manage time entries'])->group(function () {
    Volt::route('admin/time-entries', 'admin.time-entries.index')->name('admin.time-entries');

    Route::get('admin/time-entries/report', function (Request $request) {
        $query = TimeEntry::with(['worker', 'project'])
            ->orderByDesc('clock_in_at');

        $worker = null;
        $project = null;

        if ($request->worker_id) {
            $query->where('user_id', $request->worker_id);
            $worker = User::find($request->worker_id);
        }

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
            $project = Project::find($request->project_id);
        }

        if ($request->date_from) {
            $query->whereDate('clock_in_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('clock_in_at', '<=', $request->date_to);
        }

        return view('admin.time-entries.report', [
            'entries' => $query->get(),
            'company' => CompanySetting::current(),
            'worker' => $worker,
            'project' => $project,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
    })->name('admin.time-entries.report');
});

Route::middleware(['restrict.ip', 'auth', 'permission:manage quote requests'])->group(function () {
    Volt::route('admin/quote-requests', 'admin.quote-requests.index')->name('admin.quote-requests');
});

Route::middleware(['restrict.ip', 'auth', 'permission:manage contractors'])->group(function () {
    Volt::route('admin/contractors', 'admin.contractors.index')->name('admin.contractors');
    Volt::route('admin/contractors/create', 'admin.contractors.create')->name('admin.contractors.create');
    Volt::route('admin/contractors/{contractor}/edit', 'admin.contractors.edit')->name('admin.contractors.edit');
});

Route::middleware(['restrict.ip', 'auth', 'permission:manage locations'])->group(function () {
    Volt::route('admin/locations', 'admin.locations.index')->name('admin.locations');
    Volt::route('admin/locations/create', 'admin.locations.create')->name('admin.locations.create');
    Volt::route('admin/locations/{location}/edit', 'admin.locations.edit')->name('admin.locations.edit');
});

Route::middleware(['restrict.ip', 'auth', 'permission:manage quotes'])->group(function () {
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
