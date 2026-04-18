<?php

use App\Models\Location;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function deleteLocation(Location $location): void
    {
        $location->delete();
        session()->flash('success', "Location \"{$location->name}\" deleted.");
    }

    public function toggleActive(Location $location): void
    {
        $location->update(['is_active' => ! $location->is_active]);
    }

    public function with(): array
    {
        return [
            'locations' => Location::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Locations</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage your office locations.</flux:text>
        </div>
        <flux:button href="{{ route('admin.locations.create') }}" variant="primary" icon="plus" wire:navigate>
            New Location
        </flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if($locations->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.map-pin class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">No locations yet.</p>
            <flux:button href="{{ route('admin.locations.create') }}" variant="primary" size="sm" class="mt-4" wire:navigate>Add First Location</flux:button>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-100 dark:border-zinc-800">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Address</th>
                        <th class="px-4 py-3 hidden md:table-cell">Phone</th>
                        <th class="px-4 py-3 hidden md:table-cell">Email</th>
                        <th class="px-4 py-3">Clients</th>
                        <th class="px-4 py-3">Projects</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                    @foreach($locations as $location)
                        <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                            <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $location->name }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500 hidden sm:table-cell">
                                {{ collect([$location->address, $location->city, $location->state])->filter()->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500 hidden md:table-cell">
                                {{ $location->phone ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500 hidden md:table-cell">
                                {{ $location->email ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $location->clients()->count() }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $location->projects()->count() }}
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="toggleActive({{ $location->id }})"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition cursor-pointer
                                        {{ $location->is_active
                                            ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition">
                                    <a href="{{ route('admin.locations.edit', $location) }}" wire:navigate
                                        class="text-zinc-400 hover:text-blue-500 transition">
                                        <flux:icon.pencil class="size-4" />
                                    </a>
                                    <button type="button"
                                        wire:click="deleteLocation({{ $location->id }})"
                                        wire:confirm="Delete location &quot;{{ $location->name }}&quot;? Clients and projects assigned to it will be unassigned."
                                        class="text-zinc-400 hover:text-red-500 transition">
                                        <flux:icon.trash class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
