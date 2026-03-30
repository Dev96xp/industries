<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.client')] class extends Component {
    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->name  = auth()->user()->name;
        $this->email = auth()->user()->email;
    }
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-zinc-900">My Account</h1>
        <p class="mt-1 text-sm text-zinc-500">Your registered information with Nucleus Industries.</p>
    </div>

    {{-- Profile card --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex size-16 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-600">
                {{ auth()->user()->initials() }}
            </div>
            <div>
                <p class="text-lg font-semibold text-zinc-900">{{ $name }}</p>
                <p class="text-sm text-zinc-400">{{ $email }}</p>
            </div>
        </div>

        <div class="border-t border-zinc-100 pt-5 space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Full Name</p>
                <p class="mt-1 text-sm text-zinc-800">{{ $name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Email Address</p>
                <p class="mt-1 text-sm text-zinc-800">{{ $email }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Member Since</p>
                <p class="mt-1 text-sm text-zinc-800">{{ auth()->user()->created_at->format('F d, Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Back to site --}}
    <div class="mt-6 text-center">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 transition hover:text-zinc-600">
            ← Back to website
        </a>
    </div>

</div>
