<?php

use App\Models\Contractor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search = '';

    public function deleteContractor(Contractor $contractor): void
    {
        $contractor->delete();
        session()->flash('success', "Contractor \"{$contractor->company_name}\" deleted.");
    }

    public function toggleActive(Contractor $contractor): void
    {
        $contractor->update(['is_active' => ! $contractor->is_active]);
    }

    public function with(): array
    {
        return [
            'contractors' => Contractor::query()
                ->when($this->search, fn ($q) => $q
                    ->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_name', 'like', "%{$this->search}%")
                    ->orWhere('specialty', 'like', "%{$this->search}%")
                )
                ->orderBy('company_name')
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Contractors</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage external companies you work with.</flux:text>
        </div>
        <flux:button href="{{ route('admin.contractors.create') }}" variant="primary" icon="plus" wire:navigate>
            New Contractor
        </flux:button>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Search --}}
    <flux:input wire:model.live="search" placeholder="Search by company, contact or specialty..." icon="magnifying-glass" />

    {{-- Table --}}
    @if($contractors->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-office-2 class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">
                {{ $search ? 'No contractors match your search.' : 'No contractors yet. Add your first one.' }}
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Company</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Contact</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 sm:table-cell">Specialty</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 md:table-cell">Phone</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 lg:table-cell">Email</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($contractors as $contractor)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $contractor->company_name }}
                                </td>
                                <td class="px-4 py-3 text-zinc-500">
                                    {{ $contractor->contact_name ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 sm:table-cell">
                                    {{ $contractor->specialty ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 md:table-cell">
                                    {{ $contractor->phone ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 lg:table-cell">
                                    {{ $contractor->email ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="toggleActive({{ $contractor->id }})" title="Toggle status">
                                        <flux:badge color="{{ $contractor->is_active ? 'green' : 'zinc' }}" size="sm">
                                            {{ $contractor->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button href="{{ route('admin.contractors.edit', $contractor) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                                        <flux:button wire:click="deleteContractor({{ $contractor->id }})"
                                            wire:confirm="Delete '{{ $contractor->company_name }}'? This cannot be undone."
                                            size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
