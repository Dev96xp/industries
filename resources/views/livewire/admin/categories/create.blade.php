<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name      = '';
    public string $nameEn    = '';
    public int    $sortOrder = 0;

    public function mount(): void
    {
        $this->sortOrder = (int) Category::max('sort_order') + 1;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => ['required', 'string', 'max:255'],
            'nameEn'    => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ]);

        $category = Category::create([
            'name'       => $this->name,
            'name_en'    => $this->nameEn ?: null,
            'slug'       => Category::uniqueSlug($this->nameEn ?: $this->name),
            'sort_order' => $this->sortOrder,
            'is_active'  => true,
        ]);

        session()->flash('success', "Category \"{$category->label()}\" created.");

        $this->redirectRoute('admin.categories.index', navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">New Category</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:input wire:model="name" label="Name (Spanish)" placeholder="e.g. Construcción" required />
            <flux:input wire:model="nameEn" label="Name (English)" placeholder="e.g. Construction" description="Optional. Used for the slug when provided." />
            <flux:input wire:model="sortOrder" type="number" min="0" label="Order" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>
</div>
