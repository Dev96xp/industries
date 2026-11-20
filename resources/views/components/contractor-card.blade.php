@props(['contractor'])

@php
    $gradients = [
        'from-amber-400 to-orange-500',
        'from-sky-400 to-blue-600',
        'from-violet-400 to-purple-600',
        'from-emerald-400 to-teal-600',
        'from-rose-400 to-pink-600',
        'from-indigo-400 to-blue-700',
    ];
    $initials = collect(preg_split('/\s+/', trim($contractor->company_name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<div class="flex items-center gap-3 rounded-2xl px-2 py-2.5 transition active:bg-zinc-100 dark:active:bg-zinc-800">
    <a href="{{ route('admin.contractors.edit', $contractor) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-linear-to-br {{ $gradients[$contractor->id % count($gradients)] }} text-sm font-semibold text-white shadow-sm">
            {{ $initials ?: '?' }}
        </span>
        <span class="min-w-0">
            <span class="block truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $contractor->company_name }}</span>
            <span class="block truncate text-sm text-zinc-500">{{ $contractor->contact_name ?? 'No contact name' }}</span>
        </span>
    </a>
    @if($contractor->phone)
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contractor->phone) }}" title="Call {{ $contractor->phone }}"
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition active:scale-95 dark:bg-emerald-500/10 dark:text-emerald-400">
            <flux:icon.phone variant="mini" class="size-5" />
        </a>
    @endif
</div>
