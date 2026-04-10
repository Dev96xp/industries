<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'clients' => User::with(['roles', 'quotes' => fn ($q) => $q->orderByDesc('created_at')])
                ->whereHas('roles', fn ($q) => $q->where('name', 'client'))
                ->withCount(['projects', 'quotes'])
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-0 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Clients</flux:heading>
            <flux:text class="mt-1 text-zinc-500">All registered clients.</flux:text>
        </div>
        <flux:badge color="amber" size="lg">{{ $clients->count() }} Clients</flux:badge>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <th class="w-8 px-4 py-4"></th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Client</th>
                        <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 sm:table-cell">Phone</th>
                        <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 md:table-cell">Projects</th>
                        <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 md:table-cell">Quotes</th>
                        <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 lg:table-cell">Registered</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                    </tr>
                </thead>

                @forelse($clients as $client)
                    <tbody x-data="{ open: false }" class="divide-y divide-zinc-100 dark:divide-zinc-800">

                        {{-- Client row --}}
                        <tr x-on:click="open = !open"
                            class="cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                            :class="open ? 'bg-zinc-50 dark:bg-zinc-800/30' : ''"
                        >
                            {{-- Chevron --}}
                            <td class="px-4 py-4 text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-600 dark:bg-amber-900/30">
                                        {{ $client->initials() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $client->name }}</p>
                                        <p class="text-xs text-zinc-400">{{ $client->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-6 py-4 text-zinc-500 sm:table-cell">
                                {{ $client->phone ?: '—' }}
                            </td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <flux:badge color="blue" size="sm">{{ $client->projects_count }}</flux:badge>
                            </td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <flux:badge color="green" size="sm">{{ $client->quotes_count }}</flux:badge>
                            </td>
                            <td class="hidden px-6 py-4 text-xs text-zinc-400 lg:table-cell">
                                {{ $client->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right" x-on:click.stop>
                                <div class="flex items-center justify-end gap-2">
                                    @can('manage quotes')
                                        <flux:button
                                            href="{{ route('admin.quotes.create', ['client' => $client->id]) }}"
                                            variant="primary"
                                            size="sm"
                                            icon="document-text"
                                            wire:navigate
                                        >
                                            New Quote
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>

                        {{-- Accordion: quotes --}}
                        <tr x-show="open"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            style="display: none;"
                        >
                            <td colspan="7" class="bg-zinc-50 px-6 py-4 dark:bg-zinc-800/30">
                                @if($client->quotes->isEmpty())
                                    <p class="text-sm text-zinc-400">No quotes for this client yet.</p>
                                @else
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                                <th class="pb-2 text-left">Quote #</th>
                                                <th class="pb-2 text-left">Date</th>
                                                <th class="pb-2 text-left">Status</th>
                                                <th class="pb-2 text-right">Total</th>
                                                <th class="pb-2 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                            @foreach($client->quotes as $quote)
                                                <tr class="hover:bg-zinc-100/50 dark:hover:bg-zinc-700/30">
                                                    <td class="py-2 font-mono font-semibold text-zinc-700 dark:text-zinc-300">{{ $quote->number }}</td>
                                                    <td class="py-2 text-zinc-500">{{ $quote->quote_date->format('M d, Y') }}</td>
                                                    <td class="py-2">
                                                        <flux:badge size="sm" color="{{ match($quote->status) { 'draft' => 'zinc', 'sent' => 'blue', 'accepted' => 'green', 'rejected' => 'red' } }}">
                                                            {{ ucfirst($quote->status) }}
                                                        </flux:badge>
                                                    </td>
                                                    <td class="py-2 text-right font-semibold text-zinc-800 dark:text-zinc-100">
                                                        ${{ number_format($quote->total, 2) }}
                                                    </td>
                                                    <td class="py-2 text-right">
                                                        @can('manage quotes')
                                                            <flux:button href="{{ route('admin.quotes.edit', $quote) }}" variant="ghost" size="sm" icon="pencil" wire:navigate>
                                                                Edit
                                                            </flux:button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </td>
                        </tr>

                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-zinc-400">
                                No clients have registered yet.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

</div>
