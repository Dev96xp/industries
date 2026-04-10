<?php

use App\Mail\QuoteSent;
use App\Models\CompanySetting;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $activeTab = 'active';

    public function sendQuote(Quote $quote): void
    {
        if (! $quote->client_email) {
            session()->flash('error', 'This quote has no client email.');

            return;
        }

        $quote->load('items');
        $company = CompanySetting::current();

        Mail::to($quote->client_email)
            ->send(new QuoteSent($quote, $company));

        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent']);
        }

        session()->flash('success', "Quote {$quote->number} sent to {$quote->client_email}.");
    }

    public function archiveQuote(Quote $quote): void
    {
        $quote->delete();
        session()->flash('success', "Quote {$quote->number} archived.");
    }

    public function restoreQuote(int $id): void
    {
        $quote = Quote::withTrashed()->findOrFail($id);
        $quote->restore();
        session()->flash('success', "Quote {$quote->number} restored.");
    }

    public function with(): array
    {
        return [
            'quotes' => $this->activeTab === 'archived'
                ? Quote::onlyTrashed()->with('project')->orderByDesc('deleted_at')->get()
                : Quote::with('project')->orderByDesc('created_at')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Quotes</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage project quotes and estimates.</flux:text>
        </div>
        <flux:button href="{{ route('admin.quotes.create') }}" variant="primary" icon="plus" wire:navigate>
            New Quote
        </flux:button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if(session('error'))
        <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl border border-zinc-200 bg-zinc-100 p-1 w-fit dark:border-zinc-700 dark:bg-zinc-800">
        <button wire:click="$set('activeTab', 'active')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeTab === 'active' ? 'bg-white shadow text-zinc-900 dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            Active
        </button>
        <button wire:click="$set('activeTab', 'archived')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeTab === 'archived' ? 'bg-white shadow text-zinc-900 dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            Archived
        </button>
    </div>

    {{-- Table --}}
    @if($quotes->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.document-text class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">
                {{ $activeTab === 'archived' ? 'No archived quotes.' : 'No quotes yet. Create your first one.' }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Number</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Client</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Project</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Total</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($quotes as $quote)
                        <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-4 font-mono font-semibold text-zinc-700 dark:text-zinc-300">{{ $quote->number }}</td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $quote->client_name }}</p>
                                @if($quote->client_email)
                                    <p class="text-xs text-zinc-400">{{ $quote->client_email }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-500">{{ $quote->quote_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <flux:badge color="{{ match($quote->status) { 'draft' => 'zinc', 'sent' => 'blue', 'accepted' => 'green', 'rejected' => 'red' } }}">
                                    {{ ucfirst($quote->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-400">
                                {{ $quote->project?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-zinc-800 dark:text-zinc-100">
                                ${{ number_format($quote->total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($activeTab === 'archived')
                                        <flux:button wire:click="restoreQuote({{ $quote->id }})"
                                            variant="ghost" size="sm" icon="arrow-uturn-left">
                                            Restore
                                        </flux:button>
                                    @else
                                        <a href="{{ route('admin.quotes.report', $quote) }}" target="_blank"
                                            class="p-1.5 text-zinc-400 transition hover:text-zinc-600 dark:hover:text-zinc-300" title="Print">
                                            <flux:icon.printer class="size-4" />
                                        </a>
                                        <button
                                            wire:click="sendQuote({{ $quote->id }})"
                                            wire:confirm="Send quote {{ $quote->number }} to {{ $quote->client_email ?: 'client' }}?"
                                            class="p-1.5 text-zinc-400 transition hover:text-blue-500 dark:hover:text-blue-400"
                                            title="Send by email"
                                        >
                                            <flux:icon.envelope class="size-4" />
                                        </button>
                                        <flux:button href="{{ route('admin.quotes.edit', $quote) }}" variant="ghost" size="sm" icon="pencil" wire:navigate>
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="archiveQuote({{ $quote->id }})"
                                            wire:confirm="Archive quote {{ $quote->number }}?"
                                            variant="ghost" size="sm" icon="archive-box"
                                            class="text-zinc-400 hover:text-zinc-600">
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
