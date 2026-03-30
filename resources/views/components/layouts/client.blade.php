<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>My Account — {{ config('app.name') }}</title>
</head>
<body class="min-h-screen bg-zinc-50 antialiased">

    {{-- Header --}}
    <header class="fixed top-0 z-50 w-full border-b border-white/10 backdrop-blur-md" style="background-color: rgba(43,45,48,0.95);">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-white">
                    <span class="text-sm font-bold tracking-tight" style="color: #2b2d30">NI</span>
                </div>
                <div>
                    <span class="block text-sm font-bold leading-none text-white sm:text-base">{{ config('app.name') }}</span>
                    <span class="block text-[9px] font-medium uppercase tracking-widest text-blue-300 sm:text-[10px]">Construction Group</span>
                </div>
            </a>

            <div class="flex items-center gap-4">
                <span class="hidden text-sm text-zinc-400 sm:block">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-white/10 px-3 py-1.5 text-sm font-medium text-zinc-300 transition hover:bg-white/5 hover:text-white">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="pt-20">
        {{ $slot }}
    </main>

    @fluxScripts
</body>
</html>
