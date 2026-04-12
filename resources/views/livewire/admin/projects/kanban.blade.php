<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public const STATUSES = [
        'draft'       => ['label' => 'Draft',       'color' => 'zinc',   'icon' => 'pencil'],
        'planning'    => ['label' => 'Planning',     'color' => 'blue',   'icon' => 'clipboard-document-list'],
        'in_progress' => ['label' => 'In Progress',  'color' => 'amber',  'icon' => 'wrench-screwdriver'],
        'on_hold'     => ['label' => 'On Hold',      'color' => 'orange', 'icon' => 'pause-circle'],
        'completed'   => ['label' => 'Completed',    'color' => 'green',  'icon' => 'check-circle'],
        'cancelled'   => ['label' => 'Cancelled',    'color' => 'red',    'icon' => 'x-circle'],
    ];

    public function moveForward(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $keys = array_keys(self::STATUSES);
        $current = array_search($project->status, $keys);

        if ($current !== false && $current < count($keys) - 1) {
            $project->update(['status' => $keys[$current + 1]]);
        }
    }

    public function moveBackward(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $keys = array_keys(self::STATUSES);
        $current = array_search($project->status, $keys);

        if ($current !== false && $current > 0) {
            $project->update(['status' => $keys[$current - 1]]);
        }
    }

    public function moveToStatus(int $projectId, string $status): void
    {
        if (! array_key_exists($status, self::STATUSES)) {
            return;
        }

        Project::findOrFail($projectId)->update(['status' => $status]);
    }

    public function with(): array
    {
        $projects = Project::with('client')
            ->where('is_archived', false)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('status');

        return [
            'statuses' => self::STATUSES,
            'projects' => $projects,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Project Board</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Move projects across stages as they progress.</flux:text>
        </div>
        <flux:button :href="route('admin.projects.create')" variant="primary" icon="plus" wire:navigate>
            New Project
        </flux:button>
    </div>

    {{-- Kanban Board --}}
    <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 70vh;">
        @foreach($statuses as $statusKey => $config)
            @php $columnProjects = $projects->get($statusKey, collect()); @endphp

            <div class="flex w-64 min-w-64 max-w-64 shrink-0 flex-col gap-3"
                 x-data="{ isDragOver: false }"
                 @dragover.prevent="isDragOver = true"
                 @dragleave="isDragOver = false"
                 @drop.prevent="isDragOver = false; $wire.moveToStatus(parseInt($event.dataTransfer.getData('projectId')), '{{ $statusKey }}')">

                {{-- Column Header --}}
                <div class="flex w-full items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"
                     :class="isDragOver ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : ''">
                    <flux:icon :icon="$config['icon']" class="size-4 text-zinc-500" />
                    <span class="flex-1 text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $config['label'] }}</span>
                    <flux:badge :color="$config['color']" size="sm">{{ $columnProjects->count() }}</flux:badge>
                </div>

                {{-- Cards --}}
                <div class="flex flex-col gap-3">
                    @forelse($columnProjects as $project)
                        <div wire:key="project-{{ $project->id }}"
                             x-data="{ expanded: false, dragging: false }"
                             draggable="true"
                             @dragstart="dragging = true; $event.dataTransfer.setData('projectId', '{{ $project->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                             @dragend="dragging = false"
                             :class="dragging ? 'opacity-40 scale-95' : ''"
                             class="cursor-grab rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition-all active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-900">

                            {{-- Project name + number + days --}}
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate
                                   class="text-left text-sm font-semibold leading-snug hover:text-blue-600 dark:hover:text-blue-400 {{ $project->status === 'completed' ? 'text-green-600 dark:text-green-400' : ($project->status === 'cancelled' ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                                    {{ $project->name }}
                                </a>
                                <div class="flex shrink-0 flex-col items-end gap-0.5">
                                    <span class="font-mono text-xs text-zinc-400">{{ $project->number }}</span>
                                    @if($project->start_date)
                                        <span class="font-mono text-xs font-semibold text-blue-500">
                                            {{ (int) $project->start_date->diffInDays(now()) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Client — click to expand dates --}}
                            @if($project->client)
                                <button @click="expanded = !expanded"
                                        class="mb-2 text-left text-xs text-zinc-500 hover:text-blue-500 transition">
                                    {{ $project->client->name }}
                                </button>
                            @endif

                            {{-- Dates — visible only when expanded --}}
                            @if($project->start_date || $project->estimated_completion_date)
                                <div x-show="expanded"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 -translate-y-1"
                                     class="mb-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-400">
                                    @if($project->start_date)
                                        <span>Start: {{ $project->start_date->format('M d, Y') }}</span>
                                    @endif
                                    @if($project->estimated_completion_date)
                                        <span>Est: {{ $project->estimated_completion_date->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Navigation buttons --}}
                            <div class="flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                @php
                                    $keys = array_keys($statuses);
                                    $currentIndex = array_search($project->status, $keys);
                                    $isFirst = $currentIndex === 0;
                                    $isLast  = $currentIndex === count($keys) - 1;
                                @endphp

                                <flux:button
                                    wire:click="moveBackward({{ $project->id }})"
                                    wire:loading.attr="disabled"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-left"
                                    :disabled="$isFirst"
                                >Back</flux:button>

                                <flux:button
                                    wire:click="moveForward({{ $project->id }})"
                                    wire:loading.attr="disabled"
                                    size="xs"
                                    variant="filled"
                                    icon-trailing="chevron-right"
                                    :disabled="$isLast"
                                >Next</flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-200 p-6 text-center dark:border-zinc-700"
                             :class="isDragOver ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : ''">
                            <p class="text-xs text-zinc-400">No projects</p>
                        </div>
                    @endforelse

                    {{-- Drop zone indicator --}}
                    <div x-show="isDragOver"
                         class="rounded-xl border-2 border-dashed border-blue-400 bg-blue-50 p-4 text-center dark:bg-blue-900/20">
                        <p class="text-xs font-medium text-blue-500">Drop here</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
