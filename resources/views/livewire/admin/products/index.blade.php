<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    // Form fields
    public string  $name        = '';
    public string  $description = '';
    public string  $unit_price  = '';
    public string  $unit        = '';
    public ?int    $category_id = null;
    public bool    $is_active   = true;
    public ?int    $editingId   = null;

    // Search & filter
    public string $search      = '';
    public ?int   $filterCategory = null;

    public function save(): void
    {
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price'  => ['nullable', 'numeric', 'min:0'],
            'unit'        => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_active'   => ['boolean'],
        ]);

        $data = [
            'name'        => $validated['name'],
            'description' => $validated['description'] ?: null,
            'unit_price'  => $validated['unit_price'] ?: null,
            'unit'        => $validated['unit'] ?: null,
            'category_id' => $validated['category_id'] ?: null,
            'is_active'   => $validated['is_active'],
        ];

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
            session()->flash('success', "Product \"{$data['name']}\" updated.");
        } else {
            Product::create($data);
            session()->flash('success', "Product \"{$data['name']}\" created.");
        }

        $this->resetForm();
        $this->modal('product-form')->close();
    }

    public function edit(Product $product): void
    {
        $this->editingId   = $product->id;
        $this->name        = $product->name;
        $this->description = $product->description ?? '';
        $this->unit_price  = $product->unit_price ? (string) $product->unit_price : '';
        $this->unit        = $product->unit ?? '';
        $this->category_id = $product->category_id;
        $this->is_active   = $product->is_active;
        $this->modal('product-form')->show();
    }

    public function toggleActive(Product $product): void
    {
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function delete(Product $product): void
    {
        $product->delete();
        session()->flash('success', "Product \"{$product->name}\" deleted.");
    }

    public function newProduct(): void
    {
        $this->resetForm();
        $this->modal('product-form')->show();
    }

    private function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->description = '';
        $this->unit_price  = '';
        $this->unit        = '';
        $this->category_id = null;
        $this->is_active   = true;
    }

    public function with(): array
    {
        return [
            'products'   => Product::with('category')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$this->search}%")))
                ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
                ->orderBy('name')
                ->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Products</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Catalog of products and services used in quotes.</flux:text>
        </div>
        <flux:button wire:click="newProduct" variant="primary" icon="plus">
            New Product
        </flux:button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Search & Filter --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input wire:model.live="search" placeholder="Search products..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="filterCategory" class="sm:w-52">
            <flux:select.option value="">All Categories</flux:select.option>
            @foreach($categories as $cat)
                <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if($filterCategory)
            <flux:button wire:click="$set('filterCategory', null)" variant="ghost" size="sm" icon="x-mark">
                Clear
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if($products->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <flux:icon.cube class="size-10 text-zinc-300" />
                <p class="mt-3 text-sm text-zinc-400">
                    {{ $search ? 'No products match your search.' : 'No products yet. Create your first one.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 md:table-cell">Category</th>
                            <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 sm:table-cell">Unit Price</th>
                            <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 sm:table-cell">Unit</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($products as $product)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $product->name }}</p>
                                    @if($product->description)
                                        <p class="mt-0.5 text-xs text-zinc-400">{{ Str::limit($product->description, 60) }}</p>
                                    @endif
                                </td>
                                <td class="hidden px-6 py-4 text-zinc-500 md:table-cell">
                                    {{ $product->category?->name ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 font-medium text-zinc-800 dark:text-zinc-200 sm:table-cell">
                                    {{ $product->unit_price ? '$' . number_format($product->unit_price, 2) : '—' }}
                                </td>
                                <td class="hidden px-6 py-4 text-zinc-500 sm:table-cell">
                                    {{ $product->unit ?: '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActive({{ $product->id }})">
                                        <flux:badge color="{{ $product->is_active ? 'green' : 'zinc' }}" size="sm">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button wire:click="edit({{ $product->id }})" variant="ghost" size="sm" icon="pencil">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="delete({{ $product->id }})"
                                            wire:confirm="Delete '{{ $product->name }}'? This cannot be undone."
                                            variant="ghost" size="sm" icon="trash">
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal --}}
    <flux:modal name="product-form" class="w-full max-w-lg">
        <div class="flex flex-col gap-4 p-1">
            <flux:heading size="lg">{{ $editingId ? 'Edit Product' : 'New Product' }}</flux:heading>

            <flux:input wire:model="name" label="Name" placeholder="e.g. Concrete Block" />
            <flux:textarea wire:model="description" label="Description (optional)" placeholder="Brief description..." rows="2" />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="unit_price" label="Unit Price ($)" type="number" step="0.01" placeholder="0.00" />
                <flux:input wire:model="unit" label="Unit" placeholder="e.g. sqft, each, hr" />
            </div>

            <flux:select wire:model="category_id" label="Category">
                <flux:select.option value="">— Select category —</flux:select.option>
                @foreach($categories as $cat)
                    <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox wire:model="is_active" label="Active (visible when selecting products in quotes)" />

            <div class="flex gap-2 pt-1">
                <flux:button wire:click="save" variant="primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
