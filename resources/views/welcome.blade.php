<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>Nucleus Industries — Building Dreams Into Reality</title>
    <style>
        html { scroll-behavior: smooth; }

        .bg-charcoal       { background-color: var(--color-charcoal); }
        .bg-charcoal-light { background-color: var(--color-charcoal-light); }
        .bg-charcoal-dark  { background-color: var(--color-charcoal-dark); }
        .bg-dove           { background-color: var(--color-dove); }
        .bg-dove-dark      { background-color: var(--color-dove-dark); }
        .text-charcoal     { color: var(--color-charcoal); }
        .text-dove         { color: var(--color-dove); }
        .border-dove-dark  { border-color: var(--color-dove-dark); }
    </style>
</head>
<body class="bg-white text-zinc-900 antialiased">

    {{-- Navigation --}}
    <header
        x-data="{ open: false }"
        class="fixed top-0 z-50 w-full border-b border-white/10 backdrop-blur-md"
        style="background-color: rgba(43,45,48,0.95);"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-white sm:size-10">
                    <span class="text-sm font-bold tracking-tight" style="color: var(--color-charcoal)">NI</span>
                </div>
                <div>
                    <span class="block text-sm font-bold leading-none text-white sm:text-base">{{ $company->company_name }}</span>
                    <span class="block text-[9px] font-medium uppercase tracking-widest text-blue-300 sm:text-[10px]">Construction Group</span>
                </div>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden items-center gap-6 lg:flex xl:gap-8">
                <a href="#services" class="text-sm font-medium text-zinc-400 transition-colors hover:text-white">Services</a>
                <a href="#about" class="text-sm font-medium text-zinc-400 transition-colors hover:text-white">About</a>
                <a href="#projects" class="text-sm font-medium text-zinc-400 transition-colors hover:text-white">Projects</a>
                <a href="#process" class="text-sm font-medium text-zinc-400 transition-colors hover:text-white">Our Process</a>
                <a href="#contact" class="text-sm font-medium text-zinc-400 transition-colors hover:text-white">Contact</a>
            </nav>

            <div class="flex items-center gap-3">
                {{-- Desktop CTA --}}
                <div class="hidden sm:flex sm:items-center sm:gap-3">
                    @auth
                        @if(auth()->user()->hasRole('client'))
                            <a href="{{ route('client.profile') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">My Profile</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="hidden text-sm font-medium text-zinc-400 transition hover:text-white lg:block">Sign in</a>
                        <a href="#contact" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Get a Quote</a>
                    @endauth
                </div>

                {{-- Mobile hamburger --}}
                <button
                    @click="open = !open"
                    class="flex size-9 items-center justify-center rounded-lg border border-white/10 text-zinc-400 hover:bg-white/5 hover:text-white lg:hidden"
                    aria-label="Toggle menu"
                >
                    <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="border-t border-white/10 lg:hidden"
            style="background-color: rgba(43,45,48,0.98);"
            @click.away="open = false"
        >
            <nav class="flex flex-col px-4 py-4 sm:px-6">
                <a @click="open = false" href="#services" class="border-b border-white/5 py-3 text-sm font-medium text-zinc-300 hover:text-white">Services</a>
                <a @click="open = false" href="#about" class="border-b border-white/5 py-3 text-sm font-medium text-zinc-300 hover:text-white">About</a>
                <a @click="open = false" href="#projects" class="border-b border-white/5 py-3 text-sm font-medium text-zinc-300 hover:text-white">Projects</a>
                <a @click="open = false" href="#process" class="border-b border-white/5 py-3 text-sm font-medium text-zinc-300 hover:text-white">Our Process</a>
                <a @click="open = false" href="#contact" class="py-3 text-sm font-medium text-zinc-300 hover:text-white">Contact</a>
                <div class="mt-4 flex flex-col gap-2">
                    @auth
                        @if(auth()->user()->hasRole('client'))
                            <a href="{{ route('client.profile') }}" class="rounded-lg bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">My Profile</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg border border-white/10 px-4 py-3 text-center text-sm font-medium text-zinc-300">Sign in</a>
                        <a href="#contact" @click="open = false" class="rounded-lg bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">Get a Quote</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main>

        {{-- ===== HERO ===== --}}
        <section class="relative flex min-h-screen items-end bg-charcoal pb-16 pt-28 sm:pb-20 sm:pt-32 lg:pb-24">
            {{-- Hero photo background --}}
            @if($heroPhoto)
                <div class="absolute inset-0">
                    <img src="{{ $heroPhoto->url() }}" alt="Hero" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-charcoal/75"></div>
                </div>
            @else
                {{-- Grid overlay (default when no hero photo) --}}
                <div class="pointer-events-none absolute inset-0"
                    style="background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px); background-size: 64px 64px;">
                </div>
                {{-- Blue glow --}}
                <div class="pointer-events-none absolute left-0 top-0 h-full w-full sm:w-1/2">
                    <div class="h-[300px] w-[300px] rounded-full bg-blue-600/10 blur-[100px] sm:h-[500px] sm:w-[500px] sm:blur-[140px]"></div>
                </div>
            @endif

            <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-600/10 px-3 py-1.5 sm:mb-6 sm:px-4">
                        <span class="size-2 rounded-full bg-blue-400 animate-pulse"></span>
                        <span class="text-xs font-medium text-blue-300">Now accepting new projects for 2026</span>
                    </div>

                    <h1 class="text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl xl:text-8xl">
                        Building<br>
                        <span class="text-blue-400">Your Vision,</span><br>
                        <span class="text-zinc-400">Brick by Brick.</span>
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-zinc-400 sm:mt-8 sm:text-lg">
                        Nucleus Industries specializes in custom residential construction — from starter homes to dream builds. Every project is personal, and we treat it that way.
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:flex-wrap">
                        <a href="#projects" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:py-3">
                            View Our Projects
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a href="#contact" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:border-zinc-400 hover:bg-white/5 sm:py-3">
                            Free Consultation
                        </a>
                    </div>
                </div>

                {{-- Stats bar --}}
                <div class="mt-12 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/10 bg-white/5 sm:mt-20 sm:grid-cols-4">
                    @foreach([
                        ['num' => '17', 'label' => 'Homes Built'],
                        ['num' => '100%', 'label' => 'Client Satisfaction'],
                        ['num' => 'On Time', 'label' => 'Project Delivery'],
                        ['num' => 'Licensed', 'label' => '& Insured'],
                    ] as $stat)
                        <div class="flex flex-col items-center justify-center bg-charcoal py-6 sm:py-8">
                            <p class="text-2xl font-extrabold text-white sm:text-3xl">{{ $stat['num'] }}</p>
                            <p class="mt-1 text-[11px] font-medium text-zinc-500 sm:text-xs">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== SERVICES ===== --}}
        <section id="services" class="bg-dove py-16 sm:py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mb-10 sm:mb-16">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-600">What We Build</p>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <h2 class="text-3xl font-extrabold leading-tight text-charcoal sm:text-4xl lg:text-5xl">
                            Built for<br>How You Live
                        </h2>
                        <p class="max-w-sm text-sm text-zinc-500 sm:text-base">
                            We specialize in residential construction — custom homes, renovations, and commercial projects done right.
                        </p>
                    </div>
                </div>

                {{-- Two primary service cards --}}
                <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="group relative overflow-hidden rounded-2xl bg-charcoal p-6 sm:rounded-3xl sm:p-10">
                        <div class="pointer-events-none absolute right-0 top-0 h-48 w-48 translate-x-1/3 -translate-y-1/3 rounded-full bg-blue-500/10 blur-3xl transition group-hover:bg-blue-500/20 sm:h-64 sm:w-64"></div>
                        <div class="relative">
                            <div class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-blue-600/20 text-2xl sm:mb-6 sm:size-14 sm:text-3xl">🏠</div>
                            <h3 class="text-xl font-bold text-white sm:text-2xl">Residential Construction</h3>
                            <p class="mt-3 text-sm text-zinc-400 sm:text-base">Your home is one of the most important investments you'll make. We work closely with you from the first meeting to the final walkthrough to make sure it's exactly what you envisioned.</p>
                            <ul class="mt-5 space-y-2 sm:mt-6">
                                @foreach(['Custom Home Design & Build', 'Single-Family Homes', 'Home Additions & Extensions', 'Kitchen & Bathroom Remodels', 'Outdoor & Landscaping Work'] as $item)
                                    <li class="flex items-center gap-2 text-sm text-zinc-300">
                                        <svg class="size-4 shrink-0 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="#contact" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-400 hover:text-blue-300 sm:mt-8">
                                Start your home project →
                            </a>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm sm:rounded-3xl sm:p-10">
                        <div class="pointer-events-none absolute right-0 top-0 h-48 w-48 translate-x-1/3 -translate-y-1/3 rounded-full bg-blue-100/80 blur-3xl transition group-hover:bg-blue-200/80 sm:h-64 sm:w-64"></div>
                        <div class="relative">
                            <div class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-2xl sm:mb-6 sm:size-14 sm:text-3xl">🏢</div>
                            <h3 class="text-xl font-bold text-charcoal sm:text-2xl">Commercial Projects</h3>
                            <p class="mt-3 text-sm text-zinc-500 sm:text-base">We also take on commercial work — offices, local retail spaces, and light commercial builds where quality and attention to detail matter just as much.</p>
                            <ul class="mt-5 space-y-2 sm:mt-6">
                                @foreach(['Small Office Build-Outs', 'Local Retail Spaces', 'Warehouses & Workshops', 'Tenant Improvements', 'Light Commercial Renovations'] as $item)
                                    <li class="flex items-center gap-2 text-sm text-zinc-600">
                                        <svg class="size-4 shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="#contact" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700 sm:mt-8">
                                Start your commercial project →
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Additional services --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-6">
                    @foreach([
                        ['icon' => '🔧', 'title' => 'Renovations & Remodeling', 'desc' => 'Update your kitchen, bathroom, or entire home with craftsmanship you can trust.'],
                        ['icon' => '📐', 'title' => 'Design & Planning', 'desc' => 'We help you plan your project from concept to blueprint before breaking ground.'],
                        ['icon' => '🤝', 'title' => 'Honest Estimates', 'desc' => 'No surprises. We give you clear, detailed quotes so you know exactly what to expect.'],
                    ] as $service)
                        <div class="rounded-xl border border-dove-dark bg-white p-5 transition hover:border-blue-200 hover:shadow-md sm:rounded-2xl sm:p-7">
                            <div class="mb-3 text-2xl sm:mb-4 sm:text-3xl">{{ $service['icon'] }}</div>
                            <h3 class="mb-2 font-semibold text-charcoal">{{ $service['title'] }}</h3>
                            <p class="text-sm text-zinc-500">{{ $service['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== ABOUT ===== --}}
        <section id="about" class="bg-charcoal py-16 sm:py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-400">Who We Are</p>
                        <h2 class="text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">
                            More Than a<br>Contractor. A Partner.
                        </h2>
                        <p class="mt-5 text-sm text-zinc-400 sm:mt-6 sm:text-base">
                            Nucleus Industries was built on a simple belief: every family deserves a home that's built with care. We're a hands-on team that takes pride in our work and shows up for our clients every step of the way.
                        </p>
                        <p class="mt-4 text-sm text-zinc-400 sm:text-base">
                            We've completed 17 homes and counting — each one built with the same attention to detail as the first. We're fully licensed and insured, and we work closely with every client to make the process as smooth as possible.
                        </p>

                        <div class="mt-8 grid grid-cols-1 gap-3 sm:mt-10 sm:grid-cols-2 sm:gap-4">
                            @foreach([
                                ['icon' => '🛡️', 'title' => 'Safety First', 'desc' => 'A safe job site is a non-negotiable on every project'],
                                ['icon' => '⏱️', 'title' => 'On-Time Delivery', 'desc' => 'We respect your time and stick to agreed timelines'],
                                ['icon' => '📋', 'title' => 'Licensed & Insured', 'desc' => 'Fully licensed and insured for your peace of mind'],
                                ['icon' => '🤝', 'title' => 'Client Focused', 'desc' => 'Transparent communication from start to finish'],
                            ] as $item)
                                <div class="flex gap-4 rounded-xl border border-white/10 bg-white/5 p-4 sm:p-5">
                                    <span class="text-xl sm:text-2xl">{{ $item['icon'] }}</span>
                                    <div>
                                        <p class="font-semibold text-white text-sm sm:text-base">{{ $item['title'] }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 sm:text-sm">{{ $item['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative pb-10 sm:pb-12 lg:pb-0">
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-white/10 bg-charcoal-light sm:aspect-[4/5] sm:rounded-3xl">
                            @if($aboutPhoto)
                                <img src="{{ $aboutPhoto->url() }}" alt="About Nucleus Industries" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full flex-col items-center justify-center gap-3 text-zinc-700">
                                    <div class="text-6xl sm:text-7xl">🏗️</div>
                                    <p class="text-sm">Company photo goes here</p>
                                </div>
                            @endif
                        </div>
                        {{-- Floating card — anchored inside flow on mobile --}}
                        <div class="relative mt-4 rounded-2xl border border-white/10 bg-charcoal-dark p-5 shadow-2xl sm:absolute sm:-bottom-8 sm:-right-6 sm:mt-0 sm:p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xl sm:size-12">🏠</div>
                                <div>
                                    <p class="font-bold text-white">17 Homes Built</p>
                                    <p class="text-xs text-zinc-400">And every client still calls us back</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== PROJECTS / PHOTOS ===== --}}
        <section id="projects" class="bg-dove py-16 sm:py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mb-10 sm:mb-16">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-600">Portfolio</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <h2 class="text-3xl font-extrabold leading-tight text-charcoal sm:text-4xl lg:text-5xl">
                            Featured<br>Projects
                        </h2>
                        <a href="#contact" class="shrink-0 text-sm font-semibold text-blue-600 underline underline-offset-4 hover:text-blue-700">
                            Contact us →
                        </a>
                    </div>
                </div>

                @if($featuredPhotos->isNotEmpty())
                    {{-- Real photos from the database --}}
                    @php $first = $featuredPhotos->first(); $rest = $featuredPhotos->skip(1); @endphp

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        {{-- Feature photo --}}
                        <div class="lg:col-span-2">
                            <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm sm:rounded-3xl">
                                <div class="aspect-[16/9] overflow-hidden bg-blue-50">
                                    <img src="{{ $first->url() }}" alt="{{ $first->title }}" class="h-full w-full object-cover">
                                </div>
                                @if($first->title || $first->category)
                                    <div class="p-5 sm:p-8">
                                        @if($first->category)
                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($first->category) }}</span>
                                        @endif
                                        @if($first->title)
                                            <h3 class="mt-3 text-lg font-bold text-charcoal sm:text-xl">{{ $first->title }}</h3>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Side photos --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:flex lg:flex-col">
                            @foreach($rest->take(2) as $photo)
                                <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm sm:rounded-3xl lg:flex-1">
                                    <div class="aspect-video overflow-hidden bg-blue-50">
                                        <img src="{{ $photo->url() }}" alt="{{ $photo->title }}" class="h-full w-full object-cover">
                                    </div>
                                    @if($photo->title || $photo->category)
                                        <div class="p-4 sm:p-6">
                                            @if($photo->category)
                                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ ucfirst($photo->category) }}</span>
                                            @endif
                                            @if($photo->title)
                                                <h3 class="mt-2 font-bold text-charcoal text-sm sm:text-base">{{ $photo->title }}</h3>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($rest->count() > 2)
                        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                            @foreach($rest->skip(2)->take(3) as $photo)
                                <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm">
                                    <div class="aspect-video overflow-hidden bg-blue-50">
                                        <img src="{{ $photo->url() }}" alt="{{ $photo->title }}" class="h-full w-full object-cover">
                                    </div>
                                    @if($photo->title || $photo->category)
                                        <div class="p-4 sm:p-6">
                                            @if($photo->category)
                                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ ucfirst($photo->category) }}</span>
                                            @endif
                                            @if($photo->title)
                                                <h3 class="mt-2 font-semibold text-charcoal text-sm sm:text-base">{{ $photo->title }}</h3>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    {{-- Placeholder projects when no photos are uploaded yet --}}
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <div class="lg:col-span-2">
                            <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm sm:rounded-3xl">
                                <div class="aspect-[16/9] bg-blue-50">
                                    <div class="flex h-full items-center justify-center text-4xl sm:text-5xl">🏢</div>
                                </div>
                                <div class="p-5 sm:p-8">
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Residential</span>
                                    <h3 class="mt-3 text-lg font-bold text-charcoal sm:text-xl">Custom Family Home — Oakwood</h3>
                                    <p class="mt-2 text-sm text-zinc-500">A 2,400 sq ft custom single-family home built from the ground up, designed around the family's lifestyle and completed on schedule.</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:flex lg:flex-col">
                            @foreach([
                                ['emoji' => '🏠', 'type' => 'Residential', 'title' => 'Kitchen & Addition — Maplewood', 'desc' => 'Full kitchen remodel and master suite addition for a growing family.'],
                                ['emoji' => '🏡', 'type' => 'Residential', 'title' => 'New Build — Cedarwood', 'desc' => '1,800 sq ft starter home built from the ground up for a first-time buyer.'],
                            ] as $project)
                                <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm sm:rounded-3xl lg:flex-1">
                                    <div class="aspect-video bg-blue-50">
                                        <div class="flex h-full items-center justify-center text-3xl sm:text-4xl">{{ $project['emoji'] }}</div>
                                    </div>
                                    <div class="p-4 sm:p-6">
                                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $project['type'] }}</span>
                                        <h3 class="mt-2 font-bold text-charcoal text-sm sm:text-base">{{ $project['title'] }}</h3>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $project['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        @foreach([
                            ['emoji' => '🔧', 'type' => 'Renovation', 'title' => 'Full Home Remodel — Riverside', 'desc' => 'Complete interior renovation including new flooring, electrical, and bathrooms.'],
                            ['emoji' => '🏢', 'type' => 'Commercial', 'title' => 'Office Build-Out — Downtown', 'desc' => 'Professional office space built out for a local business.'],
                            ['emoji' => '🏠', 'type' => 'Residential', 'title' => 'Custom Home — Hillside', 'desc' => '3-bedroom custom home with open floor plan and covered back patio.'],
                        ] as $project)
                            <div class="overflow-hidden rounded-2xl border border-dove-dark bg-white shadow-sm">
                                <div class="aspect-video bg-blue-50">
                                    <div class="flex h-full items-center justify-center text-3xl sm:text-4xl">{{ $project['emoji'] }}</div>
                                </div>
                                <div class="p-4 sm:p-6">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $project['type'] }}</span>
                                    <h3 class="mt-2 font-semibold text-charcoal text-sm sm:text-base">{{ $project['title'] }}</h3>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $project['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ===== PROCESS ===== --}}
        <section id="process" class="bg-charcoal py-16 sm:py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mb-10 text-center sm:mb-16">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-400">How We Work</p>
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl">Our Simple Process</h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm text-zinc-400 sm:text-base">
                        From your first call to the final handover, we make building seamless and stress-free.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                    @foreach([
                        ['step' => '01', 'icon' => '💬', 'title' => 'Free Consultation', 'desc' => 'We meet to understand your vision, budget, and timeline. No commitment required.'],
                        ['step' => '02', 'icon' => '📋', 'title' => 'Design & Planning', 'desc' => 'Our design-build team creates detailed plans, 3D renders, and a fixed-price proposal.'],
                        ['step' => '03', 'icon' => '🔨', 'title' => 'Construction', 'desc' => 'Expert crews break ground with a dedicated project manager keeping you informed every week.'],
                        ['step' => '04', 'icon' => '🗝️', 'title' => 'Handover & Support', 'desc' => 'Final walkthrough, punch-list completion, and a comprehensive 2-year warranty.'],
                    ] as $step)
                        <div class="relative rounded-xl border border-white/10 bg-white/5 p-6 sm:rounded-2xl sm:p-8">
                            <div class="mb-4 flex items-center justify-between sm:mb-5">
                                <span class="text-2xl sm:text-3xl">{{ $step['icon'] }}</span>
                                <span class="text-4xl font-extrabold text-white/10 sm:text-5xl">{{ $step['step'] }}</span>
                            </div>
                            <div class="mb-3 h-0.5 w-10 rounded-full bg-blue-500"></div>
                            <h3 class="mb-2 font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="text-sm leading-relaxed text-zinc-400">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== TESTIMONIALS ===== --}}
        <section class="bg-dove py-16 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mb-10 text-center sm:mb-12">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-600">Testimonials</p>
                    <h2 class="text-3xl font-extrabold text-charcoal sm:text-4xl">What Our Clients Say</h2>
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    @foreach([
                        ['quote' => '"They built our home exactly the way we imagined it. The communication was great the whole time and they finished on schedule. We\'re so happy we chose Nucleus."', 'name' => 'Sarah & Michael T.', 'role' => 'Custom Home Client'],
                        ['quote' => '"We hired Nucleus to remodel our kitchen and add a bedroom. The quality of the work was outstanding and the crew was respectful and clean every single day."', 'name' => 'Carlos & Diana M.', 'role' => 'Home Renovation Client'],
                        ['quote' => '"Honest, hardworking, and detail-oriented. They gave us a fair quote, stuck to it, and delivered a home we\'re proud to live in."', 'name' => 'Robert L.', 'role' => 'New Home Build Client'],
                    ] as $review)
                        <div class="flex flex-col rounded-2xl border border-dove-dark bg-white p-6 shadow-sm sm:p-8">
                            <div class="mb-4 flex text-yellow-400">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                    </svg>
                                @endfor
                            </div>
                            <p class="flex-1 text-sm leading-relaxed text-zinc-600">{{ $review['quote'] }}</p>
                            <div class="mt-5 border-t border-dove-dark pt-5 sm:mt-6 sm:pt-6">
                                <p class="font-semibold text-charcoal">{{ $review['name'] }}</p>
                                <p class="text-sm text-zinc-400">{{ $review['role'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== CONTACT / CTA ===== --}}
        <section id="contact" class="bg-charcoal py-16 sm:py-20 lg:py-28">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="overflow-hidden rounded-2xl border border-white/10 sm:rounded-3xl">
                    {{-- Blue top accent bar --}}
                    <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-blue-400"></div>
                    <div class="bg-charcoal-light p-6 text-center sm:p-10 lg:p-12">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-blue-400">Let's Build Together</p>
                        <h2 class="text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl">
                            Ready to Start<br>Your Project?
                        </h2>
                        <p class="mx-auto mt-4 max-w-lg text-sm text-zinc-400 sm:mt-5 sm:text-base">
                            Whether it's a new home, a renovation, or a commercial project — we'd love to hear what you have in mind. Reach out for a free consultation and estimate.
                        </p>

                        <div class="mt-8 grid grid-cols-1 gap-3 sm:mt-10 sm:grid-cols-3 sm:gap-4">
                            @if($company->phone)
                                <a href="tel:{{ preg_replace('/\D/', '', $company->phone) }}" class="flex items-center gap-4 rounded-xl border border-white/10 bg-white/5 p-4 text-left transition hover:border-blue-500/50 hover:bg-blue-600/10 sm:flex-col sm:items-center sm:p-6 sm:text-center">
                                    <div class="text-2xl sm:mb-1 sm:text-3xl">📞</div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Call Us</p>
                                        <p class="mt-0.5 text-sm text-zinc-300">{{ $company->phone }}</p>
                                    </div>
                                </a>
                            @endif
                            @if($company->email)
                                <a href="mailto:{{ $company->email }}" class="flex items-center gap-4 rounded-xl border border-white/10 bg-white/5 p-4 text-left transition hover:border-blue-500/50 hover:bg-blue-600/10 sm:flex-col sm:items-center sm:p-6 sm:text-center">
                                    <div class="text-2xl sm:mb-1 sm:text-3xl">✉️</div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Email Us</p>
                                        <p class="mt-0.5 text-sm text-zinc-300">{{ $company->email }}</p>
                                    </div>
                                </a>
                            @endif
                            @if($company->address)
                                <div class="flex items-center gap-4 rounded-xl border border-white/10 bg-white/5 p-4 text-left sm:flex-col sm:items-center sm:p-6 sm:text-center">
                                    <div class="text-2xl sm:mb-1 sm:text-3xl">📍</div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Our Office</p>
                                        <p class="mt-0.5 text-sm text-zinc-300">{{ $company->address }}{{ $company->city ? ', '.$company->city : '' }}{{ $company->state ? ', '.$company->state : '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-8 sm:mt-10">
                            <a href="{{ $company->email ? 'mailto:'.$company->email : '#' }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 py-4 text-sm font-bold text-white shadow-lg transition hover:bg-blue-700 sm:w-auto">
                                Request a Free Quote
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 bg-charcoal-dark py-10 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <div class="max-w-xs">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-blue-600">
                            <span class="text-xs font-bold text-white">NI</span>
                        </div>
                        <div>
                            <span class="block font-bold text-white">{{ $company->company_name }}</span>
                            <span class="block text-[10px] font-medium uppercase tracking-widest text-zinc-500">Construction Group</span>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-zinc-500">{{ $company->tagline ?: 'Building excellence in residential and commercial construction.' }}{{ $company->founded_year ? ' Since '.$company->founded_year.'.' : '' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 sm:gap-12">
                    <div>
                        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-zinc-500">Services</p>
                        <ul class="space-y-2 text-sm text-zinc-400">
                            <li><a href="#services" class="hover:text-white">Residential</a></li>
                            <li><a href="#services" class="hover:text-white">Commercial</a></li>
                            <li><a href="#services" class="hover:text-white">Renovations</a></li>
                            <li><a href="#services" class="hover:text-white">Design-Build</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-zinc-500">Company</p>
                        <ul class="space-y-2 text-sm text-zinc-400">
                            <li><a href="#about" class="hover:text-white">About Us</a></li>
                            <li><a href="#projects" class="hover:text-white">Projects</a></li>
                            <li><a href="#process" class="hover:text-white">Our Process</a></li>
                            <li><a href="#contact" class="hover:text-white">Careers</a></li>
                        </ul>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-zinc-500">Contact</p>
                        <ul class="space-y-2 text-sm text-zinc-400">
                            @if($company->phone)
                                <li><a href="tel:{{ preg_replace('/\D/', '', $company->phone) }}" class="hover:text-white">{{ $company->phone }}</a></li>
                            @endif
                            @if($company->phone_secondary)
                                <li><a href="tel:{{ preg_replace('/\D/', '', $company->phone_secondary) }}" class="hover:text-white">{{ $company->phone_secondary }}</a></li>
                            @endif
                            @if($company->email)
                                <li><a href="mailto:{{ $company->email }}" class="break-all hover:text-white">{{ $company->email }}</a></li>
                            @endif
                            @if($company->address)
                                <li class="text-zinc-500">{{ $company->address }}{{ $company->city ? ', '.$company->city : '' }}{{ $company->state ? ' '.$company->state : '' }} {{ $company->zip }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
                <p class="text-sm text-zinc-600">© {{ date('Y') }} {{ $company->company_name }}. All rights reserved.</p>
                <div class="flex gap-6 text-sm text-zinc-600">
                    <a href="#" class="hover:text-zinc-400">Privacy Policy</a>
                    <a href="#" class="hover:text-zinc-400">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
