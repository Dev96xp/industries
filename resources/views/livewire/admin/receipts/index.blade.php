<?php

use App\Models\Project;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search    = '';
    public int    $projectId = 0;

    public function with(): array
    {
        $query = ProjectExpense::with('project.client')
            ->whereNotNull('receipt_path')
            ->orderByDesc('expense_date');

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$this->search}%"));
            });
        }

        return [
            'expenses' => $query->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex flex-col gap-6 p-1 sm:p-6"
     x-data="{ lightbox: null }"
     @keydown.escape.window="lightbox = null">

    {{-- Lightbox --}}
    <div x-show="lightbox"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="lightbox = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
         style="display:none">
        <button @click.stop="lightbox = null"
                class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 transition">
            <flux:icon.x-mark class="size-6" />
        </button>
        <img :src="lightbox"
             @click.stop
             class="max-h-[90vh] max-w-full rounded-xl object-contain shadow-2xl">
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Receipts</flux:heading>
            <flux:text class="mt-1 text-zinc-500">All expense receipts across projects.</flux:text>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="w-64">
            <flux:input wire:model.live="search" placeholder="Search description or project..." icon="magnifying-glass" />
        </div>
        <div class="w-52">
            <flux:select wire:model.live="projectId">
                <flux:select.option value="0">All projects</flux:select.option>
                @foreach($projects as $project)
                    <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Gallery --}}
    @if($expenses->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-700 p-16 text-center">
            <flux:icon.receipt-percent class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <p class="text-sm text-zinc-400">No receipts found.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($expenses as $expense)
                <div class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                    {{-- Receipt image --}}
                    <button @click="lightbox = '{{ Storage::url($expense->receipt_path) }}'"
                            class="block w-full overflow-hidden">
                        <img src="{{ Storage::url($expense->receipt_path) }}"
                             class="h-48 w-full object-cover transition hover:scale-105"
                             alt="Receipt for {{ $expense->description }}">
                    </button>

                    {{-- Info --}}
                    <div class="flex flex-1 flex-col gap-1 p-4">
                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $expense->description }}
                        </p>
                        <a href="{{ route('admin.projects.edit', $expense->project) }}" wire:navigate
                           class="truncate text-xs text-blue-500 hover:underline">
                            {{ $expense->project->name }}
                        </a>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs text-zinc-400">{{ $expense->expense_date->format('M d, Y') }}</span>
                            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">${{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-xs text-zinc-400">
                                {{ number_format(Storage::disk('public')->size($expense->receipt_path) / 1024, 0) }} KB
                            </span>
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                            <flux:badge size="sm" color="{{ match($expense->category) {
                                'materials' => 'blue', 'labor' => 'green', 'equipment' => 'amber',
                                'subcontractors' => 'purple', 'permits' => 'zinc', default => 'zinc'
                            } }}">{{ ucfirst($expense->category) }}</flux:badge>
                            <span class="text-xs text-zinc-400">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

