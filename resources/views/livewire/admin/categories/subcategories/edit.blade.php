<?php

use App\Models\Category;
use App\Models\Subcategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Subcategory $subcategory;

    public int    $categoryId = 0;
    public string $name       = '';
    public string $nameEn     = '';
    public int    $sortOrder  = 0;
    public bool   $isActive   = true;

    public function mount(Subcategory $subcategory): void
    {
        $this->subcategory = $subcategory;
        $this->categoryId  = $subcategory->category_id;
        $this->name        = $subcategory->name;
        $this->nameEn      = $subcategory->name_en ?? '';
        $this->sortOrder   = $subcategory->sort_order;
        $this->isActive    = $subcategory->is_active;
    }

    public function save(): void
    {
        $this->validate([
            'categoryId' => ['required', 'exists:categories,id'],
            'name'       => ['required', 'string', 'max:255'],
            'nameEn'     => ['nullable', 'string', 'max:255'],
            'sortOrder'  => ['required', 'integer', 'min:0'],
            'isActive'   => ['boolean'],
        ]);

        $this->subcategory->update([
            'category_id' => $this->categoryId,
            'name'        => $this->name,
            'name_en'     => $this->nameEn ?: null,
            'sort_order'  => $this->sortOrder,
            'is_active'   => $this->isActive,
        ]);

        session()->flash('success', "Subcategory \"{$this->subcategory->label()}\" updated.");

        $this->redirectRoute('admin.categories.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'name_en']),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Edit: {{ $subcategory->label() }}</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:select wire:model="categoryId" label="Category" description="Move this subcategory to another category.">
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="name" label="Name (Spanish)" required />
            <flux:input wire:model="nameEn" label="Name (English)" />
            <flux:input :value="$subcategory->slug" label="Slug" disabled
                description="The slug cannot be edited: it is the key stored in existing services." />
            <flux:input wire:model="sortOrder" type="number" min="0" label="Order" />
            <flux:checkbox wire:model="isActive" label="Active subcategory" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>
</div>
