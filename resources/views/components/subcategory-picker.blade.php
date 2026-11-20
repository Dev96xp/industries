@props([
    /** @var \Illuminate\Support\Collection<int, \App\Models\Category> Categories with their selectable subcategories loaded */
    'categories',
    /** @var array<int, int|string> Currently selected subcategory ids */
    'selected' => [],
    'model' => 'subcategoryIds',
])

@php
    $selectedIds = collect($selected)->map(fn ($id) => (int) $id);
    $subcategoryById = $categories->flatMap(fn ($category) => $category->subcategories)->keyBy('id');
    $selectedSubcategories = $selectedIds->map(fn ($id) => $subcategoryById->get($id))->filter();
    $initialCategory = $selectedSubcategories->first()?->category_id ?? $categories->first()?->id;
@endphp

<div x-data="{ category: '{{ $initialCategory }}' }" class="flex flex-col gap-3">
    <div>
        <flux:label>Trades / Services</flux:label>
        <flux:description class="mt-1">Pick a category, then check every subcategory this contractor works in.</flux:description>
    </div>

    {{-- Selected subcategories --}}
    <div class="flex min-h-8 flex-wrap gap-1.5">
        @forelse($selectedSubcategories as $subcategory)
            <span wire:key="picked-{{ $subcategory->id }}"
                class="inline-flex items-center gap-1 rounded-full bg-blue-50 py-1 pl-2.5 pr-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                {{ $subcategory->label() }}
                <button type="button" title="Remove"
                    x-on:click="$wire.set('{{ $model }}', $wire.get('{{ $model }}').filter(id => id != {{ $subcategory->id }}))"
                    class="flex size-4 items-center justify-center rounded-full hover:bg-blue-200/60 dark:hover:bg-blue-500/20">
                    <flux:icon.x-mark variant="micro" class="size-3" />
                </button>
            </span>
        @empty
            <span class="text-xs italic text-zinc-400">No subcategories selected yet.</span>
        @endforelse
    </div>

    @if($categories->isEmpty())
        <p class="text-sm text-zinc-400">No categories with subcategories yet. Add them in Service Categories.</p>
    @else
        <select x-model="category"
            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500/40 sm:max-w-xs dark:border-white/10 dark:bg-white/10 dark:text-zinc-200">
            @foreach($categories as $category)
                @php $pickedHere = $category->subcategories->whereIn('id', $selectedIds)->count(); @endphp
                <option value="{{ $category->id }}">{{ $category->label() }}{{ $pickedHere ? " ({$pickedHere})" : '' }}</option>
            @endforeach
        </select>

        @foreach($categories as $category)
            <div x-show="category == '{{ $category->id }}'" x-cloak wire:key="picker-category-{{ $category->id }}"
                class="grid gap-x-4 gap-y-2 rounded-xl border border-zinc-100 p-3 sm:grid-cols-2 lg:grid-cols-3 dark:border-zinc-800">
                @foreach($category->subcategories as $subcategory)
                    <label wire:key="picker-sub-{{ $subcategory->id }}" class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model.live="{{ $model }}" value="{{ $subcategory->id }}"
                            class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800">
                        <span>{{ $subcategory->label() }}</span>
                        @unless($subcategory->is_active)
                            <span class="text-[10px] uppercase text-zinc-400">inactive</span>
                        @endunless
                    </label>
                @endforeach
            </div>
        @endforeach
    @endif
</div>
