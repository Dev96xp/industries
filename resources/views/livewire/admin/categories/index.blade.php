<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name        = '';
    public string $description = '';
    public bool   $is_active   = true;
    public ?int   $editingId   = null;

    public function save(): void
    {
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $data = [
            'name'        => $validated['name'],
            'description' => $validated['description'] ?: null,
            'is_active'   => $validated['is_active'],
        ];

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            session()->flash('success', "Category \"{$data['name']}\" updated.");
        } else {
            Category::create($data);
            session()->flash('success', "Category \"{$data['name']}\" created.");
        }

        $this->resetForm();
        $this->modal('category-form')->close();
    }

    public function edit(Category $category): void
    {
        $this->editingId   = $category->id;
        $this->name        = $category->name;
        $this->description = $category->description ?? '';
        $this->is_active   = $category->is_active;
        $this->modal('category-form')->show();
    }

    public function toggleActive(Category $category): void
    {
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(Category $category): void
    {
        $category->delete();
        session()->flash('success', "Category \"{$category->name}\" deleted.");
    }

    public function newCategory(): void
    {
        $this->resetForm();
        $this->modal('category-form')->show();
    }

    private function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->description = '';
        $this->is_active   = true;
    }

    public function with(): array
    {
        return [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Categories</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Product categories used in quotes.</flux:text>
        </div>
        <flux:button wire:click="newCategory" variant="primary" icon="plus">
            New Category
        </flux:button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Table --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if($categories->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <flux:icon.tag class="size-10 text-zinc-300" />
                <p class="mt-3 text-sm text-zinc-400">No categories yet. Create your first one.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="hidden px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 md:table-cell">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Products</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($categories as $category)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $category->name }}
                                </td>
                                <td class="hidden px-6 py-4 text-zinc-500 md:table-cell">
                                    {{ $category->description ?: '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <flux:badge color="blue" size="sm">{{ $category->products_count }}</flux:badge>
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActive({{ $category->id }})">
                                        <flux:badge color="{{ $category->is_active ? 'green' : 'zinc' }}" size="sm">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button wire:click="edit({{ $category->id }})" variant="ghost" size="sm" icon="pencil">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="delete({{ $category->id }})"
                                            wire:confirm="Delete '{{ $category->name }}'? Products in this category will be uncategorized."
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
    <flux:modal name="category-form" class="w-full max-w-md">
        <div class="flex flex-col gap-4 p-1">
            <flux:heading size="lg">{{ $editingId ? 'Edit Category' : 'New Category' }}</flux:heading>

            <flux:input wire:model="name" label="Name" placeholder="e.g. Materials" />
            <flux:textarea wire:model="description" label="Description (optional)" placeholder="Brief description..." rows="2" />
            <flux:checkbox wire:model="is_active" label="Active (visible when selecting products)" />

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
