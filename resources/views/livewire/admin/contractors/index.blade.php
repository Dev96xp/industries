<?php

use App\Models\Contractor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search = '';

    #[\Livewire\Attributes\Url(as: 'category')]
    public string $categoryFilter = '';

    #[\Livewire\Attributes\Url(as: 'subcategory')]
    public string $subcategoryFilter = '';

    public function updatedCategoryFilter(): void
    {
        $this->subcategoryFilter = '';
    }

    public function deleteContractor(Contractor $contractor): void
    {
        $contractor->delete();
        session()->flash('success', "Contractor \"{$contractor->company_name}\" deleted.");
    }

    public function toggleActive(Contractor $contractor): void
    {
        $contractor->update(['is_active' => ! $contractor->is_active]);
    }

    public function with(): array
    {
        $filterCategories = \App\Models\Category::forSubcategoryPicker();

        return [
            'contractors' => Contractor::query()
                ->with('subcategories')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_name', 'like', "%{$this->search}%")
                    ->orWhere('specialty', 'like', "%{$this->search}%")
                    ->orWhereHas('subcategories', fn ($q) => $q
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name_en', 'like', "%{$this->search}%"))
                ))
                ->when($this->subcategoryFilter, fn ($q) => $q->whereHas('subcategories', fn ($q) => $q->whereKey($this->subcategoryFilter)))
                ->when($this->categoryFilter && ! $this->subcategoryFilter, fn ($q) => $q->whereHas('subcategories', fn ($q) => $q->where('category_id', $this->categoryFilter)))
                ->orderBy('company_name')
                ->get(),
            'filterCategories'    => $filterCategories,
            'filterSubcategories' => $this->categoryFilter
                ? ($filterCategories->firstWhere('id', (int) $this->categoryFilter)?->subcategories ?? collect())
                : collect(),
            ...$this->mobileBrowseTree(),
        ];
    }

    /**
     * Contractors grouped as category → subcategory → contractors for the mobile browser.
     *
     * @return array{browseTree: \Illuminate\Support\Collection<int, array{category: \App\Models\Category, count: int, subcategories: \Illuminate\Support\Collection<int, array{subcategory: \App\Models\Subcategory, contractors: \Illuminate\Support\Collection<int, Contractor>}>}>, unclassified: \Illuminate\Support\Collection<int, Contractor>}
     */
    private function mobileBrowseTree(): array
    {
        if ($this->search) {
            return ['browseTree' => collect(), 'unclassified' => collect()];
        }

        $contractors = Contractor::with('subcategories.category')->orderBy('company_name')->get();

        $browseTree = $contractors
            ->flatMap(fn (Contractor $contractor) => $contractor->subcategories->map(fn ($subcategory) => ['contractor' => $contractor, 'subcategory' => $subcategory]))
            ->filter(fn (array $pair) => $pair['subcategory']->category !== null)
            ->groupBy(fn (array $pair) => $pair['subcategory']->category_id)
            ->map(fn ($pairs) => [
                'category'      => $pairs->first()['subcategory']->category,
                'count'         => $pairs->pluck('contractor.id')->unique()->count(),
                'subcategories' => $pairs
                    ->groupBy(fn (array $pair) => $pair['subcategory']->id)
                    ->map(fn ($subPairs) => [
                        'subcategory' => $subPairs->first()['subcategory'],
                        'contractors' => $subPairs->pluck('contractor')->values(),
                    ])
                    ->sortBy(fn (array $group) => [$group['subcategory']->sort_order, $group['subcategory']->id])
                    ->values(),
            ])
            ->sortBy(fn (array $group) => [$group['category']->sort_order, $group['category']->id])
            ->values();

        return [
            'browseTree'   => $browseTree,
            'unclassified' => $contractors->filter(fn (Contractor $contractor) => $contractor->subcategories->isEmpty())->values(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Contractors</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage external companies you work with.</flux:text>
        </div>
        <flux:button href="{{ route('admin.contractors.create') }}" variant="primary" icon="plus" wire:navigate>
            New Contractor
        </flux:button>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Search & filters --}}
    <div class="flex flex-col gap-3 lg:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by company, contact or trade..." icon="magnifying-glass" />
        </div>
        <div class="hidden grid-cols-2 gap-3 sm:grid lg:w-[28rem]">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">All categories</flux:select.option>
                @foreach($filterCategories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="subcategoryFilter" :disabled="! $categoryFilter">
                <flux:select.option value="">{{ $categoryFilter ? 'All subcategories' : 'Pick a category first' }}</flux:select.option>
                @foreach($filterSubcategories as $subcategory)
                    <flux:select.option value="{{ $subcategory->id }}">{{ $subcategory->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Mobile browser: categories → subcategories → contractors --}}
    @unless($search)
        <div class="sm:hidden" x-data="{ openCategory: null, sheet: null, sheetTitle: '', dragY: 0, startY: 0 }"
            x-effect="document.body.classList.toggle('overflow-hidden', sheet !== null)">

            @if($browseTree->isEmpty() && $unclassified->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-zinc-300 py-14 text-center dark:border-zinc-700">
                    <flux:icon.building-office-2 class="size-10 text-zinc-300" />
                    <p class="mt-3 text-sm text-zinc-400">No contractors yet. Add your first one.</p>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($browseTree as $group)
                        @php $category = $group['category']; @endphp
                        <div wire:key="browse-cat-{{ $category->id }}"
                            class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200/70 transition dark:bg-zinc-900 dark:ring-zinc-800"
                            :class="openCategory === {{ $category->id }} && 'shadow-lg ring-zinc-300 dark:ring-zinc-700'">
                            <button type="button"
                                x-on:click="openCategory = openCategory === {{ $category->id }} ? null : {{ $category->id }}"
                                class="flex w-full items-center gap-4 px-4 py-4 text-left transition active:scale-[0.99]">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-2xl dark:bg-zinc-800">{{ $category->emoji() }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $category->label() }}</span>
                                    <span class="block text-xs text-zinc-500">
                                        {{ $group['count'] }} {{ str('contractor')->plural($group['count']) }}
                                        · {{ $group['subcategories']->count() }} {{ str('trade')->plural($group['subcategories']->count()) }}
                                    </span>
                                </span>
                                <flux:icon.chevron-down variant="mini" class="size-5 shrink-0 text-zinc-400 transition-transform duration-300"
                                    x-bind:class="openCategory === {{ $category->id }} && 'rotate-180'" />
                            </button>

                            <div x-show="openCategory === {{ $category->id }}" x-collapse x-cloak>
                                <div class="flex flex-col px-2 pb-2">
                                    @foreach($group['subcategories'] as $subGroup)
                                        @php $subcategory = $subGroup['subcategory']; @endphp
                                        <button type="button" wire:key="browse-sub-{{ $subcategory->id }}"
                                            x-on:click="sheet = 'sub-{{ $subcategory->id }}'; sheetTitle = @js($subcategory->label())"
                                            class="flex items-center gap-3 rounded-2xl px-3 py-3 text-left transition active:bg-zinc-100 dark:active:bg-zinc-800">
                                            <span class="size-1.5 shrink-0 rounded-full bg-blue-500"></span>
                                            <span class="flex-1 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $subcategory->label() }}</span>
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $subGroup['contractors']->count() }}</span>
                                            <flux:icon.chevron-right variant="micro" class="text-zinc-300 dark:text-zinc-600" />
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($unclassified->isNotEmpty())
                        <button type="button" x-on:click="sheet = 'unclassified'; sheetTitle = 'Unclassified'"
                            class="flex w-full items-center gap-4 rounded-3xl bg-white px-4 py-4 text-left shadow-sm ring-1 ring-zinc-200/70 transition active:scale-[0.99] dark:bg-zinc-900 dark:ring-zinc-800">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-2xl dark:bg-zinc-800">📋</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-base font-semibold text-zinc-900 dark:text-white">Unclassified</span>
                                <span class="block text-xs text-zinc-500">{{ $unclassified->count() }} {{ str('contractor')->plural($unclassified->count()) }} without trades yet</span>
                            </span>
                            <flux:icon.chevron-right variant="mini" class="size-5 shrink-0 text-zinc-400" />
                        </button>
                    @endif
                </div>
            @endif

            {{-- Bottom sheet --}}
            <template x-teleport="body">
                <div x-show="sheet !== null" x-cloak class="fixed inset-0 z-50 sm:hidden" x-on:keydown.escape.window="sheet = null">
                    <div x-show="sheet !== null" x-transition.opacity.duration.300ms
                        x-on:click="sheet = null" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>

                    <div x-show="sheet !== null"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="translate-y-full"
                        x-transition:enter-end="translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="translate-y-0"
                        x-transition:leave-end="translate-y-full"
                        :style="dragY ? `transform: translateY(${dragY}px)` : ''"
                        class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-3xl bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl dark:bg-zinc-900">

                        <div class="shrink-0 cursor-grab px-5 pb-3 pt-3"
                            x-on:touchstart="startY = $event.touches[0].clientY"
                            x-on:touchmove="dragY = Math.max(0, $event.touches[0].clientY - startY)"
                            x-on:touchend="if (dragY > 90) { sheet = null } dragY = 0">
                            <div class="mx-auto h-1.5 w-10 rounded-full bg-zinc-300 dark:bg-zinc-700"></div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <h3 class="truncate text-lg font-bold text-zinc-900 dark:text-white" x-text="sheetTitle"></h3>
                                <button type="button" x-on:click="sheet = null"
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                                    <flux:icon.x-mark variant="micro" />
                                </button>
                            </div>
                        </div>

                        <div class="overflow-y-auto overscroll-contain px-3 pb-4">
                            @foreach($browseTree as $group)
                                @foreach($group['subcategories'] as $subGroup)
                                    <div x-show="sheet === 'sub-{{ $subGroup['subcategory']->id }}'" wire:key="sheet-sub-{{ $subGroup['subcategory']->id }}" class="flex flex-col">
                                        @foreach($subGroup['contractors'] as $contractor)
                                            <x-contractor-card :contractor="$contractor" wire:key="sheet-{{ $subGroup['subcategory']->id }}-{{ $contractor->id }}" />
                                        @endforeach
                                    </div>
                                @endforeach
                            @endforeach
                            <div x-show="sheet === 'unclassified'" class="flex flex-col">
                                @foreach($unclassified as $contractor)
                                    <x-contractor-card :contractor="$contractor" wire:key="sheet-unclassified-{{ $contractor->id }}" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endunless

    {{-- Table --}}
    @if($contractors->isEmpty())
        <div class="{{ $search ? 'flex' : 'hidden sm:flex' }} flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-office-2 class="size-10 text-zinc-300" />
            <p class="mt-3 text-sm text-zinc-400">
                {{ ($search || $categoryFilter) ? 'No contractors match your filters.' : 'No contractors yet. Add your first one.' }}
            </p>
        </div>
    @else
        <div class="{{ $search ? '' : 'hidden sm:block' }} overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Company</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 sm:table-cell">Contact</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 sm:table-cell">Trades</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 md:table-cell">Phone</th>
                            <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400 sm:hidden">Phone</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 lg:table-cell">Email</th>
                            <th class="hidden px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400 sm:table-cell">Status</th>
                            <th class="hidden px-4 py-3 sm:table-cell"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($contractors as $contractor)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    <a href="{{ route('admin.contractors.edit', $contractor) }}" wire:navigate
                                        class="hover:text-blue-600 hover:underline dark:hover:text-blue-400">
                                        {{ $contractor->company_name }}
                                    </a>
                                    @if($contractor->contact_name)
                                        <p class="mt-0.5 text-sm font-normal text-zinc-500 sm:hidden">{{ $contractor->contact_name }}</p>
                                    @endif
                                    @if($contractor->subcategories->isNotEmpty())
                                        <div class="mt-1.5 flex flex-wrap gap-1 sm:hidden">
                                            @foreach($contractor->subcategories as $subcategory)
                                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $subcategory->label() }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 sm:table-cell">
                                    {{ $contractor->contact_name ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 sm:table-cell">
                                    @if($contractor->subcategories->isNotEmpty())
                                        <div class="flex max-w-xs flex-wrap gap-1">
                                            @foreach($contractor->subcategories as $subcategory)
                                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $subcategory->label() }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs italic text-zinc-400">{{ $contractor->specialty ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 md:table-cell">
                                    {{ $contractor->phone ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right sm:hidden">
                                    @if($contractor->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contractor->phone) }}"
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400">
                                            <flux:icon.phone variant="micro" class="size-3.5" /> {{ $contractor->phone }}
                                        </a>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-500 lg:table-cell">
                                    {{ $contractor->email ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    <button wire:click="toggleActive({{ $contractor->id }})" title="Toggle status">
                                        <flux:badge color="{{ $contractor->is_active ? 'green' : 'zinc' }}" size="sm">
                                            {{ $contractor->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>
                                    </button>
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button href="{{ route('admin.contractors.edit', $contractor) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                                        <flux:button wire:click="deleteContractor({{ $contractor->id }})"
                                            wire:confirm="Delete '{{ $contractor->company_name }}'? This cannot be undone."
                                            size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
