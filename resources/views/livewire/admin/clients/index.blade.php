<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'clients' => User::with('roles')
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
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Client</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden sm:table-cell">Phone</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden md:table-cell">Projects</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden md:table-cell">Quotes</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden lg:table-cell">Registered</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($clients as $client)
                        <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
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
                            <td class="px-6 py-4 text-zinc-500 hidden sm:table-cell">
                                {{ $client->phone ?: '—' }}
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <flux:badge color="blue" size="sm">{{ $client->projects_count }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <flux:badge color="green" size="sm">{{ $client->quotes_count }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-400 hidden lg:table-cell">
                                {{ $client->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
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
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-zinc-400">
                                No clients have registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
