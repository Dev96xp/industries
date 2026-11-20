<?php

use App\Models\Category;
use App\Models\Subcategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function toggleCategory(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->subcategories()->exists()) {
            session()->flash('error', "\"{$category->name}\" has subcategories. Delete or move them first.");

            return;
        }

        if ($category->products()->exists()) {
            session()->flash('error', "\"{$category->name}\" is used by products. Move those products to another category first.");

            return;
        }

        $category->delete();
        session()->flash('success', "Category \"{$category->name}\" deleted.");
    }

    public function toggleSubcategory(int $id): void
    {
        $subcategory = Subcategory::findOrFail($id);
        $subcategory->update(['is_active' => ! $subcategory->is_active]);
    }

    public function deleteSubcategory(int $id): void
    {
        $subcategory = Subcategory::findOrFail($id);

        if ($subcategory->isInUse()) {
            session()->flash('error', "\"{$subcategory->name}\" is assigned to contractors. Unassign it from them first.");

            return;
        }

        $subcategory->delete();
        session()->flash('success', "Subcategory \"{$subcategory->name}\" deleted.");
    }

    public function with(): array
    {
        $categories = Category::with(['subcategories' => fn ($q) => $q->withCount('contractors')])
            ->withCount(['subcategories', 'products'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'categories'         => $categories,
            'subcategoriesCount' => $categories->sum('subcategories_count'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Service Categories</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                {{ $categories->count() }} {{ str('category')->plural($categories->count()) }}
                · {{ $subcategoriesCount }} {{ str('subcategory')->plural($subcategoriesCount) }}
            </flux:text>
        </div>
        <flux:button href="{{ route('admin.categories.create') }}" variant="primary" icon="plus" wire:navigate>
            New Category
        </flux:button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle">{{ session('error') }}</flux:callout>
    @endif

    @if($categories->isEmpty())
        <flux:card class="flex flex-col items-center justify-center py-14 text-center">
            <flux:icon.tag class="size-10 text-zinc-300 dark:text-zinc-600" />
            <p class="mt-3 text-sm text-zinc-400">No categories registered yet.</p>
        </flux:card>
    @else
        <div class="flex flex-col gap-3">
            @foreach($categories as $category)
                @php
                    $hasSubcategories = $category->subcategories_count > 0;
                    $hasProducts      = $category->products_count > 0;
                    $deleteBlockedBy  = $hasSubcategories
                        ? 'Delete or move its subcategories first'
                        : ($hasProducts ? 'Used by products — move them to another category first' : null);
                @endphp

                <details wire:key="category-{{ $category->id }}" wire:ignore.self
                    class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 px-4 py-3 transition hover:bg-zinc-50 sm:flex-nowrap dark:hover:bg-zinc-800/50 [&::-webkit-details-marker]:hidden">
                        <flux:icon.chevron-right variant="micro" class="shrink-0 text-zinc-400 transition-transform duration-200 group-open:rotate-90" />

                        <div class="flex min-w-0 flex-1 items-baseline gap-2">
                            <span class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $category->label() }}</span>
                            <span class="hidden truncate font-mono text-xs text-zinc-400 sm:inline">{{ $category->slug }}</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <flux:badge size="sm" color="zinc">
                                {{ $category->subcategories_count }} {{ str('subcategory')->plural($category->subcategories_count) }}
                            </flux:badge>
                            @if($hasProducts)
                                <flux:badge size="sm" color="blue">{{ $category->products_count }} {{ str('product')->plural($category->products_count) }}</flux:badge>
                            @endif
                            <span class="hidden w-8 text-right text-xs tabular-nums text-zinc-400 sm:inline">#{{ $category->sort_order }}</span>

                            <button type="button" wire:click.prevent="toggleCategory({{ $category->id }})" title="Toggle status">
                                <flux:badge size="sm" color="{{ $category->is_active ? 'green' : 'zinc' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </button>

                            <flux:button href="{{ route('admin.categories.edit', $category) }}" wire:navigate
                                size="sm" variant="ghost" icon="pencil" title="Edit category" />

                            <flux:button wire:click.prevent="deleteCategory({{ $category->id }})"
                                wire:confirm="Delete the category &quot;{{ $category->name }}&quot;?"
                                size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600"
                                :disabled="(bool) $deleteBlockedBy" title="{{ $deleteBlockedBy ?? 'Delete category' }}" />
                        </div>
                    </summary>

                    <div class="border-t border-zinc-100 px-4 pb-4 pt-2 dark:border-zinc-800">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Subcategory</flux:table.column>
                                <flux:table.column class="hidden sm:table-cell">English</flux:table.column>
                                <flux:table.column class="hidden md:table-cell">Slug</flux:table.column>
                                <flux:table.column class="hidden sm:table-cell">Order</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse($category->subcategories as $subcategory)
                                    @php $inUse = $subcategory->contractors_count > 0; @endphp
                                    <flux:table.row wire:key="subcategory-{{ $subcategory->id }}">
                                        <flux:table.cell class="font-medium text-zinc-800 dark:text-zinc-100">{{ $subcategory->name }}</flux:table.cell>
                                        <flux:table.cell class="hidden sm:table-cell">{{ $subcategory->name_en ?? '—' }}</flux:table.cell>
                                        <flux:table.cell class="hidden font-mono text-xs text-zinc-400 md:table-cell">{{ $subcategory->slug }}</flux:table.cell>
                                        <flux:table.cell class="hidden tabular-nums sm:table-cell">{{ $subcategory->sort_order }}</flux:table.cell>
                                        <flux:table.cell>
                                            <button type="button" wire:click="toggleSubcategory({{ $subcategory->id }})" title="Toggle status">
                                                <flux:badge size="sm" color="{{ $subcategory->is_active ? 'green' : 'zinc' }}">
                                                    {{ $subcategory->is_active ? 'Active' : 'Inactive' }}
                                                </flux:badge>
                                            </button>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center justify-end gap-1">
                                                <flux:button href="{{ route('admin.categories.subcategories.edit', $subcategory) }}" wire:navigate
                                                    size="sm" variant="ghost" icon="pencil" title="Edit subcategory" />
                                                <flux:button wire:click="deleteSubcategory({{ $subcategory->id }})"
                                                    wire:confirm="Delete the subcategory &quot;{{ $subcategory->name }}&quot;?"
                                                    size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600"
                                                    :disabled="$inUse" title="{{ $inUse ? 'Assigned to contractors — unassign them first' : 'Delete subcategory' }}" />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="6" class="py-6 text-center text-zinc-400">No subcategories yet.</flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>

                        <div class="mt-3">
                            <flux:button href="{{ route('admin.categories.subcategories.create', $category) }}" wire:navigate
                                size="sm" icon="plus">
                                Add Subcategory
                            </flux:button>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    @endif

</div>
