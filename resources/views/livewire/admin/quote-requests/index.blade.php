<?php

use App\Mail\QuoteRequestReply;
use App\Models\CompanySetting;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $tab = 'active';
    public ?QuoteRequest $viewing = null;
    public string $replyMessage = '';
    public bool $replySent = false;

    public function openRequest(QuoteRequest $quoteRequest): void
    {
        $this->viewing = $quoteRequest;
        $this->replyMessage = '';
        $this->replySent = false;

        if ($quoteRequest->status === 'new') {
            $quoteRequest->update(['status' => 'read']);
            $this->viewing = $quoteRequest->fresh();
        }
    }

    public function closeView(): void
    {
        $this->viewing = null;
        $this->replyMessage = '';
        $this->replySent = false;
    }

    public function sendReply(): void
    {
        $this->validate(['replyMessage' => ['required', 'string', 'min:10']]);

        $company = CompanySetting::current();

        Mail::to($this->viewing->email)
            ->send(new QuoteRequestReply($this->viewing, $this->replyMessage, $company));

        if ($this->viewing->status === 'read') {
            $this->viewing->update(['status' => 'contacted']);
            $this->viewing = $this->viewing->fresh();
        }

        $this->replyMessage = '';
        $this->replySent = true;
    }

    public function cycleStatus(QuoteRequest $quoteRequest): void
    {
        $next = match ($quoteRequest->status) {
            'new'       => 'read',
            'read'      => 'contacted',
            'contacted' => 'closed',
            default     => 'new',
        };

        $quoteRequest->update(['status' => $next]);

        if ($this->viewing?->id === $quoteRequest->id) {
            $this->viewing = $quoteRequest->fresh();
        }
    }

    public function archive(QuoteRequest $quoteRequest): void
    {
        if ($this->viewing?->id === $quoteRequest->id) {
            $this->viewing = null;
        }

        $quoteRequest->delete();
    }

    public function restore(int $id): void
    {
        QuoteRequest::withTrashed()->findOrFail($id)->restore();
    }

    public function with(): array
    {
        return [
            'activeRequests'   => QuoteRequest::query()->whereIn('status', ['new', 'read', 'contacted'])->latest()->get(),
            'closedRequests'   => QuoteRequest::query()->where('status', 'closed')->latest()->get(),
            'archivedRequests' => QuoteRequest::onlyTrashed()->latest()->get(),
            'newCount'         => QuoteRequest::where('status', 'new')->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Quote Requests</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Messages received from the public website.</flux:text>
        </div>
        @if($newCount > 0)
            <flux:badge color="red" size="lg">{{ $newCount }} New</flux:badge>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800/50 sm:w-fit">
        <button
            wire:click="$set('tab', 'active')"
            class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'active' ? 'bg-white shadow-sm text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Active
            @if($activeRequests->count() > 0)
                <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ $activeRequests->count() }}</span>
            @endif
        </button>
        <button
            wire:click="$set('tab', 'closed')"
            class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'closed' ? 'bg-white shadow-sm text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Closed
            @if($closedRequests->count() > 0)
                <span class="rounded-full bg-zinc-200 px-1.5 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-600 dark:text-zinc-300">{{ $closedRequests->count() }}</span>
            @endif
        </button>
        <button
            wire:click="$set('tab', 'archived')"
            class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'archived' ? 'bg-white shadow-sm text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Archived
            @if($archivedRequests->count() > 0)
                <span class="rounded-full bg-zinc-200 px-1.5 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-600 dark:text-zinc-300">{{ $archivedRequests->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Table --}}
    @php $list = match($tab) { 'closed' => $closedRequests, 'archived' => $archivedRequests, default => $activeRequests }; @endphp

    @if($list->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 py-16 dark:border-zinc-700">
            <div class="text-4xl">{{ $tab === 'active' ? '📬' : '✅' }}</div>
            <p class="mt-3 font-medium text-zinc-500">
                {{ $tab === 'active' ? 'No active requests.' : 'No closed requests yet.' }}
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Contact</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 sm:table-cell">Project Type</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 md:table-cell">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 lg:table-cell">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($list as $request)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $request->status === 'new' ? 'font-semibold' : '' }}">
                                <td class="px-4 py-3">
                                    <p class="text-zinc-900 dark:text-white">{{ $request->name }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-zinc-700 dark:text-zinc-300">{{ $request->email }}</p>
                                    @if($request->phone)
                                        <p class="text-xs text-zinc-400">{{ $request->phone }}</p>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    @if($request->project_type)
                                        <span class="inline-block rounded-full bg-zinc-100 px-2 py-0.5 text-xs capitalize text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            {{ $request->project_type }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden max-w-xs px-4 py-3 md:table-cell">
                                    <p class="truncate text-zinc-500">{{ Str::limit($request->message, 60) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="cycleStatus({{ $request->id }})" title="Click to change status">
                                        @php
                                            $colors = ['new' => 'green', 'read' => 'blue', 'contacted' => 'yellow', 'closed' => 'red'];
                                            $color = $colors[$request->status] ?? 'zinc';
                                        @endphp
                                        <flux:badge :color="$color" size="sm">{{ ucfirst($request->status) }}</flux:badge>
                                    </button>
                                </td>
                                <td class="hidden px-4 py-3 text-xs text-zinc-400 lg:table-cell">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($tab !== 'archived')
                                            <flux:button size="sm" variant="ghost" wire:click="openRequest({{ $request->id }})">View</flux:button>
                                            <flux:button size="sm" variant="ghost" wire:click="archive({{ $request->id }})" wire:confirm="Archive this request?">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                </svg>
                                            </flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" wire:click="restore({{ $request->id }})">Restore</flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- View modal --}}
    @if($viewing)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.closeView()">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeView"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>
                <div class="p-6">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $viewing->name }}</h2>
                            <p class="text-sm text-zinc-500">{{ $viewing->created_at->format('M d, Y \a\t g:i A') }}</p>
                        </div>
                        <button wire:click="closeView" class="flex size-8 items-center justify-center rounded-full text-zinc-400 transition hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-3 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Email</p>
                            <a href="mailto:{{ $viewing->email }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">{{ $viewing->email }}</a>
                        </div>
                        @if($viewing->phone)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Phone</p>
                                <a href="tel:{{ $viewing->phone }}" class="text-sm text-zinc-700 dark:text-zinc-300">{{ $viewing->phone }}</a>
                            </div>
                        @endif
                        @if($viewing->project_type)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Project Type</p>
                                <p class="text-sm capitalize text-zinc-700 dark:text-zinc-300">{{ $viewing->project_type }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Status</p>
                            <button wire:click="cycleStatus({{ $viewing->id }})" title="Click to change status">
                                @php $colors = ['new' => 'green', 'read' => 'blue', 'contacted' => 'yellow', 'closed' => 'red']; @endphp
                                <flux:badge :color="$colors[$viewing->status] ?? 'zinc'" size="sm">{{ ucfirst($viewing->status) }}</flux:badge>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">Message</p>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $viewing->message }}</p>
                    </div>

                    {{-- Reply form --}}
                    <div class="mt-5 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                        @if($replySent)
                            <div class="flex items-center gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Reply sent to {{ $viewing->email }}
                            </div>
                        @else
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">Reply to Client</p>
                            <textarea
                                wire:model="replyMessage"
                                rows="4"
                                placeholder="Write your response here..."
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            ></textarea>
                            @error('replyMessage') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    <div class="mt-4 flex justify-between">
                        <flux:button variant="ghost" wire:click="archive({{ $viewing->id }})" wire:confirm="Archive this request?">Archive</flux:button>
                        @if(!$replySent)
                            <flux:button variant="filled" wire:click="sendReply" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="sendReply">Send Reply</span>
                                <span wire:loading wire:target="sendReply">Sending...</span>
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
