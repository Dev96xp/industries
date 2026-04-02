<?php

use App\Models\QuoteRequest;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public bool $isOpen = false;
    public bool $submitted = false;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $project_type = '';
    public string $message = '';

    #[On('open-quote-form')]
    public function open(): void
    {
        $this->reset(['name', 'email', 'phone', 'project_type', 'message', 'submitted']);
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function submit(): void
    {
        $this->validate([
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:150'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'project_type' => ['nullable', 'string'],
            'message'      => ['required', 'string', 'max:2000'],
        ]);

        QuoteRequest::create([
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone ?: null,
            'project_type' => $this->project_type ?: null,
            'message'      => $this->message,
        ]);

        $this->submitted = true;
    }
}; ?>

<div>
    @if($isOpen)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            @keydown.escape.window="$wire.close()"
        >
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                {{-- Blue top bar --}}
                <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>

                {{-- Header --}}
                <div class="flex items-start justify-between p-6 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Request a Free Quote</h2>
                        <p class="mt-1 text-sm text-zinc-500">We'll get back to you within 24 hours.</p>
                    </div>
                    <button wire:click="close" class="flex size-8 items-center justify-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if($submitted)
                    {{-- Success state --}}
                    <div class="flex flex-col items-center gap-3 px-6 pb-8 pt-4 text-center">
                        <div class="flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Message Sent!</h3>
                        <p class="text-sm text-zinc-500">Thank you, <strong>{{ $name }}</strong>! We'll review your request and contact you soon.</p>
                        <button wire:click="close" class="mt-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Close
                        </button>
                    </div>
                @else
                    {{-- Form --}}
                    <form wire:submit="submit" class="px-6 pb-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Full Name <span class="text-red-500">*</span></label>
                                <input
                                    wire:model="name"
                                    type="text"
                                    placeholder="John Smith"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Email <span class="text-red-500">*</span></label>
                                <input
                                    wire:model="email"
                                    type="email"
                                    placeholder="john@email.com"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Phone</label>
                                <input
                                    wire:model="phone"
                                    type="tel"
                                    placeholder="(555) 000-0000"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                                @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Project Type</label>
                                <select
                                    wire:model="project_type"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                                    <option value="">Select type...</option>
                                    <option value="residential">Residential</option>
                                    <option value="commercial">Commercial</option>
                                    <option value="renovation">Renovation</option>
                                    <option value="industrial">Industrial</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Message <span class="text-red-500">*</span></label>
                            <textarea
                                wire:model="message"
                                rows="4"
                                placeholder="Tell us about your project..."
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            ></textarea>
                            @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" wire:click="close" class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submit">Send Request</span>
                                <span wire:loading wire:target="submit">Sending...</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
