<?php

use App\Models\Project;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name                     = '';
    public string $description              = '';
    public string $status                   = 'planning';
    public string $address                  = '';
    public string $start_date               = '';
    public string $estimated_completion_date = '';
    public string $budget                   = '';
    public ?int   $client_user_id           = null;
    public string $internal_notes           = '';
    public bool   $is_featured              = false;

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
        ]);

        $project = Project::create(array_merge($validated, ['number' => Project::generateNumber()]));

        session()->flash('success', 'Project "' . $project->name . '" created.');

        $this->redirect(route('admin.projects'), navigate: true);
    }

    public function with(): array
    {
        $clientRole = Role::where('name', 'client')->first();

        return [
            'clients' => $clientRole
                ? User::role('client')->orderBy('name')->get()
                : collect(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.projects') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <div>
            <flux:heading size="xl">New Project</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Fill in the project details below.</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6 max-w-3xl">

        {{-- Basic info --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Project Info</h3>
            <div class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Project Name" placeholder="e.g. Smith Residence" required />
                <flux:textarea wire:model="description" label="Description" rows="3" placeholder="Brief description of the project..." />
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
                <flux:input wire:model="address" label="Address / Location" placeholder="123 Main St, City, State" />
            </div>
        </div>

        {{-- Dates & Budget --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Schedule & Budget</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="start_date" label="Start Date" type="date" />
                <flux:input wire:model="estimated_completion_date" label="Est. Completion" type="date" />
                <flux:input wire:model="budget" label="Budget ($)" type="number" step="100" placeholder="0.00" />
            </div>
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
            <flux:button type="submit" variant="primary">Create Project</flux:button>
            <flux:button href="{{ route('admin.projects') }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>

    </form>

</div>
