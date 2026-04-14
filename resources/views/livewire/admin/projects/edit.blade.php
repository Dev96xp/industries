<?php

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectIncome;
use App\Models\ProjectPhoto;
use App\Models\ProjectSubtask;
use App\Models\Contractor;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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
    public string $status                    = 'draft';
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
    public ?int    $editingExpenseId    = null;
    public         $receiptImage        = null;
    public string  $receiptExistingPath = '';
    public bool    $isScanning          = false;

    // Income form
    public string  $incomeDescription    = '';
    public string  $incomeSource         = 'other';
    public string  $incomeAmount         = '';
    public string  $incomeDate           = '';
    public string  $incomeNotes          = '';
    public string  $incomePaymentMethod  = 'other';
    public ?int    $editingIncomeId      = null;

    // Task form
    public string  $taskName             = '';
    public string  $taskDescription      = '';
    public string  $taskStartDate        = '';
    public string  $taskEndDate          = '';
    public string  $taskStatus           = 'pending';
    public string  $taskAssignedType     = 'internal';
    public int|string|null $taskAssignedUserId = null;
    public string  $taskAssignedCompany    = '';
    public ?int    $taskAssignedContractorId = null;
    public string  $taskNotes            = '';
    public ?int    $editingTaskId        = null;
    public string  $subtaskName           = '';
    public string  $subtaskStatus         = 'pending';
    public ?int    $subtaskAssignedUserId = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['uploads.*' => 'mimes:jpg,jpeg,png,gif,webp,pdf|max:10240'])]
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

    public function syncBudgetFromQuote(): void
    {
        $quote = $this->project->quotes()->where('status', 'accepted')->latest()->first();

        if (! $quote) {
            session()->flash('error', 'No accepted quote found for this project.');

            return;
        }

        $this->project->update(['budget' => $quote->total]);
        $this->budget = (string) $quote->total;
        session()->flash('success', 'Budget updated from quote: $' . number_format($quote->total, 2));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'status'                    => ['required', 'in:draft,planning,in_progress,on_hold,completed,cancelled'],
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
        $this->validate(['uploads.*' => 'mimes:jpg,jpeg,png,gif,webp,pdf|max:10240']);

        foreach ($this->uploads as $file) {
            $path = $file->store('project-photos', 'public');

            ProjectPhoto::create([
                'project_id'    => $this->project->id,
                'path'          => $path,
                'disk'          => 'public',
                'mime_type'     => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
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

    public function saveAnnotatedPhoto(string $dataUrl): void
    {
        $data      = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $imageData = base64_decode($data);
        $filename  = 'annotated_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.jpg';
        $path      = 'project-photos/' . $filename;

        \Storage::disk('public')->put($path, $imageData);

        ProjectPhoto::create([
            'project_id'    => $this->project->id,
            'path'          => $path,
            'disk'          => 'public',
            'mime_type'     => 'image/jpeg',
            'original_name' => $filename,
        ]);

        $this->project->refresh();
        session()->flash('success', 'Annotated photo saved.');
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

        if ($this->receiptImage) {
            $data['receipt_path'] = $this->storeReceipt();
        } elseif ($this->receiptExistingPath) {
            $data['receipt_path'] = $this->receiptExistingPath;
        }

        if ($this->editingExpenseId) {
            ProjectExpense::findOrFail($this->editingExpenseId)->update($data);
        } else {
            $this->project->expenses()->create($data);
        }

        $this->resetExpenseForm();
        $this->modal('expense-form')->close();
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
        $this->receiptExistingPath   = $expense->receipt_path ?? '';
        $this->modal('expense-form')->show();
    }

    public function deleteExpense(ProjectExpense $expense): void
    {
        $expense->delete();
        $this->project->refresh();
    }

    public function newExpense(): void
    {
        $this->resetExpenseForm();
    }

    public function cancelExpenseEdit(): void
    {
        $this->resetExpenseForm();
        $this->modal('expense-form')->close();
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
        $this->receiptImage         = null;
        $this->receiptExistingPath  = '';
        $this->dispatch('receipt-reset');
    }

    private function storeReceipt(): string
    {
        $filename = 'receipts/' . \Illuminate\Support\Str::uuid() . '.jpg';

        $manager = new \Intervention\Image\ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        $image = $manager->read($this->receiptImage->getRealPath())
            ->scaleDown(width: 1500, height: 2000)
            ->toJpeg(quality: 80);

        Storage::disk('public')->put($filename, $image);

        return $filename;
    }

    public function scanReceipt(): void
    {
        if (! $this->receiptImage) {
            return;
        }

        $this->isScanning = true;

        try {
            $imageData = base64_encode(file_get_contents($this->receiptImage->getRealPath()));
            $mimeType  = $this->receiptImage->getMimeType();

            $client = new \Anthropic\Client(config('services.anthropic.api_key'));

            $response = $client->messages()->create([
                'model'      => config('services.anthropic.model'),
                'max_tokens' => 512,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'   => 'image',
                                'source' => [
                                    'type'       => 'base64',
                                    'media_type' => $mimeType,
                                    'data'       => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Extract data from this receipt. Reply ONLY with a JSON object with these keys: "amount" (numeric, no currency symbol), "date" (YYYY-MM-DD format), "description" (store/company name and what was purchased, max 120 chars), "payment_method" (one of: cash, check, visa, mastercard, bank_transfer, other). If a value cannot be determined, use null.',
                            ],
                        ],
                    ],
                ],
            ]);

            $text = $response->content[0]->text ?? '';
            // Extract JSON from response
            preg_match('/\{.*\}/s', $text, $matches);
            $data = $matches[0] ? json_decode($matches[0], true) : [];

            if (! empty($data['amount'])) {
                $this->expenseAmount = (string) $data['amount'];
            }
            if (! empty($data['date'])) {
                $this->expenseDate = $data['date'];
            }
            if (! empty($data['description'])) {
                $this->expenseDescription = $data['description'];
            }
            if (! empty($data['payment_method'])) {
                $validMethods = ['cash', 'check', 'visa', 'mastercard', 'bank_transfer', 'other'];
                if (in_array($data['payment_method'], $validMethods)) {
                    $this->expensePaymentMethod = $data['payment_method'];
                }
            }

            session()->flash('scan_success', 'Receipt scanned successfully.');
        } catch (\Throwable $e) {
            session()->flash('scan_error', 'Could not read receipt. Please fill in manually.');
        } finally {
            $this->isScanning = false;
        }
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
        $this->modal('income-form')->close();
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
        $this->modal('income-form')->show();
    }

    public function deleteIncome(ProjectIncome $income): void
    {
        $income->delete();
        $this->project->refresh();
    }

    public function newIncome(): void
    {
        $this->resetIncomeForm();
    }

    public function cancelIncomeEdit(): void
    {
        $this->resetIncomeForm();
        $this->modal('income-form')->close();
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

    public function saveTask(): void
    {
        $this->taskAssignedUserId = $this->taskAssignedUserId ?: null;

        $rules = [
            'taskName'            => ['required', 'string', 'max:255'],
            'taskDescription'     => ['nullable', 'string'],
            'taskStartDate'       => ['required', 'date'],
            'taskEndDate'         => ['required', 'date', 'after_or_equal:taskStartDate'],
            'taskStatus'          => ['required', 'in:pending,in_progress,completed,delayed,cancelled'],
            'taskAssignedType'    => ['required', 'in:internal,external'],
            'taskAssignedUserId'  => ['nullable', 'required_if:taskAssignedType,internal', 'integer', 'exists:users,id'],
            'taskAssignedContractorId' => ['nullable', 'required_if:taskAssignedType,external', 'exists:contractors,id'],
            'taskNotes'           => ['nullable', 'string'],
        ];

        $validated = $this->validate($rules);

        $data = [
            'name'             => $validated['taskName'],
            'description'      => $validated['taskDescription'] ?: null,
            'start_date'       => $validated['taskStartDate'],
            'end_date'         => $validated['taskEndDate'],
            'status'           => $validated['taskStatus'],
            'assigned_type'    => $validated['taskAssignedType'],
            'assigned_user_id' => $validated['taskAssignedType'] === 'internal' ? ($validated['taskAssignedUserId'] ?: null) : null,
            'contractor_id'    => $validated['taskAssignedType'] === 'external' ? ($validated['taskAssignedContractorId'] ?: null) : null,
            'assigned_company' => null,
            'notes'            => $validated['taskNotes'] ?: null,
        ];

        if ($this->editingTaskId) {
            ProjectTask::findOrFail($this->editingTaskId)->update($data);
        } else {
            $data['sort_order'] = $this->project->tasks()->max('sort_order') + 1;
            $this->project->tasks()->create($data);
        }

        $this->resetTaskForm();
        $this->project->refresh();
        $this->modal('task-form')->close();
    }

    public function editTask(ProjectTask $task): void
    {
        $this->editingTaskId       = $task->id;
        $this->taskName            = $task->name;
        $this->taskDescription     = $task->description ?? '';
        $this->taskStartDate       = $task->start_date->format('Y-m-d');
        $this->taskEndDate         = $task->end_date->format('Y-m-d');
        $this->taskStatus          = $task->status;
        $this->taskAssignedType         = $task->assigned_type;
        $this->taskAssignedUserId       = $task->assigned_user_id;
        $this->taskAssignedContractorId = $task->contractor_id;
        $this->taskNotes           = $task->notes ?? '';
        $this->modal('task-form')->show();
    }

    public function deleteTask(ProjectTask $task): void
    {
        $task->delete();
        $this->project->refresh();
    }

    public function cancelTaskEdit(): void
    {
        $this->resetTaskForm();
    }

    public function addSubtask(): void
    {
        $this->validate([
            'subtaskName'           => ['required', 'string', 'max:255'],
            'subtaskStatus'         => ['required', 'in:pending,in_progress,completed,delayed,cancelled'],
            'subtaskAssignedUserId' => ['nullable', 'exists:users,id'],
        ]);

        $task = ProjectTask::findOrFail($this->editingTaskId);

        $task->subtasks()->create([
            'name'             => $this->subtaskName,
            'status'           => $this->subtaskStatus,
            'assigned_user_id' => $this->subtaskAssignedUserId,
            'sort_order'       => $task->subtasks()->max('sort_order') + 1,
        ]);

        $this->subtaskName           = '';
        $this->subtaskStatus         = 'pending';
        $this->subtaskAssignedUserId = null;
    }

    public function deleteSubtask(ProjectSubtask $subtask): void
    {
        $subtask->delete();
    }

    public function updateSubtaskAssignee(int $subtaskId, ?string $userId): void
    {
        ProjectSubtask::findOrFail($subtaskId)->update([
            'assigned_user_id' => $userId ?: null,
        ]);
    }

    public function cycleSubtaskStatus(ProjectSubtask $subtask): void
    {
        $next = match($subtask->status) {
            'pending'     => 'in_progress',
            'in_progress' => 'completed',
            'completed'   => 'delayed',
            'delayed'     => 'cancelled',
            default       => 'pending',
        };

        $subtask->update(['status' => $next]);
    }

    public function cycleTaskStatus(ProjectTask $task): void
    {
        $next = match($task->status) {
            'pending'     => 'in_progress',
            'in_progress' => 'completed',
            'completed'   => 'delayed',
            default       => 'pending',
        };

        $task->update(['status' => $next]);
        $this->project->refresh();
    }

    public function reorderTasks(int $taskId, int $position): void
    {
        ProjectTask::where('id', $taskId)
            ->where('project_id', $this->project->id)
            ->update(['sort_order' => $position]);
    }

    private function resetTaskForm(): void
    {
        $this->taskName            = '';
        $this->taskDescription     = '';
        $this->taskStartDate       = '';
        $this->taskEndDate         = '';
        $this->taskStatus          = 'pending';
        $this->taskAssignedType    = 'internal';
        $this->taskAssignedUserId       = null;
        $this->taskAssignedContractorId = null;
        $this->taskNotes                = '';
        $this->editingTaskId       = null;
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
            'tasks'        => $this->project->tasks()->with(['assignedUser', 'contractor', 'subtasks.assignedUser'])->get(),
            'subtasks'     => $this->editingTaskId ? ProjectTask::find($this->editingTaskId)?->subtasks()->with('assignedUser')->get() : collect(),
            'staff'        => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['superadmin', 'admin', 'editor', 'worker']))->orderBy('name')->get(),
            'contractors'  => Contractor::where('is_active', true)->orderBy('company_name')->get(),
            'totalSpent'   => $this->project->expenses()->sum('amount'),
            'totalIncome'  => $this->project->incomes()->sum('amount'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

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
            <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300 mb-4">Project Info</h3>
            <div class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Project Name" required />
                <flux:textarea wire:model="description" label="Description" rows="3" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="planning">Planning</flux:select.option>
                        <flux:select.option value="in_progress">In Progress</flux:select.option>
                        <flux:select.option value="on_hold">On Hold</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="cancelled">Cancelled</flux:select.option>
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
                <div class="flex flex-col gap-1">
                    <flux:input wire:model="budget" label="Budget ($)" type="number" step="100" />
                    @if($project->quotes()->where('status', 'accepted')->exists())
                        <flux:button wire:click="syncBudgetFromQuote" wire:confirm="Update budget from the latest accepted quote?" size="xs" variant="ghost" icon="arrow-path">
                            Sync from Quote
                        </flux:button>
                    @endif
                </div>
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
                    <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300">Worksite Location (GPS)</h3>
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

        {{-- Tasks / Schedule --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300">Work Schedule</h3>
                <flux:modal.trigger name="task-form">
                    <flux:button size="sm" icon="plus">Add Task</flux:button>
                </flux:modal.trigger>
            </div>

            {{-- Task timeline --}}
            @if($tasks->isNotEmpty())
                <div class="space-y-2"
                    x-sort="$wire.reorderTasks($item, $position)"
                >
                    @foreach($tasks as $task)
                        @php
                            $statusColor = match($task->status) {
                                'in_progress' => 'blue',
                                'completed'   => 'green',
                                'delayed'     => 'red',
                                default       => 'zinc',
                            };
                            $statusLabel = match($task->status) {
                                'in_progress' => 'In Progress',
                                'completed'   => 'Completed',
                                'delayed'     => 'Delayed',
                                default       => 'Pending',
                            };
                        @endphp
                        <div x-sort:item="{{ $task->id }}" x-data="{ open: false }" class="rounded-xl border border-zinc-100 dark:border-zinc-800">
                            {{-- Task row --}}
                            <div class="flex items-start gap-3 px-4 py-3 cursor-default">
                                {{-- Drag handle --}}
                                <div x-sort:handle class="mt-1 shrink-0 cursor-grab text-zinc-300 hover:text-zinc-400 dark:text-zinc-600 dark:hover:text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8.5 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM8.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM8.5 18a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM18.5 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM18.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM18.5 18a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                                </div>
                                {{-- Status dot --}}
                                <div class="mt-1 shrink-0">
                                    @if($task->status === 'completed')
                                        <div class="flex size-5 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                            <svg class="size-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </div>
                                    @elseif($task->status === 'in_progress')
                                        <div class="size-5 rounded-full border-2 border-blue-500 bg-blue-100 dark:bg-blue-900/30"></div>
                                    @elseif($task->status === 'delayed')
                                        <div class="flex size-5 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                            <svg class="size-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z"/></svg>
                                        </div>
                                    @else
                                        <div class="size-5 rounded-full border-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800"></div>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $task->name }}</span>
                                        <button wire:click="cycleTaskStatus({{ $task->id }})" title="Click to change status" class="cursor-pointer">
                                            <flux:badge size="sm" color="{{ $statusColor }}">{{ $statusLabel }}</flux:badge>
                                        </button>
                                        @if($task->subtasks->isNotEmpty())
                                            <span class="text-xs text-zinc-400">{{ $task->subtasks->count() }} subtask(s)</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-zinc-400 mt-0.5">
                                        {{ $task->start_date->format('M d') }} – {{ $task->end_date->format('M d, Y') }}
                                        &middot;
                                        @if($task->isExternal())
                                            <span class="text-amber-600 dark:text-amber-400">{{ $task->assigned_company }}</span>
                                        @else
                                            {{ $task->assignedUser?->name ?? '—' }}
                                        @endif
                                    </p>
                                    @if($task->notes)
                                        <p class="text-xs text-zinc-400 mt-0.5 italic">{{ $task->notes }}</p>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="flex shrink-0 items-center gap-1">
                                    @if($task->subtasks->isNotEmpty())
                                        <button x-on:click="open = !open" class="p-1 text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400 transition" title="Toggle subtasks">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.projects.tasks.report', [$project, $task]) }}" target="_blank" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                                        <flux:icon.printer class="size-3.5" />
                                    </a>
                                    <button wire:click="editTask({{ $task->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                                        <flux:icon.pencil class="size-3.5" />
                                    </button>
                                    <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" class="p-1 text-zinc-400 hover:text-red-500 transition">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            </div>

                            {{-- Accordion: subtasks --}}
                            @if($task->subtasks->isNotEmpty())
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                    style="display: none;"
                                    class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/30 rounded-b-xl px-4 py-3 space-y-2"
                                >
                                    @foreach($task->subtasks as $subtask)
                                        @php
                                            $subColor = match($subtask->status) {
                                                'in_progress' => 'blue',
                                                'completed'   => 'green',
                                                'delayed'     => 'red',
                                                'cancelled'   => 'zinc',
                                                default       => 'zinc',
                                            };
                                            $subLabel = match($subtask->status) {
                                                'in_progress' => 'In Progress',
                                                'completed'   => 'Completed',
                                                'delayed'     => 'Delayed',
                                                'cancelled'   => 'Cancelled',
                                                default       => 'Pending',
                                            };
                                        @endphp
                                        <div class="flex items-center gap-3 pl-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5">
                                            {{-- Indent line --}}
                                            <div class="w-px h-4 bg-blue-300 dark:bg-blue-700 shrink-0"></div>
                                            {{-- Status dot --}}
                                            <div class="shrink-0">
                                                @if($subtask->status === 'completed')
                                                    <div class="flex size-4 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                                        <svg class="size-2.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    </div>
                                                @else
                                                    <div class="size-4 rounded-full border-2 border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800"></div>
                                                @endif
                                            </div>
                                            {{-- Name --}}
                                            <span class="flex-1 text-xs text-zinc-700 dark:text-zinc-300">{{ $subtask->name }}</span>
                                            {{-- Assigned --}}
                                            @if($subtask->assignedUser)
                                                <span class="text-xs text-zinc-400">
                                                    {{ $subtask->assignedUser->name }}
                                                    @if($subtask->assignedUser->phone)
                                                        · <a href="tel:{{ $subtask->assignedUser->phone }}" class="hover:text-blue-500 transition">{{ $subtask->assignedUser->phone }}</a>
                                                    @endif
                                                </span>
                                            @endif
                                            {{-- Status badge --}}
                                            <button wire:click="cycleSubtaskStatus({{ $subtask->id }})" title="Click to change status" class="cursor-pointer">
                                                <flux:badge size="sm" color="{{ $subColor }}">{{ $subLabel }}</flux:badge>
                                            </button>
                                            {{-- Delete --}}
                                            <button wire:click="deleteSubtask({{ $subtask->id }})" wire:confirm="Delete this subtask?" class="p-0.5 text-zinc-300 hover:text-red-400 transition">
                                                <flux:icon.trash class="size-3" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-zinc-400">No tasks yet. Add the first one.</p>
            @endif
        </div>

        {{-- Task Modal --}}
        <flux:modal name="task-form" class="w-full max-w-xl" :dismissible="false" @cancel="$wire.cancelTaskEdit()">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingTaskId ? 'Edit Task' : 'New Task' }}</flux:heading>

                <flux:input wire:model="taskName" label="Task Name" placeholder="e.g. Foundation work" />
                <flux:textarea wire:model="taskDescription" label="Description (optional)" rows="2" />

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="taskStartDate" label="Start Date" type="date" />
                    <flux:input wire:model="taskEndDate" label="End Date" type="date" />
                </div>

                <flux:select wire:model="taskStatus" label="Status">
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="in_progress">In Progress</flux:select.option>
                    <flux:select.option value="completed">Completed</flux:select.option>
                    <flux:select.option value="delayed">Delayed</flux:select.option>
                </flux:select>

                {{-- Assignment type --}}
                <div>
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Assign to</p>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="taskAssignedType" value="internal" class="accent-blue-600" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Staff interno</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="taskAssignedType" value="external" class="accent-blue-600" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Compañía externa</span>
                        </label>
                    </div>
                </div>

                @if($taskAssignedType === 'internal')
                    <div wire:key="assign-internal">
                        <flux:select wire:model="taskAssignedUserId" label="Staff Member">
                            <flux:select.option value="">— Select person —</flux:select.option>
                            @foreach($staff as $member)
                                <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @else
                    <div wire:key="assign-external">
                        <flux:select wire:model="taskAssignedContractorId" label="Contractor">
                            <flux:select.option value="">— Select contractor —</flux:select.option>
                            @foreach($contractors as $contractor)
                                <flux:select.option value="{{ $contractor->id }}">
                                    {{ $contractor->company_name }}@if($contractor->specialty) — {{ $contractor->specialty }}@endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                <flux:input wire:model="taskNotes" label="Notes (optional)" placeholder="Additional details..." />

                {{-- Subtasks (only when editing an existing task) --}}
                @if($editingTaskId)
                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Subtasks</p>

                        @if($subtasks->isNotEmpty())
                            <ul class="mb-3 space-y-1">
                                @foreach($subtasks as $subtask)
                                    @php
                                        $subColor = match($subtask->status) {
                                            'in_progress' => 'blue',
                                            'completed'   => 'green',
                                            'delayed'     => 'yellow',
                                            'cancelled'   => 'red',
                                            default       => 'zinc',
                                        };
                                        $subLabel = match($subtask->status) {
                                            'in_progress' => 'In Progress',
                                            'completed'   => 'Completed',
                                            'delayed'     => 'Delayed',
                                            'cancelled'   => 'Cancelled',
                                            default       => 'Pending',
                                        };
                                    @endphp
                                    <li class="flex items-center gap-2 rounded-lg px-3 py-2 bg-zinc-50 dark:bg-zinc-800">
                                        <span class="flex-1 text-sm text-zinc-700 dark:text-zinc-200">{{ $subtask->name }}</span>
                                        <button wire:click="cycleSubtaskStatus({{ $subtask->id }})" title="Click to change status" class="cursor-pointer">
                                            <flux:badge size="sm" color="{{ $subColor }}">{{ $subLabel }}</flux:badge>
                                        </button>
                                        <select
                                            x-on:change="$wire.updateSubtaskAssignee({{ $subtask->id }}, $event.target.value)"
                                            class="rounded-md border-0 bg-transparent py-0.5 pl-1 pr-6 text-xs text-zinc-500 dark:text-zinc-400 ring-1 ring-zinc-200 dark:ring-zinc-700 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                        >
                                            <option value="">— Unassigned —</option>
                                            @foreach($staff as $member)
                                                <option value="{{ $member->id }}" {{ $subtask->assigned_user_id == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="deleteSubtask({{ $subtask->id }})" wire:confirm="Delete this subtask?" class="text-zinc-300 hover:text-red-500 transition">
                                            <flux:icon.trash class="size-3.5" />
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="flex gap-2">
                            <flux:input wire:model="subtaskName" placeholder="New subtask name..." class="flex-1" />
                            <flux:button wire:click="addSubtask" size="sm" icon="plus">Add</flux:button>
                        </div>
                    </div>
                @endif

                <div class="flex gap-2 pt-1">
                    <flux:button wire:click="saveTask" variant="primary">
                        {{ $editingTaskId ? 'Update Task' : 'Save Task' }}
                    </flux:button>
                    <flux:modal.close>
                        <flux:button wire:click="cancelTaskEdit" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>

        {{-- Income --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300">Income</h3>
                <div class="flex items-center gap-3">
                    @if($totalIncome > 0)
                        <span class="text-xs font-semibold text-green-600 dark:text-green-400">
                            Total: ${{ number_format($totalIncome, 0) }}
                        </span>
                    @endif
                    <flux:modal.trigger name="income-form">
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="newIncome">
                            Add Income
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            {{-- Income list --}}
            @if($incomes->isEmpty())
                <p class="text-sm text-zinc-400 text-center py-4">No income entries yet.</p>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
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
        </div>

        {{-- Income Modal --}}
        <flux:modal name="income-form" class="w-full max-w-lg" :dismissible="false" @cancel="$wire.cancelIncomeEdit()">
            <div class="flex flex-col gap-4 p-1">
                <flux:heading size="lg">{{ $editingIncomeId ? 'Edit Income' : 'Add Income' }}</flux:heading>

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
                    <flux:button wire:click="saveIncome" variant="primary">
                        {{ $editingIncomeId ? 'Update Income' : 'Add Income' }}
                    </flux:button>
                    <flux:modal.close>
                        <flux:button wire:click="cancelIncomeEdit" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>

        {{-- Expenses --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300">Expenses</h3>
                <div class="flex items-center gap-3">
                    @if($project->budget)
                        <span class="text-xs text-zinc-400">
                            Budget: <span class="font-semibold text-zinc-700 dark:text-zinc-200">${{ number_format($project->budget, 0) }}</span>
                        </span>
                    @endif
                    <flux:modal.trigger name="expense-form">
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="newExpense">
                            Add Expense
                        </flux:button>
                    </flux:modal.trigger>
                </div>
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
            @if($expenses->isEmpty())
                <p class="text-sm text-zinc-400 text-center py-4">No expense entries yet.</p>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    @foreach($expenses as $expense)
                        <div class="flex items-start justify-between gap-3 px-4 py-3">
                            {{-- Receipt thumbnail --}}
                            @if($expense->receipt_path)
                                <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank" class="shrink-0">
                                    <img src="{{ Storage::url($expense->receipt_path) }}"
                                         class="h-12 w-12 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700 hover:opacity-80 transition"
                                         title="Ver recibo">
                                </a>
                            @else
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                                    <flux:icon.receipt-percent class="size-5 text-zinc-300 dark:text-zinc-600" />
                                </div>
                            @endif

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
        </div>

        {{-- Expense Modal --}}
        <flux:modal name="expense-form" class="w-full max-w-lg" :dismissible="false" @cancel="$wire.cancelExpenseEdit()">
            <div class="flex flex-col gap-4 p-1">
                <flux:heading size="lg">{{ $editingExpenseId ? 'Edit Expense' : 'Add Expense' }}</flux:heading>

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

                {{-- Receipt Photo --}}
                <div x-data="{ previewUrl: null }"
                     @receipt-reset.window="previewUrl = null; $refs.receiptInput && ($refs.receiptInput.value = '')"
                     class="flex flex-col gap-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <label class="cursor-pointer">
                            <input type="file"
                                   wire:model="receiptImage"
                                   accept="image/*"
                                   capture="environment"
                                   class="hidden"
                                   x-ref="receiptInput"
                                   @change="previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                            <flux:button type="button" icon="camera" variant="ghost" size="sm"
                                         @click.prevent="$refs.receiptInput.click()">
                                Capturar Recibo
                            </flux:button>
                        </label>
                        <div wire:loading wire:target="receiptImage" class="text-xs text-zinc-400">Subiendo...</div>

                        {{-- Scan button: appears once image is uploaded --}}
                        @if($receiptImage)
                            <flux:button type="button" icon="sparkles" variant="primary" size="sm"
                                         wire:click="scanReceipt"
                                         wire:loading.attr="disabled"
                                         wire:target="scanReceipt">
                                <span wire:loading.remove wire:target="scanReceipt">Scan Recibo</span>
                                <span wire:loading wire:target="scanReceipt">Escaneando...</span>
                            </flux:button>
                        @endif
                    </div>

                    {{-- Scan feedback --}}
                    @if(session('scan_success'))
                        <p class="text-xs font-medium text-green-600">{{ session('scan_success') }}</p>
                    @endif
                    @if(session('scan_error'))
                        <p class="text-xs font-medium text-red-500">{{ session('scan_error') }}</p>
                    @endif

                    {{-- New receipt preview --}}
                    <template x-if="previewUrl">
                        <div class="relative">
                            <img :src="previewUrl" class="h-28 w-full rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                            <button type="button"
                                    @click="previewUrl = null; $refs.receiptInput.value = ''; $wire.set('receiptImage', null)"
                                    class="absolute right-1 top-1 rounded-full bg-zinc-800/70 p-0.5 text-white transition hover:bg-red-500">
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </div>
                    </template>

                    {{-- Existing receipt (when editing) --}}
                    @if($receiptExistingPath && ! $receiptImage)
                        <div class="relative">
                            <img src="{{ Storage::url($receiptExistingPath) }}"
                                 class="h-28 w-full rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                            <span class="absolute left-1 top-1 rounded bg-zinc-800/70 px-1.5 py-0.5 text-[10px] text-white">Recibo guardado</span>
                        </div>
                    @endif
                </div>

                <div class="flex gap-2 pt-1">
                    <flux:button wire:click="saveExpense" variant="primary">
                        {{ $editingExpenseId ? 'Update Expense' : 'Add Expense' }}
                    </flux:button>
                    <flux:modal.close>
                        <flux:button wire:click="cancelExpenseEdit" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>

        {{-- Photos --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-yellow-400 dark:text-yellow-300 mb-4">Project Photos</h3>

            {{-- Image gallery --}}
            @if($photos->where('mime_type', '!=', 'application/pdf')->isNotEmpty())
                <div
                    x-data="{
                        lightbox: null,
                        drawing: false,
                        color: '#ef4444',
                        penSize: 4,
                        isEraser: false,
                        lastX: 0, lastY: 0,
                        saving: false,

                        openLightbox(url) {
                            this.lightbox = url;
                            this.isEraser = false;
                            this.$nextTick(() => { this.initCanvas(); });
                        },

                        initCanvas() {
                            const canvas = this.$refs.canvas;
                            const ctx = canvas.getContext('2d');
                            const img = new Image();
                            img.crossOrigin = 'anonymous';
                            img.onload = () => {
                                canvas.width = img.naturalWidth;
                                canvas.height = img.naturalHeight;
                                ctx.drawImage(img, 0, 0);
                            };
                            img.src = this.lightbox;
                        },

                        getPos(e) {
                            const canvas = this.$refs.canvas;
                            const rect = canvas.getBoundingClientRect();
                            const scaleX = canvas.width / rect.width;
                            const scaleY = canvas.height / rect.height;
                            const src = e.touches ? e.touches[0] : e;
                            return {
                                x: (src.clientX - rect.left) * scaleX,
                                y: (src.clientY - rect.top) * scaleY
                            };
                        },

                        startDraw(e) {
                            e.preventDefault();
                            this.drawing = true;
                            const pos = this.getPos(e);
                            this.lastX = pos.x; this.lastY = pos.y;
                        },

                        draw(e) {
                            e.preventDefault();
                            if (!this.drawing) return;
                            const canvas = this.$refs.canvas;
                            const ctx = canvas.getContext('2d');
                            const pos = this.getPos(e);
                            ctx.beginPath();
                            ctx.moveTo(this.lastX, this.lastY);
                            ctx.lineTo(pos.x, pos.y);
                            ctx.strokeStyle = this.isEraser ? '#ffffff' : this.color;
                            ctx.lineWidth = this.isEraser ? this.penSize * 6 : this.penSize;
                            ctx.lineCap = 'round';
                            ctx.lineJoin = 'round';
                            ctx.stroke();
                            this.lastX = pos.x; this.lastY = pos.y;
                        },

                        stopDraw() { this.drawing = false; },

                        clearCanvas() { this.initCanvas(); },

                        async saveAnnotated() {
                            this.saving = true;
                            const dataUrl = this.$refs.canvas.toDataURL('image/jpeg', 0.92);
                            await $wire.saveAnnotatedPhoto(dataUrl);
                            this.saving = false;
                            this.lightbox = null;
                        }
                    }"
                    class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-3"
                >
                    @foreach($photos->where('mime_type', '!=', 'application/pdf') as $photo)
                        <div class="group relative overflow-hidden rounded-xl border border-zinc-100 dark:border-zinc-800">
                            <img src="{{ $photo->url() }}"
                                x-on:click="openLightbox('{{ $photo->url() }}')"
                                class="h-32 w-full object-cover transition group-hover:scale-105 cursor-zoom-in" alt="">
                            <div class="pointer-events-none absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition group-hover:opacity-100">
                                <div class="pointer-events-auto flex items-center justify-end p-2">
                                    <button
                                        wire:click="deletePhoto({{ $photo->id }})"
                                        wire:confirm="Remove this photo?"
                                        class="flex size-8 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-red-500"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Annotation Lightbox --}}
                    <div
                        x-show="lightbox"
                        x-on:keydown.escape.window="lightbox = null"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex flex-col-reverse bg-black"
                        style="display: none;"
                        x-on:click.stop
                    >
                        {{-- Toolbar --}}
                        <div class="flex shrink-0 items-center gap-2 bg-zinc-900 px-3 py-2 overflow-x-auto">
                            {{-- Colors --}}
                            <template x-for="c in ['#ef4444','#3b82f6','#22c55e','#eab308','#ffffff','#18181b']">
                                <button
                                    x-on:click="color = c; isEraser = false"
                                    :style="'background:' + c"
                                    :class="color === c && !isEraser ? 'ring-2 ring-offset-1 ring-offset-zinc-900 ring-white' : ''"
                                    class="size-7 shrink-0 rounded-full border border-white/20 transition"
                                ></button>
                            </template>

                            <div class="mx-1 h-6 w-px bg-white/20 shrink-0"></div>

                            {{-- Pen size --}}
                            <template x-for="s in [2, 4, 8]">
                                <button
                                    x-on:click="penSize = s; isEraser = false"
                                    :class="penSize === s && !isEraser ? 'bg-white/30' : 'bg-white/10'"
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full transition"
                                >
                                    <div :style="'width:' + s + 'px; height:' + s + 'px'" class="rounded-full bg-white"></div>
                                </button>
                            </template>

                            <div class="mx-1 h-6 w-px bg-white/20 shrink-0"></div>

                            {{-- Eraser --}}
                            <button
                                x-on:click="isEraser = !isEraser"
                                :class="isEraser ? 'bg-amber-500 text-black' : 'bg-white/10 text-white'"
                                class="flex shrink-0 items-center gap-1 rounded-full px-3 py-1.5 text-xs font-medium transition"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m20.893 13.393-1.135-1.135a2.252 2.252 0 0 1 0-3.182l3.184-3.185a.5.5 0 0 0-.707-.707l-3.185 3.185a3.752 3.752 0 0 0 0 5.303l1.135 1.135c.141.14.33.219.526.219h2.5a.75.75 0 0 0 0-1.5h-2.012a.25.25 0 0 1-.177-.073ZM2.436 17.25a.75.75 0 0 1 0-1.06l9.19-9.19a.75.75 0 0 1 1.06 0l1.94 1.94a.75.75 0 0 1 0 1.06l-9.19 9.19a.75.75 0 0 1-1.06 0l-1.94-1.94Z"/></svg>
                                Eraser
                            </button>

                            {{-- Clear --}}
                            <button
                                x-on:click="clearCanvas()"
                                class="flex shrink-0 items-center gap-1 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-white/20"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Clear
                            </button>

                            <div class="ml-auto flex shrink-0 items-center gap-2">
                                {{-- Save --}}
                                <button
                                    x-on:click="saveAnnotated()"
                                    :disabled="saving"
                                    class="flex items-center gap-1 rounded-full bg-green-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-600 disabled:opacity-50"
                                >
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                                </button>

                                {{-- Close --}}
                                <button
                                    x-on:click="lightbox = null"
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/30"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Canvas --}}
                        <div class="flex flex-1 items-center justify-center overflow-hidden bg-zinc-950 p-2">
                            <canvas
                                x-ref="canvas"
                                x-on:mousedown="startDraw($event)"
                                x-on:mousemove="draw($event)"
                                x-on:mouseup="stopDraw()"
                                x-on:mouseleave="stopDraw()"
                                x-on:touchstart.passive="startDraw($event)"
                                x-on:touchmove="draw($event)"
                                x-on:touchend="stopDraw()"
                                :style="isEraser ? 'cursor: cell' : 'cursor: crosshair'"
                                class="max-h-full max-w-full rounded-lg object-contain shadow-2xl"
                            ></canvas>
                        </div>
                    </div>
                </div>
            @endif

            {{-- PDF list --}}
            @if($photos->where('mime_type', 'application/pdf')->isNotEmpty())
                <div
                    x-data="{
                        pdfUrl: null,
                        currentPage: 1,
                        totalPages: 0,
                        loading: false,
                        drawing: false,
                        color: '#ef4444',
                        penSize: 4,
                        isEraser: false,
                        lastX: 0, lastY: 0,
                        saving: false,

                        async openPdf(url) {
                            this.pdfUrl = url;
                            this.currentPage = 1;
                            this.isEraser = false;
                            this.loading = true;
                            await this.$nextTick();
                            const pdfjsLib = window['pdfjs-dist/build/pdf'];
                            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                            window._pdfDoc = await pdfjsLib.getDocument(url).promise;
                            this.totalPages = window._pdfDoc.numPages;
                            await this.renderPage(this.currentPage);
                            this.loading = false;
                        },

                        async renderPage(num) {
                            this.loading = true;
                            const page = await window._pdfDoc.getPage(num);
                            const canvas = this.$refs.pdfCanvas;
                            const viewport = page.getViewport({ scale: 2 });
                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            canvas.style.width = '';
                            canvas.style.height = '';
                            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                            this.loading = false;
                        },

                        async prevPage() {
                            if (this.currentPage <= 1) return;
                            this.currentPage--;
                            await this.renderPage(this.currentPage);
                        },

                        async nextPage() {
                            if (this.currentPage >= this.totalPages) return;
                            this.currentPage++;
                            await this.renderPage(this.currentPage);
                        },

                        getPos(e) {
                            const canvas = this.$refs.pdfCanvas;
                            const rect = canvas.getBoundingClientRect();
                            const scaleX = canvas.width / rect.width;
                            const scaleY = canvas.height / rect.height;
                            const src = e.touches ? e.touches[0] : e;
                            return {
                                x: (src.clientX - rect.left) * scaleX,
                                y: (src.clientY - rect.top) * scaleY
                            };
                        },

                        startDraw(e) {
                            e.preventDefault();
                            this.drawing = true;
                            const pos = this.getPos(e);
                            this.lastX = pos.x; this.lastY = pos.y;
                        },

                        draw(e) {
                            e.preventDefault();
                            if (!this.drawing) return;
                            const canvas = this.$refs.pdfCanvas;
                            const ctx = canvas.getContext('2d');
                            const pos = this.getPos(e);
                            ctx.beginPath();
                            ctx.moveTo(this.lastX, this.lastY);
                            ctx.lineTo(pos.x, pos.y);
                            ctx.strokeStyle = this.isEraser ? '#ffffff' : this.color;
                            ctx.lineWidth = this.isEraser ? this.penSize * 6 : this.penSize;
                            ctx.lineCap = 'round';
                            ctx.lineJoin = 'round';
                            ctx.stroke();
                            this.lastX = pos.x; this.lastY = pos.y;
                        },

                        stopDraw() { this.drawing = false; },

                        async clearPage() { await this.renderPage(this.currentPage); },

                        async saveAnnotated() {
                            this.saving = true;
                            const dataUrl = this.$refs.pdfCanvas.toDataURL('image/jpeg', 0.92);
                            await $wire.saveAnnotatedPhoto(dataUrl);
                            this.saving = false;
                            this.pdfUrl = null;
                        }
                    }"
                    class="mb-5 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800"
                >
                    @foreach($photos->where('mime_type', 'application/pdf') as $photo)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <a href="{{ $photo->url() }}" target="_blank"
                                class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $photo->original_name ?? basename($photo->path) }}
                            </a>
                            <button
                                x-on:click="openPdf('{{ $photo->url() }}')"
                                class="shrink-0 p-1.5 text-zinc-400 transition hover:text-amber-500"
                                title="Annotate"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                            </button>
                            <button
                                wire:click="deletePhoto({{ $photo->id }})"
                                wire:confirm="Remove this file?"
                                class="shrink-0 p-1.5 text-zinc-400 transition hover:text-red-500"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    @endforeach

                    {{-- PDF Annotation Modal --}}
                    <div
                        x-show="pdfUrl"
                        x-on:keydown.escape.window="pdfUrl = null"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex flex-col-reverse bg-black"
                        style="display: none;"
                        x-on:click.stop
                    >
                        {{-- Toolbar (now at bottom) --}}
                        <div class="flex w-full shrink-0 items-center gap-2 bg-zinc-900 px-3 py-2 overflow-x-auto">
                            {{-- Colors --}}
                            <template x-for="c in ['#ef4444','#3b82f6','#22c55e','#eab308','#ffffff','#18181b']">
                                <button
                                    x-on:click="color = c; isEraser = false"
                                    :style="'background:' + c"
                                    :class="color === c && !isEraser ? 'ring-2 ring-offset-1 ring-offset-zinc-900 ring-white' : ''"
                                    class="size-7 shrink-0 rounded-full border border-white/20 transition"
                                ></button>
                            </template>

                            <div class="mx-1 h-6 w-px bg-white/20 shrink-0"></div>

                            <template x-for="s in [2, 4, 8]">
                                <button
                                    x-on:click="penSize = s; isEraser = false"
                                    :class="penSize === s && !isEraser ? 'bg-white/30' : 'bg-white/10'"
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full transition"
                                >
                                    <div :style="'width:' + s + 'px; height:' + s + 'px'" class="rounded-full bg-white"></div>
                                </button>
                            </template>

                            <div class="mx-1 h-6 w-px bg-white/20 shrink-0"></div>

                            <button x-on:click="isEraser = !isEraser"
                                :class="isEraser ? 'bg-amber-500 text-black' : 'bg-white/10 text-white'"
                                class="flex shrink-0 items-center gap-1 rounded-full px-3 py-1.5 text-xs font-medium transition">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m20.893 13.393-1.135-1.135a2.252 2.252 0 0 1 0-3.182l3.184-3.185a.5.5 0 0 0-.707-.707l-3.185 3.185a3.752 3.752 0 0 0 0 5.303l1.135 1.135c.141.14.33.219.526.219h2.5a.75.75 0 0 0 0-1.5h-2.012a.25.25 0 0 1-.177-.073ZM2.436 17.25a.75.75 0 0 1 0-1.06l9.19-9.19a.75.75 0 0 1 1.06 0l1.94 1.94a.75.75 0 0 1 0 1.06l-9.19 9.19a.75.75 0 0 1-1.06 0l-1.94-1.94Z"/></svg>
                                Eraser
                            </button>

                            <button x-on:click="clearPage()"
                                class="flex shrink-0 items-center gap-1 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-white/20">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Clear
                            </button>

                            {{-- Page navigation --}}
                            <div x-show="totalPages > 1" class="flex shrink-0 items-center gap-1">
                                <div class="mx-1 h-6 w-px bg-white/20"></div>
                                <button x-on:click="prevPage()" :disabled="currentPage <= 1"
                                    class="flex size-7 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-30">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                </button>
                                <span class="text-xs text-zinc-400" x-text="currentPage + ' / ' + totalPages"></span>
                                <button x-on:click="nextPage()" :disabled="currentPage >= totalPages"
                                    class="flex size-7 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-30">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            </div>

                            <div class="ml-auto flex shrink-0 items-center gap-2">
                                <button x-on:click="saveAnnotated()" :disabled="saving || loading"
                                    class="flex items-center gap-1 rounded-full bg-green-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-600 disabled:opacity-50">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    <span x-text="saving ? 'Saving...' : 'Save as Image'"></span>
                                </button>
                                <button x-on:click="pdfUrl = null"
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/30">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Canvas --}}
                        <div class="relative flex flex-1 items-center justify-center overflow-hidden bg-zinc-950 p-2" style="min-height:0">
                            <div x-show="loading" class="absolute text-sm text-zinc-400">Loading PDF...</div>
                            <canvas
                                x-ref="pdfCanvas"
                                x-show="!loading"
                                x-on:mousedown="startDraw($event)"
                                x-on:mousemove="draw($event)"
                                x-on:mouseup="stopDraw()"
                                x-on:mouseleave="stopDraw()"
                                x-on:touchstart.passive="startDraw($event)"
                                x-on:touchmove="draw($event)"
                                x-on:touchend="stopDraw()"
                                :style="isEraser ? 'cursor: cell; max-width:100%; max-height:100%; object-fit:contain' : 'cursor: crosshair; max-width:100%; max-height:100%; object-fit:contain'"
                                class="rounded-lg shadow-2xl"
                            ></canvas>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Upload new files --}}
            <div
                x-data="{
                    uploading: false,
                    ready: false,
                    compressImage(file, maxBytes = 900 * 1024) {
                        return new Promise((resolve) => {
                            if (!file.type.startsWith('image/') || file.size <= maxBytes) { resolve(file); return; }
                            const img = new Image();
                            const url = URL.createObjectURL(file);
                            img.onload = () => {
                                URL.revokeObjectURL(url);
                                const canvas = document.createElement('canvas');
                                let { width, height } = img;
                                const scale = Math.sqrt(maxBytes / file.size);
                                width = Math.round(width * scale);
                                height = Math.round(height * scale);
                                canvas.width = width;
                                canvas.height = height;
                                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                                const mime = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                                let quality = 0.85;
                                const tryCompress = () => {
                                    canvas.toBlob((blob) => {
                                        if (blob.size <= maxBytes || quality <= 0.3) {
                                            resolve(new File([blob], file.name, { type: mime }));
                                        } else {
                                            quality -= 0.1;
                                            canvas.toBlob((b) => resolve(new File([b], file.name, { type: mime })), mime, quality);
                                        }
                                    }, mime, quality);
                                };
                                tryCompress();
                            };
                            img.src = url;
                        });
                    },
                    async uploadFiles(files) {
                        if (!files.length) return;
                        this.uploading = true;
                        this.ready = false;
                        const processed = await Promise.all(files.map(f => this.compressImage(f)));
                        $wire.uploadMultiple('uploads', processed,
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
                <input type="file" multiple accept="image/*,application/pdf" class="hidden" id="project-photo-input"
                    @change="uploadFiles(Array.from($event.target.files))">

                <label for="project-photo-input" class="cursor-pointer">
                    <flux:icon.photo class="mx-auto size-8 text-zinc-300" />
                    <p class="mt-2 text-sm text-zinc-500">Drop files here or <span class="text-blue-600 underline">browse</span></p>
                    <p class="text-xs text-zinc-400">JPG, PNG, WEBP, PDF — max 10MB each</p>
                </label>

                <div x-show="uploading" class="mt-3 text-sm text-zinc-500">Uploading...</div>

                <div x-show="ready" class="mt-3">
                    <flux:button wire:click="savePhotos" variant="primary" size="sm">Save Files</flux:button>
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
@endpush
