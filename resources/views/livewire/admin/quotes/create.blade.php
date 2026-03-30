<?php

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int $user_id         = null;
    public string $quote_date    = '';
    public string $expiration_date = '';
    public string $tax_percentage  = '0';
    public string $discount        = '0';
    public string $notes           = '';
    public string $terms           = '';
    public string $status          = 'draft';

    /** @var array<int, array{description: string, quantity: string, unit_price: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->quote_date = now()->format('Y-m-d');
        $this->user_id    = request()->integer('client') ?: null;
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => '1', 'unit_price' => ''];
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function with(): array
    {
        $client = $this->user_id ? User::find($this->user_id) : null;

        $subtotal  = collect($this->items)->sum(
            fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $taxAmount = round($subtotal * ((float) $this->tax_percentage / 100), 2);
        $total     = round($subtotal + $taxAmount - (float) $this->discount, 2);

        $clients = User::role('client')->orderBy('name')->get(['id', 'name', 'email', 'phone']);

        return compact('client', 'clients', 'subtotal', 'taxAmount', 'total');
    }

    public function save(): void
    {
        $this->validate([
            'user_id'         => ['required', 'exists:users,id'],
            'quote_date'      => ['required', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'tax_percentage'  => ['numeric', 'min:0', 'max:100'],
            'discount'        => ['numeric', 'min:0'],
            'status'          => ['required', 'in:draft,sent,accepted,rejected'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $client = User::findOrFail($this->user_id);

        $quote = Quote::create([
            'user_id'         => $this->user_id,
            'number'          => Quote::generateNumber(),
            'client_name'     => $client->name,
            'client_email'    => $client->email,
            'client_phone'    => $client->phone ?? null,
            'quote_date'      => $this->quote_date,
            'expiration_date' => $this->expiration_date ?: null,
            'tax_percentage'  => $this->tax_percentage,
            'discount'        => $this->discount,
            'notes'           => $this->notes ?: null,
            'terms'           => $this->terms ?: null,
            'status'          => $this->status,
        ]);

        foreach ($this->items as $i => $item) {
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'sort_order'  => $i,
            ]);
        }

        session()->flash('success', "Quote {$quote->number} created.");

        $this->redirect(route('admin.quotes'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.quotes') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <div>
            <flux:heading size="xl">New Quote</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Create a quote for a registered client.</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6 max-w-4xl">

        {{-- Client selector --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Client</h3>

            @if($client)
                {{-- Pre-selected client card --}}
                <div class="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-900 dark:bg-blue-950/30">
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $client->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $client->email }}</p>
                        @if($client->phone)
                            <p class="text-sm text-zinc-500">{{ $client->phone }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="$set('user_id', null)"
                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        Change
                    </button>
                </div>
            @else
                <flux:select wire:model.live="user_id" label="Select Client" placeholder="Choose a registered client...">
                    @foreach($clients as $c)
                        <flux:select.option value="{{ $c->id }}">{{ $c->name }} — {{ $c->email }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            @endif
        </div>

        {{-- Quote details --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Quote Details</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="quote_date" label="Quote Date" type="date" required />
                <flux:input wire:model="expiration_date" label="Expiration Date" type="date" />
                <flux:select wire:model="status" label="Status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="sent">Sent</flux:select.option>
                    <flux:select.option value="accepted">Accepted</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Line items --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Line Items</h3>

            <div class="mb-3 hidden grid-cols-12 gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-400 sm:grid">
                <div class="col-span-6">Description</div>
                <div class="col-span-2 text-right">Qty</div>
                <div class="col-span-2 text-right">Unit Price</div>
                <div class="col-span-2 text-right">Total</div>
            </div>

            <div class="flex flex-col gap-2">
                @foreach($items as $i => $item)
                    <div class="grid grid-cols-12 items-center gap-2">
                        <div class="col-span-12 sm:col-span-6">
                            <flux:input wire:model="items.{{ $i }}.description" placeholder="Description of work or material" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0" placeholder="1" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <flux:input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" min="0" placeholder="0.00" />
                        </div>
                        <div class="col-span-3 sm:col-span-1 text-right text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            ${{ number_format((float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0), 2) }}
                        </div>
                        <div class="col-span-1 text-right">
                            @if(count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $i }})" class="text-zinc-300 hover:text-red-400 transition">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="addItem"
                class="mt-3 flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 transition">
                <flux:icon.plus class="size-4" /> Add Line Item
            </button>

            {{-- Totals --}}
            <div class="mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-4 flex flex-col items-end gap-2">
                <div class="grid grid-cols-2 gap-x-8 text-sm w-64">
                    <span class="text-zinc-500">Subtotal</span>
                    <span class="text-right font-medium">${{ number_format($subtotal, 2) }}</span>

                    <div class="contents">
                        <span class="text-zinc-500">Tax</span>
                        <div class="flex items-center justify-end gap-2">
                            <input wire:model.live="tax_percentage" type="number" min="0" max="100" step="0.5"
                                class="w-14 rounded border border-zinc-200 px-2 py-0.5 text-right text-xs dark:border-zinc-700 dark:bg-zinc-800">
                            <span class="text-xs text-zinc-400">%</span>
                            <span class="font-medium">${{ number_format($taxAmount, 2) }}</span>
                        </div>
                    </div>

                    <div class="contents">
                        <span class="text-zinc-500">Discount</span>
                        <div class="flex items-center justify-end gap-1">
                            <span class="text-xs text-zinc-400">$</span>
                            <input wire:model.live="discount" type="number" min="0" step="0.01"
                                class="w-20 rounded border border-zinc-200 px-2 py-0.5 text-right text-xs dark:border-zinc-700 dark:bg-zinc-800">
                        </div>
                    </div>

                    <span class="border-t border-zinc-200 pt-2 font-bold text-zinc-900 dark:text-zinc-100 dark:border-zinc-700">Total</span>
                    <span class="border-t border-zinc-200 pt-2 text-right text-lg font-bold text-zinc-900 dark:text-zinc-100 dark:border-zinc-700">${{ number_format($total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Notes & Terms --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Notes & Terms</h3>
            <div class="flex flex-col gap-4">
                <flux:textarea wire:model="notes" label="Notes" rows="3" placeholder="Additional notes for the client..." />
                <flux:textarea wire:model="terms" label="Terms & Conditions" rows="3" placeholder="Payment terms, warranty, etc." />
            </div>
        </div>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Create Quote</flux:button>
            <flux:button href="{{ route('admin.quotes') }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>

    </form>

</div>
