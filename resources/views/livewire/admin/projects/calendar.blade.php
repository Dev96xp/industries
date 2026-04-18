<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public const STATUS_COLORS = [
        'draft'       => '#71717a',
        'planning'    => '#3b82f6',
        'in_progress' => '#f59e0b',
        'on_hold'     => '#f97316',
        'completed'   => '#22c55e',
        'cancelled'   => '#ef4444',
    ];

    public function updateProjectDates(int $id, string $start, ?string $end): void
    {
        $project = Project::findOrFail($id);

        // FullCalendar end dates are exclusive, so subtract 1 day
        $endDate = $end
            ? \Carbon\Carbon::parse($end)->subDay()
            : \Carbon\Carbon::parse($start);

        $project->update([
            'start_date'                => $start,
            'estimated_completion_date' => $endDate,
        ]);
    }

    public function getEventsProperty(): array
    {
        return Project::query()
            ->whereNotNull('start_date')
            ->where('is_archived', false)
            ->with('client')
            ->get()
            ->map(function (Project $project) {
                $end = $project->estimated_completion_date
                    ? $project->estimated_completion_date->addDay()->toDateString()
                    : $project->start_date->addDay()->toDateString();

                return [
                    'id'    => $project->id,
                    'title' => $project->number . ' — ' . $project->name,
                    'start' => $project->start_date->toDateString(),
                    'end'   => $end,
                    'color' => self::STATUS_COLORS[$project->status] ?? '#71717a',
                    'url'   => route('admin.projects.edit', $project),
                    'extendedProps' => [
                        'status' => $project->status,
                        'client' => $project->client?->name,
                    ],
                ];
            })
            ->toArray();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6"
    x-data
    x-init="$nextTick(() => initProjectCalendar($el, @js($this->events), $wire))">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl">Project Calendar</flux:heading>
            <flux:subheading>Timeline of all active projects by start and estimated completion date.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button :href="route('admin.projects')" icon="list-bullet" size="sm" wire:navigate>List</flux:button>
            <flux:button :href="route('admin.projects.kanban')" icon="view-columns" size="sm" wire:navigate>Board</flux:button>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-3">
        @foreach(['draft' => ['zinc', 'Draft'], 'planning' => ['blue', 'Planning'], 'in_progress' => ['amber', 'In Progress'], 'on_hold' => ['orange', 'On Hold'], 'completed' => ['green', 'Completed'], 'cancelled' => ['red', 'Cancelled']] as $status => [$color, $label])
            <span class="inline-flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                <span class="size-2.5 rounded-full bg-{{ $color }}-500"></span>
                {{ $label }}
            </span>
        @endforeach
    </div>

    <style>
        .fc-event-resizer {
            width: 10px !important;
            opacity: 1 !important;
        }
        .fc-event-resizer-start { left: -2px !important; cursor: w-resize; }
        .fc-event-resizer-end   { right: -2px !important; cursor: e-resize; }
        .fc-daygrid-event:hover .fc-event-resizer {
            background: rgba(255,255,255,0.5);
            border-radius: 3px;
        }
        .fc-daygrid-body, .fc-scrollgrid-section, .fc-daygrid-day-frame {
            overflow: visible !important;
        }
    </style>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" wire:ignore>
        <div id="project-calendar"></div>
    </div>
</div>
