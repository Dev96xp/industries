<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $name.' — '.$company->company_name])
    <style>
        .bg-charcoal       { background-color: var(--color-charcoal); }
        .bg-charcoal-light { background-color: var(--color-charcoal-light); }
        .bg-charcoal-dark  { background-color: var(--color-charcoal-dark); }
    </style>
</head>
<body class="bg-charcoal text-zinc-900 antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm overflow-hidden rounded-3xl border border-white/10 bg-charcoal-light shadow-2xl">
            {{-- Blue top accent bar --}}
            <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>

            <div class="flex flex-col items-center px-6 py-10 text-center sm:px-8">
                {{-- Company logo/name — links to main site --}}
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-white">
                        <span class="text-xs font-bold tracking-tight" style="color: var(--color-charcoal)">NI</span>
                    </div>
                    <span class="text-[1.3125rem] font-bold text-white">{{ $company->company_name }}</span>
                </a>

                {{-- Avatar with initials --}}
                <div class="mb-5 flex size-24 items-center justify-center rounded-full bg-blue-600 text-3xl font-extrabold text-white ring-4 ring-white/10">
                    {{ collect(explode(' ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->join('') }}
                </div>

                {{-- Name — links to main site --}}
                <a href="{{ route('home') }}" class="text-2xl font-extrabold text-white hover:text-blue-300">
                    {{ $name }}
                </a>

                <p class="mt-2 text-xs font-bold uppercase tracking-widest text-blue-400">{{ $title }}</p>
                <p class="mt-1 text-sm text-zinc-400">{{ $company->company_name }}</p>
                <p class="mt-1 text-sm text-zinc-400">{{ $phone }}</p>

                {{-- Action buttons --}}
                <div class="mt-8 flex w-full flex-col gap-3">
                    <a
                        href="tel:{{ $phoneDigits }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        📞 Llamar
                    </a>
                    <a
                        href="https://wa.me/{{ $phoneDigits }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:border-blue-500/50 hover:bg-blue-600/10"
                    >
                        💬 WhatsApp
                    </a>
                    <a
                        href="{{ route('businesscard.vcard') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:border-zinc-400 hover:bg-white/5"
                    >
                        💾 Guardar contacto
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
