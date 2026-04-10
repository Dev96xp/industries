<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public bool $showArchived = false;

    public function archiveProject(Project $project): void
    {
        $project->update(['is_archived' => true]);
        session()->flash('success', "Project \"{$project->name}\" archived.");
    }

    public function restoreProject(Project $project): void
    {
        $project->update(['is_archived' => false]);
        session()->flash('success', "Project \"{$project->name}\" restored.");
    }

    public function with(): array
    {
        $query = Project::with(['client', 'photos'])
            ->where('is_archived', $this->showArchived)
            ->orderByDesc('created_at');

        return [
            'projects'       => $query->get(),
            'archivedCount'  => Project::where('is_archived', true)->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Projects</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage construction projects.</flux:text>
        </div>
        <div class="flex items-center gap-3">
            @if($archivedCount > 0)
                <button wire:click="$toggle('showArchived')"
                    class="flex items-center gap-1.5 text-sm transition {{ $showArchived ? 'text-amber-600 font-semibold' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    <flux:icon.archive-box class="size-4" />
                    {{ $showArchived ? 'Hide Archived' : "Archived ({$archivedCount})" }}
                </button>
            @endif
            @if(! $showArchived)
                <flux:button href="{{ route('admin.projects.create') }}" variant="primary" icon="plus" wire:navigate>
                    New Project
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Archived banner --}}
    @if($showArchived)
        <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-400">
            <flux:icon.archive-box class="size-4 shrink-0" />
            Showing archived projects. These are hidden from normal view and can be restored at any time.
        </div>
    @endif

    {{-- Project cards --}}
    @if($projects->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.folder-open class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">
                {{ $showArchived ? 'No archived projects.' : 'No projects yet. Create your first one.' }}
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($projects as $project)
                <div class="flex flex-col rounded-2xl border overflow-hidden transition
                    {{ $project->is_archived
                        ? 'border-zinc-200 bg-zinc-50 opacity-75 dark:border-zinc-700 dark:bg-zinc-900/50'
                        : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">

                    {{-- Cover photo --}}
                    @if($project->photos->first())
                        <img src="{{ $project->photos->first()->url() }}" alt="{{ $project->name }}"
                            class="h-40 w-full object-cover {{ $project->is_archived ? 'grayscale' : '' }}">
                    @else
                        <div class="flex h-40 items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.photo class="size-10 text-zinc-300" />
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col gap-3 p-5">
                        {{-- Status badge --}}
                        <div class="flex items-center justify-between">
                            <flux:badge color="{{ match($project->status) { 'planning' => 'amber', 'in_progress' => 'blue', 'completed' => 'green' } }}" size="sm">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </flux:badge>
                            <div class="flex items-center gap-1.5">
                                @if($project->is_archived)
                                    <flux:badge color="zinc" size="sm">Archived</flux:badge>
                                @endif
                                @if($project->is_featured)
                                    <flux:badge color="purple" size="sm">Featured</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div>
                            @if($project->number)
                                <p class="text-xs font-mono text-zinc-400 mb-0.5">{{ $project->number }}</p>
                            @endif
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $project->name }}</h3>
                            @if($project->address)
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($project->address) }}" target="_blank" class="mt-0.5 block text-xs text-zinc-400 hover:text-blue-500 transition">{{ $project->address }}</a>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-xs text-zinc-400 mt-auto pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <div>
                                <span>{{ $project->client?->name ?? 'No client' }}</span>
                                @if($project->client?->phone)
                                    <a href="tel:{{ $project->client->phone }}" class="ml-2 hover:text-blue-500 transition">{{ $project->client->phone }}</a>
                                @endif
                            </div>
                            @if($project->budget)
                                <span>${{ number_format($project->budget, 0) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex border-t border-zinc-100 dark:border-zinc-800">
                        @if($project->is_archived)
                            <button wire:click="restoreProject({{ $project->id }})"
                                class="flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-green-600 transition hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-900/20">
                                <flux:icon.arrow-up-tray class="size-3.5" /> Restore
                            </button>
                        @else
                            <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate
                                class="flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                <flux:icon.pencil class="size-3.5" /> Edit
                            </a>
                            <div class="w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <button wire:click="archiveProject({{ $project->id }})"
                                wire:confirm="Archive '{{ $project->name }}'? It will be hidden but can be restored later."
                                class="flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-amber-500 transition hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20">
                                <flux:icon.archive-box class="size-3.5" /> Archive
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
