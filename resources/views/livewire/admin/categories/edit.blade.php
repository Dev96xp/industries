<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Category $category;

    public string $name      = '';
    public string $nameEn    = '';
    public int    $sortOrder = 0;
    public bool   $isActive  = true;

    public function mount(Category $category): void
    {
        $this->category  = $category;
        $this->name      = $category->name;
        $this->nameEn    = $category->name_en ?? '';
        $this->sortOrder = $category->sort_order;
        $this->isActive  = $category->is_active;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => ['required', 'string', 'max:255'],
            'nameEn'    => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive'  => ['boolean'],
        ]);

        $this->category->update([
            'name'       => $this->name,
            'name_en'    => $this->nameEn ?: null,
            'sort_order' => $this->sortOrder,
            'is_active'  => $this->isActive,
        ]);

        session()->flash('success', "Category \"{$this->category->name}\" updated.");

        $this->redirectRoute('admin.categories.index', navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Edit: {{ $category->name }}</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:input wire:model="name" label="Name (Spanish)" required />
            <flux:input wire:model="nameEn" label="Name (English)" />
            <flux:input :value="$category->slug" label="Slug" disabled
                description="The slug cannot be edited: it is the key stored in existing services." />
            <flux:input wire:model="sortOrder" type="number" min="0" label="Order" />
            <flux:checkbox wire:model="isActive" label="Active category" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>
</div>
