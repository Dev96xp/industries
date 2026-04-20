<?php

use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int $user_id         = null;
    public ?int $project_id      = null;
    public string $quote_date    = '';
    public string $expiration_date = '';
    public string $tax_percentage  = '0';
    public string $discount        = '0';
    public string $notes           = '';
    public string $terms           = '';
    public string $status          = 'draft';

    /** @var array<int, array{product_id: int|null, description: string, quantity: string, unit_price: string, unit: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->quote_date = now()->format('Y-m-d');
        $this->user_id    = request()->integer('client') ?: null;
        $this->addItem();
    }

    public function updatedUserId(): void
    {
        $this->project_id = null;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '', 'discount' => '0', 'unit' => ''];
    }

    public function selectProduct(int $index, ?int $productId): void
    {
        if (! $productId) {
            $this->items[$index]['product_id'] = null;

            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $this->items[$index]['product_id'] = $product->id;
        $this->items[$index]['description'] = $product->name;
        $this->items[$index]['unit_price']  = $product->unit_price ? (string) $product->unit_price : '';
        $this->items[$index]['unit']        = $product->unit ?? '';
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function with(): array
    {
        $client = $this->user_id ? User::find($this->user_id) : null;

        $subtotal  = collect($this->items)->sum(function ($item) {
            $lineTotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);

            return $lineTotal * (1 - (float) ($item['discount'] ?? 0) / 100);
        });
        $taxAmount = round($subtotal * ((float) $this->tax_percentage / 100), 2);
        $total     = round($subtotal + $taxAmount - (float) $this->discount, 2);

        $clients  = User::role('client')->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $products = Product::with('category')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit_price', 'unit', 'category_id']);
        $projects = $this->user_id
            ? Project::where('client_user_id', $this->user_id)->orderBy('name')->get(['id', 'name', 'number'])
            : collect();

        return compact('client', 'clients', 'subtotal', 'taxAmount', 'total', 'products', 'projects');
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
            'project_id'      => ['nullable', 'exists:projects,id'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.discount'    => ['numeric', 'min:0', 'max:100'],
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
            'project_id'      => $this->project_id,
        ]);

        foreach ($this->items as $i => $item) {
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'product_id'  => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'discount'    => $item['discount'] ?? 0,
                'unit'        => $item['unit'] ?? null,
                'sort_order'  => $i,
            ]);
        }

        session()->flash('success', "Quote {$quote->number} created.");

        $this->redirect(route('admin.quotes'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.quotes') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <div>
            <flux:heading size="xl">New Quote</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Create a quote for a registered client.</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6 max-w-6xl">

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

                {{-- Project dropdown --}}
                <div class="mt-4">
                    @if($projects->isNotEmpty())
                        <flux:select wire:model="project_id" label="Link to Project (optional)">
                            <flux:select.option value="">— No project —</flux:select.option>
                            @foreach($projects as $proj)
                                <flux:select.option value="{{ $proj->id }}">{{ $proj->number }} — {{ $proj->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <p class="text-sm text-zinc-400 italic">No projects for this client.</p>
                    @endif
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

            <div class="overflow-x-auto">
            <div class="mb-3 grid min-w-[600px] grid-cols-12 gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">
                <div class="col-span-5">Description</div>
                <div class="col-span-2 text-right">Qty</div>
                <div class="col-span-2 text-right">Unit Price</div>
                <div class="col-span-1 text-right">Disc %</div>
                <div class="col-span-1 text-right">Total</div>
            </div>

            <div class="flex flex-col gap-2">
                @foreach($items as $i => $item)
                    @php $selectedProduct = $products->firstWhere('id', $item['product_id'] ?? null); @endphp
                    <div
                        class="grid min-w-[600px] grid-cols-12 items-start gap-2"
                        x-data="{
                            qty: {{ (float)($item['quantity'] ?? 0) }},
                            price: {{ (float)($item['unit_price'] ?? 0) }},
                            disc: {{ (float)($item['discount'] ?? 0) }},
                            open: false,
                            query: '{{ addslashes($selectedProduct?->name ?? '') }}',
                            activeIndex: -1,
                            dropdownStyle: {},
                            updatePosition() {
                                const rect = this.$refs.comboInput.getBoundingClientRect();
                                this.dropdownStyle = {
                                    position: 'fixed',
                                    top: (rect.bottom + window.scrollY) + 'px',
                                    left: (rect.left + window.scrollX) + 'px',
                                    width: rect.width + 'px',
                                    zIndex: 9999,
                                };
                            },
                            products: {{ Js::from($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'category' => $p->category?->name ?? 'Other', 'unit_price' => $p->unit_price, 'unit' => $p->unit])) }},
                            get filtered() {
                                if (!this.query) return this.products;
                                return this.products.filter(p => p.name.toLowerCase().includes(this.query.toLowerCase()) || p.category.toLowerCase().includes(this.query.toLowerCase()));
                            },
                            select(product) {
                                this.query = product.name;
                                this.open = false;
                                this.activeIndex = -1;
                                $wire.selectProduct({{ $i }}, product.id).then(() => {
                                    this.price = parseFloat($wire.items[{{ $i }}].unit_price) || 0;
                                    this.qty = parseFloat($wire.items[{{ $i }}].quantity) || 0;
                                });
                            },
                            onKeydown(e) {
                                if (!this.open && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                                    this.open = true; return;
                                }
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    this.activeIndex = Math.min(this.activeIndex + 1, this.filtered.length - 1);
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    this.activeIndex = Math.max(this.activeIndex - 1, 0);
                                } else if (e.key === 'Enter' && this.activeIndex >= 0) {
                                    e.preventDefault();
                                    this.select(this.filtered[this.activeIndex]);
                                } else if (e.key === 'Escape') {
                                    this.open = false; this.activeIndex = -1;
                                }
                            }
                        }"
                        x-on:click.outside="open = false"
                    >
                        <div class="col-span-5 flex flex-col gap-1">
                            <div class="relative">
                                <input
                                    type="text"
                                    x-ref="comboInput"
                                    x-model="query"
                                    x-on:focus="open = true; activeIndex = -1; updatePosition(); $el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                                    x-on:input="open = true; activeIndex = -1; updatePosition()"
                                    x-on:keydown="onKeydown($event)"
                                    placeholder="Search product..."
                                    autocomplete="off"
                                    class="w-full rounded border border-zinc-200 px-3 py-1.5 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                                <template x-teleport="body">
                                    <div
                                        x-show="open && filtered.length > 0"
                                        x-transition
                                        :style="dropdownStyle"
                                        class="mt-1 max-h-80 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        <template x-for="(product, idx) in filtered" :key="product.id">
                                            <button
                                                type="button"
                                                x-on:click="select(product)"
                                                x-on:mouseenter="activeIndex = idx"
                                                :class="activeIndex === idx ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                                                class="flex w-full items-center justify-between px-3 py-2 text-left text-sm"
                                            >
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100" x-text="product.name"></span>
                                                <span class="ml-2 text-xs text-zinc-400">
                                                    <span x-text="product.category"></span>
                                                    <template x-if="product.unit_price">
                                                        <span x-text="' — $' + parseFloat(product.unit_price).toFixed(2)"></span>
                                                    </template>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                    <div x-show="open && query && filtered.length === 0" :style="dropdownStyle" class="mt-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-400 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                        No products found.
                                    </div>
                                </template>
                            </div>
                            @error('items.' . $i . '.product_id')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0" placeholder="1" x-on:input="qty = parseFloat($event.target.value) || 0" />
                        </div>
                        <div class="col-span-2">
                            <flux:input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" min="0" placeholder="0.00" readonly class="bg-zinc-50 dark:bg-zinc-800/50 cursor-not-allowed" />
                        </div>
                        <div class="col-span-1">
                            <flux:input wire:model.live="items.{{ $i }}.discount" type="number" step="0.1" min="0" max="100" placeholder="0" x-on:input="disc = parseFloat($event.target.value) || 0" />
                        </div>
                        <div class="col-span-1 text-right text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            $<span x-text="(qty * price * (1 - disc / 100)).toFixed(2)"></span>
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
            </div>{{-- end overflow-x-auto --}}

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
