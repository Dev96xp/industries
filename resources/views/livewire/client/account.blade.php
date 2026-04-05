<?php

use App\Models\Project;
use App\Models\Quote;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.client')] class extends Component {
    public string $tab = 'projects';

    public function with(): array
    {
        return [
            'projects' => Project::where('client_user_id', auth()->id())
                ->orderByDesc('created_at')
                ->get(),
            'quotes' => Quote::where('user_id', auth()->id())
                ->with('items')
                ->orderByDesc('created_at')
                ->get(),
        ];
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white sm:text-3xl">My Account</h1>
        <p class="mt-1 text-sm text-zinc-400">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex gap-1 rounded-xl border border-white/10 bg-white/5 p-1 sm:w-fit">
        <button
            wire:click="$set('tab', 'projects')"
            class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition {{ $tab === 'projects' ? 'bg-blue-600 text-white' : 'text-zinc-400 hover:text-white' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            My Projects
            @if($projects->count() > 0)
                <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-xs">{{ $projects->count() }}</span>
            @endif
        </button>
        <button
            wire:click="$set('tab', 'quotes')"
            class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition {{ $tab === 'quotes' ? 'bg-blue-600 text-white' : 'text-zinc-400 hover:text-white' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            My Quotes
            @if($quotes->count() > 0)
                <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-xs">{{ $quotes->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Projects Tab --}}
    @if($tab === 'projects')
        @if($projects->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-white/10 bg-white/5 py-16 text-center">
                <div class="text-4xl">🏗️</div>
                <p class="mt-3 text-zinc-400">No projects assigned yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($projects as $project)
                    @php
                        $statusColors = [
                            'planning'    => 'bg-zinc-700 text-zinc-300',
                            'in_progress' => 'bg-blue-600/30 text-blue-300',
                            'on_hold'     => 'bg-yellow-600/30 text-yellow-300',
                            'completed'   => 'bg-green-600/30 text-green-300',
                            'cancelled'   => 'bg-red-600/30 text-red-300',
                        ];
                        $statusColor = $statusColors[$project->status] ?? 'bg-zinc-700 text-zinc-300';
                        $statusLabel = ucfirst(str_replace('_', ' ', $project->status));
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition hover:bg-white/8">
                        <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>
                        <div class="p-5">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-mono text-zinc-500">{{ $project->number }}</p>
                                    <h3 class="mt-0.5 font-semibold text-white">{{ $project->name }}</h3>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            @if($project->address)
                                <p class="mb-3 flex items-center gap-1.5 text-xs text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    {{ $project->address }}
                                </p>
                            @endif

                            <div class="flex flex-wrap gap-4 text-xs text-zinc-500">
                                @if($project->start_date)
                                    <div>
                                        <p class="font-semibold uppercase tracking-wider text-zinc-600">Start</p>
                                        <p class="text-zinc-300">{{ $project->start_date->format('M d, Y') }}</p>
                                    </div>
                                @endif
                                @if($project->estimated_completion_date)
                                    <div>
                                        <p class="font-semibold uppercase tracking-wider text-zinc-600">Est. Completion</p>
                                        <p class="text-zinc-300">{{ $project->estimated_completion_date->format('M d, Y') }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($project->description)
                                <p class="mt-3 text-xs leading-relaxed text-zinc-500">{{ Str::limit($project->description, 100) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Quotes Tab --}}
    @if($tab === 'quotes')
        @if($quotes->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-white/10 bg-white/5 py-16 text-center">
                <div class="text-4xl">📄</div>
                <p class="mt-3 text-zinc-400">No quotes yet.</p>
            </div>
        @else
            <div class="flex flex-col gap-4">
                @foreach($quotes as $quote)
                    @php
                        $statusColors = [
                            'draft'    => 'bg-zinc-700 text-zinc-300',
                            'sent'     => 'bg-blue-600/30 text-blue-300',
                            'accepted' => 'bg-green-600/30 text-green-300',
                            'rejected' => 'bg-red-600/30 text-red-300',
                        ];
                        $statusColor = $statusColors[$quote->status] ?? 'bg-zinc-700 text-zinc-300';
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        <div class="h-1 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>
                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-mono text-xs text-zinc-500">{{ $quote->number }}</p>
                                    <p class="mt-0.5 text-lg font-bold text-white">${{ number_format($quote->total, 2) }}</p>
                                    <p class="text-xs text-zinc-400">{{ $quote->quote_date->format('M d, Y') }}
                                        @if($quote->expiration_date)
                                            &nbsp;·&nbsp; Expires {{ $quote->expiration_date->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                                    {{ ucfirst($quote->status) }}
                                </span>
                            </div>

                            @if($quote->notes)
                                <p class="mt-3 text-xs leading-relaxed text-zinc-500">{{ Str::limit($quote->notes, 120) }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('client.quotes.view', $quote) }}" target="_blank"
                                    class="flex items-center gap-1.5 rounded-lg border border-white/10 px-4 py-2 text-xs font-medium text-zinc-300 transition hover:bg-white/5 hover:text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.58-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    View Quote
                                </a>

                                @if($quote->status === 'sent')
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('quotes.approve', ['quote' => $quote->id]) }}"
                                        class="flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Approve Quote
                                    </a>
                                @endif

                                @if($quote->status === 'accepted')
                                    <span class="flex items-center gap-1.5 rounded-lg bg-green-600/20 px-4 py-2 text-xs font-semibold text-green-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Approved
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

</div>
