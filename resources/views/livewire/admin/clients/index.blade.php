<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component {
    // New client form
    public string $newName  = '';
    public string $newEmail = '';
    public string $newPhone = '';

    public function createClient(): void
    {
        $this->validate([
            'newName'  => ['required', 'string', 'max:255'],
            'newEmail' => ['required', 'email', 'unique:users,email'],
            'newPhone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'name'     => $this->newName,
            'email'    => $this->newEmail,
            'phone'    => $this->newPhone ?: null,
            'password' => bcrypt(Str::random(32)),
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => 'client']));

        Password::sendResetLink(['email' => $this->newEmail]);

        $this->reset('newName', 'newEmail', 'newPhone');
        $this->modal('create-client')->close();
        session()->flash('success', "Client \"{$user->name}\" created and notified by email.");
    }

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
        <div class="flex items-center gap-3">
            <flux:badge color="amber" size="lg">{{ $clients->count() }} Clients</flux:badge>
            <flux:modal.trigger name="create-client">
                <flux:button variant="primary" icon="user-plus" size="sm">New Client</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Workflow diagram --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">How it works</p>
        <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-start sm:justify-center sm:gap-0">

            {{-- Step 1 --}}
            <div class="flex flex-col items-center text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.user-plus class="size-5 text-amber-600" />
                </div>
                <p class="mt-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">1. Register Client</p>
                <div class="mt-1.5 flex flex-col gap-1">
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                        <flux:icon.globe-alt class="size-3" /> 1a. Self-registers on website
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.user class="size-3" /> 1b. Admin registers client
                    </span>
                </div>
            </div>

            {{-- Arrow --}}
            <div class="flex items-center justify-center sm:mt-5 sm:px-3">
                <svg class="size-5 rotate-90 text-zinc-300 sm:rotate-0 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </div>

            {{-- Step 2 --}}
            <div class="flex flex-col items-center text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.document-text class="size-5 text-blue-600" />
                </div>
                <p class="mt-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">2. Create Quote</p>
                <p class="mt-0.5 max-w-[110px] text-xs text-zinc-400">Use "New Quote" button on the client row</p>
            </div>

            {{-- Arrow --}}
            <div class="flex items-center justify-center sm:mt-5 sm:px-3">
                <svg class="size-5 rotate-90 text-zinc-300 sm:rotate-0 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </div>

            {{-- Step 3 --}}
            <div class="flex flex-col items-center text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <flux:icon.check-badge class="size-5 text-green-600" />
                </div>
                <p class="mt-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">3. Quote Accepted</p>
                <p class="mt-0.5 max-w-[110px] text-xs text-zinc-400">Send quote and wait for client approval</p>
            </div>

            {{-- Arrow --}}
            <div class="flex items-center justify-center sm:mt-5 sm:px-3">
                <svg class="size-5 rotate-90 text-zinc-300 sm:rotate-0 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </div>

            {{-- Step 4 --}}
            <div class="flex flex-col items-center text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon.building-office-2 class="size-5 text-purple-600" />
                </div>
                <p class="mt-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">4. Create Project</p>
                <p class="mt-0.5 max-w-[110px] text-xs text-zinc-400">Start project and assign the client</p>
            </div>

        </div>
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
                                                    <td class="py-2 font-mono font-semibold text-amber-500">{{ $quote->number }}</td>
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

    {{-- Flash message --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="fixed bottom-6 right-6 z-50 rounded-xl bg-green-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Create Client Modal --}}
    <flux:modal name="create-client" class="w-full max-w-md" :dismissible="false">
        <div class="flex flex-col gap-4 p-1">
            <flux:heading size="lg">New Client</flux:heading>
            <flux:text class="text-zinc-500">The client will receive an email to set their password.</flux:text>

            <flux:input wire:model="newName" label="Full Name" placeholder="John Doe" />
            <flux:input wire:model="newEmail" label="Email" type="email" placeholder="john@example.com" />
            <flux:input wire:model="newPhone" label="Phone (optional)" placeholder="+1 555 000 0000" />

            <div class="flex gap-2 pt-1">
                <flux:button wire:click="createClient" variant="primary" icon="user-plus">
                    Create & Notify
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
