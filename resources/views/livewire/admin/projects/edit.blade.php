<?php

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectIncome;
use App\Models\ProjectPhoto;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;

    public string $name                      = '';
    public string $description               = '';
    public string $status                    = 'planning';
    public string $address                   = '';
    public string $start_date                = '';
    public string $estimated_completion_date = '';
    public string $budget                    = '';
    public ?int   $client_user_id            = null;
    public string  $internal_notes            = '';
    public bool    $is_featured               = false;
    public string  $latitude                  = '';
    public string  $longitude                 = '';
    public string  $geo_radius                = '100';

    // Expense form
    public string  $expenseDescription   = '';
    public string  $expenseCategory      = 'other';
    public string  $expenseAmount        = '';
    public string  $expenseDate          = '';
    public string  $expenseNotes         = '';
    public string  $expensePaymentMethod = 'other';
    public ?int    $editingExpenseId     = null;

    // Income form
    public string  $incomeDescription    = '';
    public string  $incomeSource         = 'other';
    public string  $incomeAmount         = '';
    public string  $incomeDate           = '';
    public string  $incomeNotes          = '';
    public string  $incomePaymentMethod  = 'other';
    public ?int    $editingIncomeId      = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['uploads.*' => 'image|max:10240'])]
    public array $uploads = [];

    public function mount(Project $project): void
    {
        $this->project                   = $project;
        $this->name                      = $project->name;
        $this->description               = $project->description ?? '';
        $this->status                    = $project->status;
        $this->address                   = $project->address ?? '';
        $this->start_date                = $project->start_date?->format('Y-m-d') ?? '';
        $this->estimated_completion_date = $project->estimated_completion_date?->format('Y-m-d') ?? '';
        $this->budget                    = $project->budget ? (string) $project->budget : '';
        $this->client_user_id            = $project->client_user_id;
        $this->internal_notes            = $project->internal_notes ?? '';
        $this->is_featured               = $project->is_featured;
        $this->latitude                  = $project->latitude ? (string) $project->latitude : '';
        $this->longitude                 = $project->longitude ? (string) $project->longitude : '';
        $this->geo_radius                = (string) $project->geo_radius;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'status'                    => ['required', 'in:planning,in_progress,completed'],
            'address'                   => ['nullable', 'string', 'max:255'],
            'start_date'                => ['nullable', 'date'],
            'estimated_completion_date' => ['nullable', 'date'],
            'budget'                    => ['nullable', 'numeric', 'min:0'],
            'client_user_id'            => ['nullable', 'exists:users,id'],
            'internal_notes'            => ['nullable', 'string'],
            'is_featured'               => ['boolean'],
            'latitude'                  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'                 => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius'                => ['integer', 'min:10', 'max:5000'],
        ]);

        $this->project->update($validated);

        session()->flash('success', 'Project updated.');
    }

    public function savePhotos(): void
    {
        $this->validate(['uploads.*' => 'image|max:10240']);

        foreach ($this->uploads as $file) {
            $path = $file->store('project-photos', 'public');

            ProjectPhoto::create([
                'project_id' => $this->project->id,
                'path'       => $path,
                'disk'       => 'public',
            ]);
        }

        $this->uploads = [];
        $this->project->refresh();
        $this->dispatch('photos-saved');
    }

    public function deletePhoto(ProjectPhoto $photo): void
    {
        \Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();
        $this->project->refresh();
    }

    public function saveExpense(): void
    {
        $validated = $this->validate([
            'expenseDescription'   => ['required', 'string', 'max:255'],
            'expenseCategory'      => ['required', 'in:materials,labor,equipment,subcontractors,permits,other'],
            'expenseAmount'        => ['required', 'numeric', 'min:0.01'],
            'expenseDate'          => ['required', 'date'],
            'expenseNotes'         => ['nullable', 'string'],
            'expensePaymentMethod' => ['required', 'in:cash,check,visa,mastercard,bank_transfer,other'],
        ]);

        $data = [
            'description'    => $validated['expenseDescription'],
            'category'       => $validated['expenseCategory'],
            'amount'         => $validated['expenseAmount'],
            'expense_date'   => $validated['expenseDate'],
            'notes'          => $validated['expenseNotes'] ?: null,
            'payment_method' => $validated['expensePaymentMethod'],
        ];

        if ($this->editingExpenseId) {
            ProjectExpense::findOrFail($this->editingExpenseId)->update($data);
        } else {
            $this->project->expenses()->create($data);
        }

        $this->resetExpenseForm();
        $this->project->refresh();
    }

    public function editExpense(ProjectExpense $expense): void
    {
        $this->editingExpenseId      = $expense->id;
        $this->expenseDescription    = $expense->description;
        $this->expenseCategory       = $expense->category;
        $this->expenseAmount         = (string) $expense->amount;
        $this->expenseDate           = $expense->expense_date->format('Y-m-d');
        $this->expenseNotes          = $expense->notes ?? '';
        $this->expensePaymentMethod  = $expense->payment_method;
    }

    public function deleteExpense(ProjectExpense $expense): void
    {
        $expense->delete();
        $this->project->refresh();
    }

    public function cancelExpenseEdit(): void
    {
        $this->resetExpenseForm();
    }

    private function resetExpenseForm(): void
    {
        $this->expenseDescription   = '';
        $this->expenseCategory      = 'other';
        $this->expenseAmount        = '';
        $this->expenseDate          = '';
        $this->expenseNotes         = '';
        $this->expensePaymentMethod = 'other';
        $this->editingExpenseId     = null;
    }

    public function saveIncome(): void
    {
        $validated = $this->validate([
            'incomeDescription'   => ['required', 'string', 'max:255'],
            'incomeSource'        => ['required', 'in:bank_loan,partner,personal,client_payment,investor,other'],
            'incomeAmount'        => ['required', 'numeric', 'min:0.01'],
            'incomeDate'          => ['required', 'date'],
            'incomeNotes'         => ['nullable', 'string'],
            'incomePaymentMethod' => ['required', 'in:cash,check,visa,mastercard,bank_transfer,other'],
        ]);

        $data = [
            'description'    => $validated['incomeDescription'],
            'source'         => $validated['incomeSource'],
            'amount'         => $validated['incomeAmount'],
            'income_date'    => $validated['incomeDate'],
            'notes'          => $validated['incomeNotes'] ?: null,
            'payment_method' => $validated['incomePaymentMethod'],
        ];

        if ($this->editingIncomeId) {
            ProjectIncome::findOrFail($this->editingIncomeId)->update($data);
        } else {
            $this->project->incomes()->create($data);
        }

        $this->resetIncomeForm();
        $this->project->refresh();
    }

    public function editIncome(ProjectIncome $income): void
    {
        $this->editingIncomeId      = $income->id;
        $this->incomeDescription    = $income->description;
        $this->incomeSource         = $income->source;
        $this->incomeAmount         = (string) $income->amount;
        $this->incomeDate           = $income->income_date->format('Y-m-d');
        $this->incomeNotes          = $income->notes ?? '';
        $this->incomePaymentMethod  = $income->payment_method;
    }

    public function deleteIncome(ProjectIncome $income): void
    {
        $income->delete();
        $this->project->refresh();
    }

    public function cancelIncomeEdit(): void
    {
        $this->resetIncomeForm();
    }

    private function resetIncomeForm(): void
    {
        $this->incomeDescription   = '';
        $this->incomeSource        = 'other';
        $this->incomeAmount        = '';
        $this->incomeDate          = '';
        $this->incomeNotes         = '';
        $this->incomePaymentMethod = 'other';
        $this->editingIncomeId     = null;
    }

    public function with(): array
    {
        $clientRole = Role::where('name', 'client')->first();

        return [
            'clients' => $clientRole
                ? User::role('client')->orderBy('name')->get()
                : collect(),
            'photos'       => $this->project->photos,
            'expenses'     => $this->project->expenses,
            'incomes'      => $this->project->incomes,
            'totalSpent'   => $this->project->expenses()->sum('amount'),
            'totalIncome'  => $this->project->incomes()->sum('amount'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('admin.projects') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
            <div>
                <flux:heading size="xl">{{ $project->name }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    @if($project->number)
                        <span class="font-mono">{{ $project->number }}</span> &middot;
                    @endif
                    Edit project details and photos.
                </flux:text>
            </div>
        </div>
        <a href="{{ route('admin.projects.report', $project) }}" target="_blank"
            class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <flux:icon.printer class="size-4" />
            Print Report
        </a>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="flex flex-col gap-6 max-w-3xl">

        {{-- Basic info --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Project Info</h3>
            <div class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Project Name" required />
                <flux:textarea wire:model="description" label="Description" rows="3" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="planning">Planning</flux:select.option>
                        <flux:select.option value="in_progress">In Progress</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                    </flux:select>
                    <flux:select wire:model="client_user_id" label="Assigned Client">
                        <flux:select.option value="">— No client —</flux:select.option>
                        @foreach($clients as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="address" label="Address / Location" />
            </div>
        </div>

        {{-- Dates & Budget --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Schedule & Budget</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="start_date" label="Start Date" type="date" />
                <flux:input wire:model="estimated_completion_date" label="Est. Completion" type="date" />
                <flux:input wire:model="budget" label="Budget ($)" type="number" step="100" />
            </div>
        </div>

        {{-- GPS / Worksite Location --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
            x-data="{ locating: false, error: '', getLocation() {
                this.locating = true; this.error = '';
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        $wire.set('latitude',  pos.coords.latitude.toFixed(7));
                        $wire.set('longitude', pos.coords.longitude.toFixed(7));
                        this.locating = false;
                    },
                    (err) => { this.error = 'Could not get location: ' + err.message; this.locating = false; }
                );
            }}">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Worksite Location (GPS)</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Used to verify workers are on-site when clocking in/out.</p>
                </div>
                <button type="button" @click="getLocation()" :disabled="locating"
                    class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <flux:icon.map-pin class="size-3.5" />
                    <span x-text="locating ? 'Getting location...' : 'Use My Location'"></span>
                </button>
            </div>
            <p x-show="error" x-text="error" class="mb-3 text-xs text-red-500"></p>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="latitude" label="Latitude" placeholder="e.g. 25.7617" />
                <flux:input wire:model="longitude" label="Longitude" placeholder="e.g. -80.1918" />
                <flux:input wire:model="geo_radius" label="Radius (meters)" type="number" min="10" max="5000" />
            </div>
            @if($project->latitude && $project->longitude)
                <p class="mt-3 text-xs text-zinc-400">
                    <flux:icon.check-circle class="inline size-3.5 text-green-500" />
                    Location set — workers within {{ $project->geo_radius }}m will be verified.
                </p>
            @endif
        </div>

        {{-- Internal notes --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Internal Notes</h3>
            <flux:textarea wire:model="internal_notes" rows="4" placeholder="Notes visible only to staff..." />
        </div>

        {{-- Options --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <label class="flex items-center gap-3 cursor-pointer">
                <flux:checkbox wire:model="is_featured" />
                <div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Feature on public website</p>
                    <p class="text-xs text-zinc-400">Show this project in the Projects section of the welcome page.</p>
                </div>
            </label>
        </div>

        <div class="flex gap-3">
            <flux:button wire:click="save" variant="primary">Save Changes</flux:button>
        </div>

        {{-- Income --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Income</h3>
                @if($totalIncome > 0)
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">
                        Total: ${{ number_format($totalIncome, 0) }}
                    </span>
                @endif
            </div>

            {{-- Income list --}}
            @if($incomes->isNotEmpty())
                <div class="mb-5 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    @foreach($incomes as $income)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $income->description }}</span>
                                    <flux:badge size="sm" color="{{ match($income->source) {
                                        'bank_loan' => 'blue', 'partner' => 'purple', 'personal' => 'zinc',
                                        'client_payment' => 'green', 'investor' => 'amber', default => 'zinc'
                                    } }}">{{ ucfirst(str_replace('_', ' ', $income->source)) }}</flux:badge>
                                </div>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $income->income_date->format('M d, Y') }} · {{ ucfirst(str_replace('_', ' ', $income->payment_method)) }}@if($income->notes) · {{ $income->notes }}@endif</p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">${{ number_format($income->amount, 2) }}</span>
                                <div class="flex gap-1">
                                    <button wire:click="editIncome({{ $income->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                                        <flux:icon.pencil class="size-3.5" />
                                    </button>
                                    <button wire:click="deleteIncome({{ $income->id }})" wire:confirm="Delete this income entry?" class="p-1 text-zinc-400 hover:text-red-500 transition">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add / Edit income form --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">
                    {{ $editingIncomeId ? 'Edit Income' : 'Add Income' }}
                </h4>
                <div class="flex flex-col gap-3">
                    <flux:input wire:model="incomeDescription" label="Description" placeholder="e.g. Bank loan disbursement" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:select wire:model="incomeSource" label="Source">
                            <flux:select.option value="bank_loan">Bank Loan</flux:select.option>
                            <flux:select.option value="partner">Partner</flux:select.option>
                            <flux:select.option value="personal">Personal</flux:select.option>
                            <flux:select.option value="client_payment">Client Payment</flux:select.option>
                            <flux:select.option value="investor">Investor</flux:select.option>
                            <flux:select.option value="other">Other</flux:select.option>
                        </flux:select>
                        <flux:select wire:model="incomePaymentMethod" label="Payment Method">
                            <flux:select.option value="cash">Cash</flux:select.option>
                            <flux:select.option value="check">Check</flux:select.option>
                            <flux:select.option value="visa">Visa</flux:select.option>
                            <flux:select.option value="mastercard">Mastercard</flux:select.option>
                            <flux:select.option value="bank_transfer">Bank Transfer</flux:select.option>
                            <flux:select.option value="other">Other</flux:select.option>
                        </flux:select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:input wire:model="incomeAmount" label="Amount ($)" type="number" step="0.01" placeholder="0.00" />
                        <flux:input wire:model="incomeDate" label="Date" type="date" />
                    </div>
                    <flux:input wire:model="incomeNotes" label="Notes (optional)" placeholder="Additional details..." />
                    <div class="flex gap-2 pt-1">
                        <flux:button wire:click="saveIncome" variant="primary" size="sm">
                            {{ $editingIncomeId ? 'Update Income' : 'Add Income' }}
                        </flux:button>
                        @if($editingIncomeId)
                            <flux:button wire:click="cancelIncomeEdit" variant="ghost" size="sm">Cancel</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Expenses</h3>
                @if($project->budget)
                    <span class="text-xs text-zinc-400">
                        Budget: <span class="font-semibold text-zinc-700 dark:text-zinc-200">${{ number_format($project->budget, 0) }}</span>
                    </span>
                @endif
            </div>

            {{-- Budget progress bar --}}
            @if($project->budget && $project->budget > 0)
                @php
                    $pct = min(100, round(($totalSpent / $project->budget) * 100));
                    $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-green-500');
                @endphp
                <div class="mb-5">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1">
                        <span>Spent: <strong class="text-zinc-800 dark:text-zinc-100">${{ number_format($totalSpent, 0) }}</strong></span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="h-2.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-2.5 rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                    @if($totalSpent > $project->budget)
                        <p class="mt-1 text-xs text-red-500 font-medium">Over budget by ${{ number_format($totalSpent - $project->budget, 0) }}</p>
                    @else
                        <p class="mt-1 text-xs text-zinc-400">Remaining: ${{ number_format($project->budget - $totalSpent, 0) }}</p>
                    @endif
                </div>
            @elseif($totalSpent > 0)
                <p class="mb-4 text-sm text-zinc-500">Total spent: <strong class="text-zinc-800 dark:text-zinc-100">${{ number_format($totalSpent, 0) }}</strong></p>
            @endif

            {{-- Net balance summary --}}
            @if($totalIncome > 0 || $totalSpent > 0)
                @php $net = $totalIncome - $totalSpent; @endphp
                <div class="mb-4 flex items-center justify-between rounded-xl bg-zinc-50 dark:bg-zinc-800/50 px-4 py-3 text-sm">
                    <span class="text-zinc-500">Net Balance</span>
                    <span class="font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $net >= 0 ? '+' : '' }}${{ number_format(abs($net), 0) }}
                    </span>
                </div>
            @endif

            {{-- Expense list --}}
            @if($expenses->isNotEmpty())
                <div class="mb-5 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    @foreach($expenses as $expense)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $expense->description }}</span>
                                    <flux:badge size="sm" color="{{ match($expense->category) {
                                        'materials' => 'blue', 'labor' => 'green', 'equipment' => 'amber',
                                        'subcontractors' => 'purple', 'permits' => 'zinc', default => 'zinc'
                                    } }}">{{ ucfirst($expense->category) }}</flux:badge>
                                </div>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $expense->expense_date->format('M d, Y') }} · {{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}@if($expense->notes) · {{ $expense->notes }}@endif</p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">${{ number_format($expense->amount, 2) }}</span>
                                <div class="flex gap-1">
                                    <button wire:click="editExpense({{ $expense->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                                        <flux:icon.pencil class="size-3.5" />
                                    </button>
                                    <button wire:click="deleteExpense({{ $expense->id }})" wire:confirm="Delete this expense?" class="p-1 text-zinc-400 hover:text-red-500 transition">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add / Edit expense form --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">
                    {{ $editingExpenseId ? 'Edit Expense' : 'Add Expense' }}
                </h4>
                <div class="flex flex-col gap-3">
                    <flux:input wire:model="expenseDescription" label="Description" placeholder="e.g. Concrete delivery" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:select wire:model="expenseCategory" label="Category">
                            <flux:select.option value="materials">Materials</flux:select.option>
                            <flux:select.option value="labor">Labor</flux:select.option>
                            <flux:select.option value="equipment">Equipment</flux:select.option>
                            <flux:select.option value="subcontractors">Subcontractors</flux:select.option>
                            <flux:select.option value="permits">Permits</flux:select.option>
                            <flux:select.option value="other">Other</flux:select.option>
                        </flux:select>
                        <flux:select wire:model="expensePaymentMethod" label="Payment Method">
                            <flux:select.option value="cash">Cash</flux:select.option>
                            <flux:select.option value="check">Check</flux:select.option>
                            <flux:select.option value="visa">Visa</flux:select.option>
                            <flux:select.option value="mastercard">Mastercard</flux:select.option>
                            <flux:select.option value="bank_transfer">Bank Transfer</flux:select.option>
                            <flux:select.option value="other">Other</flux:select.option>
                        </flux:select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:input wire:model="expenseAmount" label="Amount ($)" type="number" step="0.01" placeholder="0.00" />
                        <flux:input wire:model="expenseDate" label="Date" type="date" />
                    </div>
                    <flux:input wire:model="expenseNotes" label="Notes (optional)" placeholder="Additional details..." />
                    <div class="flex gap-2 pt-1">
                        <flux:button wire:click="saveExpense" variant="primary" size="sm">
                            {{ $editingExpenseId ? 'Update Expense' : 'Add Expense' }}
                        </flux:button>
                        @if($editingExpenseId)
                            <flux:button wire:click="cancelExpenseEdit" variant="ghost" size="sm">Cancel</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Photos --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Project Photos</h3>

            {{-- Existing photos --}}
            @if($photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-3">
                    @foreach($photos as $photo)
                        <div class="group relative rounded-xl overflow-hidden">
                            <img src="{{ $photo->url() }}" class="h-32 w-full object-cover" alt="">
                            <button
                                wire:click="deletePhoto({{ $photo->id }})"
                                wire:confirm="Remove this photo?"
                                class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100"
                            >
                                <flux:icon.trash class="size-5 text-white" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Upload new photos --}}
            <div
                x-data="{
                    uploading: false,
                    ready: false,
                    uploadFiles(files) {
                        if (!files.length) return;
                        this.uploading = true;
                        this.ready = false;
                        $wire.uploadMultiple('uploads', files,
                            () => { this.uploading = false; this.ready = true; },
                            () => { this.uploading = false; },
                            () => {}
                        );
                    }
                }"
                @dragover.prevent
                @drop.prevent="uploadFiles(Array.from($event.dataTransfer.files))"
                class="rounded-xl border-2 border-dashed border-zinc-200 p-6 text-center dark:border-zinc-700"
            >
                <input type="file" multiple accept="image/*" class="hidden" id="project-photo-input"
                    @change="uploadFiles(Array.from($event.target.files))">

                <label for="project-photo-input" class="cursor-pointer">
                    <flux:icon.photo class="mx-auto size-8 text-zinc-300" />
                    <p class="mt-2 text-sm text-zinc-500">Drop photos here or <span class="text-blue-600 underline">browse</span></p>
                    <p class="text-xs text-zinc-400">JPG, PNG, WEBP — max 10MB each</p>
                </label>

                <div x-show="uploading" class="mt-3 text-sm text-zinc-500">Uploading...</div>

                <div x-show="ready" class="mt-3">
                    <flux:button wire:click="savePhotos" variant="primary" size="sm">Save Photos</flux:button>
                </div>
            </div>
        </div>

    </div>

</div>
