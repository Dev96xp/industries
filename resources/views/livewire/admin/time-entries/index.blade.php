<?php

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $filterProject = '';
    public string $filterWorker  = '';
    public string $filterDate    = '';

    public function with(): array
    {
        $query = TimeEntry::with(['worker', 'project'])
            ->orderByDesc('clock_in_at');

        if ($this->filterProject) {
            $query->where('project_id', $this->filterProject);
        }

        if ($this->filterWorker) {
            $query->where('user_id', $this->filterWorker);
        }

        if ($this->filterDate) {
            $query->whereDate('clock_in_at', $this->filterDate);
        }

        $entries = $query->get();

        $totalMinutes = $entries
            ->whereNotNull('clock_out_at')
            ->sum(fn ($e) => $e->duration_minutes);

        $projects = Project::orderBy('name')->get(['id', 'name', 'number']);
        $workers  = User::role('worker')->orderBy('name')->get(['id', 'name']);

        return compact('entries', 'totalMinutes', 'projects', 'workers');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Time Entries</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Worker clock-in/out records.</flux:text>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <flux:select wire:model.live="filterProject" placeholder="All Projects">
            <flux:select.option value="">All Projects</flux:select.option>
            @foreach($projects as $project)
                <flux:select.option value="{{ $project->id }}">
                    {{ $project->number ? $project->number . ' — ' : '' }}{{ $project->name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterWorker" placeholder="All Workers">
            <flux:select.option value="">All Workers</flux:select.option>
            @foreach($workers as $worker)
                <flux:select.option value="{{ $worker->id }}">{{ $worker->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="filterDate" type="date" placeholder="Filter by date" />
    </div>

    {{-- Summary --}}
    @php
        $hours = intdiv($totalMinutes, 60);
        $mins  = $totalMinutes % 60;
        $totalFormatted = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    @endphp
    <div class="flex items-center gap-6 rounded-2xl border border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <p class="text-xs text-zinc-500 uppercase tracking-wider">Total Hours</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalMinutes > 0 ? $totalFormatted : '—' }}</p>
        </div>
        <div class="h-10 w-px bg-zinc-100 dark:bg-zinc-800"></div>
        <div>
            <p class="text-xs text-zinc-500 uppercase tracking-wider">Entries</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $entries->count() }}</p>
        </div>
        <div class="h-10 w-px bg-zinc-100 dark:bg-zinc-800"></div>
        <div>
            <p class="text-xs text-zinc-500 uppercase tracking-wider">Active Now</p>
            <p class="text-2xl font-bold text-green-600">{{ $entries->whereNull('clock_out_at')->count() }}</p>
        </div>
    </div>

    {{-- Table --}}
    @if($entries->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.clock class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">No time entries found.</p>
        </div>
    @else
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Worker</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Project</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Clock In</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Clock Out</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Duration</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500">Location</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($entries as $entry)
                        <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex size-7 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">
                                        {{ $entry->worker->initials() }}
                                    </div>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $entry->worker->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $entry->project->name }}</p>
                                @if($entry->project->number)
                                    <p class="text-xs font-mono text-zinc-400">{{ $entry->project->number }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-500">
                                <p>{{ $entry->clock_in_at->format('M d, Y') }}</p>
                                <p class="text-xs">{{ $entry->clock_in_at->format('g:i A') }}</p>
                            </td>
                            <td class="px-6 py-4 text-zinc-500">
                                @if($entry->clock_out_at)
                                    <p>{{ $entry->clock_out_at->format('M d, Y') }}</p>
                                    <p class="text-xs">{{ $entry->clock_out_at->format('g:i A') }}</p>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-green-600 font-semibold">
                                        <span class="relative flex size-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
                                        </span>
                                        Active
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $entry->formatted_duration }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(! $entry->clock_in_lat)
                                    <span class="text-xs text-zinc-300">—</span>
                                @elseif($entry->clock_in_verified && ($entry->clock_out_at === null || $entry->clock_out_verified))
                                    <flux:badge color="green" size="sm">Verified</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">⚠ Outside area</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
