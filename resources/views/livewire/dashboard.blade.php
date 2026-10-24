<?php

use App\Models\Contractor;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuotePayment;
use App\Models\QuoteRequest;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $user = auth()->user();

        $can = [
            'clients'     => $user->can('manage clients'),
            'quotes'      => $user->can('manage quotes'),
            'projects'    => $user->can('manage projects'),
            'contractors' => $user->can('manage contractors'),
            'messages'    => $user->can('manage quote requests'),
            'time'        => $user->can('manage time entries'),
        ];

        $hour     = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $invoices = $can['quotes'] ? Quote::invoices()->with(['items', 'payments'])->get() : collect();
        $unpaidInvoices = $invoices->filter(fn (Quote $invoice) => $invoice->balance_due > 0);
        $overdueInvoices = $unpaidInvoices
            ->filter(fn (Quote $invoice) => $invoice->invoice_due_date?->copy()->endOfDay()->isPast())
            ->sortBy('invoice_due_date');

        $awaitingQuotes = $can['quotes']
            ? Quote::notInvoiced()->where('status', 'sent')->latest('updated_at')->get()
            : collect();

        $newMessages = $can['messages']
            ? QuoteRequest::where('status', 'new')->latest()->get()
            : collect();

        $monthly = $can['quotes'] ? $this->collectedByMonth() : collect();
        $collectedThisMonth = (float) ($monthly->last()['total'] ?? 0);
        $collectedLastMonth = (float) ($monthly->slice(-2, 1)->first()['total'] ?? 0);

        $activeProjects = $can['projects']
            ? Project::whereIn('status', ['planning', 'in_progress'])->count()
            : 0;

        $kpis = collect([
            $can['quotes'] ? [
                'label' => 'Outstanding',
                'value' => '$'.number_format($unpaidInvoices->sum(fn (Quote $invoice) => $invoice->balance_due), 0),
                'hint'  => $unpaidInvoices->count().' unpaid '.str('invoice')->plural($unpaidInvoices->count()),
                'alert' => $overdueInvoices->isNotEmpty() ? $overdueInvoices->count().' overdue' : null,
                'url'   => route('admin.quotes', ['tab' => 'invoices']),
            ] : null,
            $can['quotes'] ? [
                'label' => 'Collected',
                'value' => '$'.number_format($collectedThisMonth, 0),
                'hint'  => $collectedLastMonth > 0
                    ? (($collectedThisMonth >= $collectedLastMonth ? '▲ ' : '▼ ').abs((int) round(($collectedThisMonth - $collectedLastMonth) / $collectedLastMonth * 100)).'% vs last month')
                    : 'This month',
                'chart' => $monthly,
                'url'   => route('admin.quotes', ['tab' => 'invoices']),
            ] : null,
            $can['projects'] ? [
                'label' => 'Active Projects',
                'value' => $activeProjects,
                'hint'  => 'Planning & in progress',
                'url'   => route('admin.projects'),
            ] : null,
            $can['messages'] ? [
                'label' => 'New Messages',
                'value' => $newMessages->count(),
                'hint'  => $newMessages->isEmpty() ? 'Inbox zero' : 'Waiting for a reply',
                'url'   => route('admin.quote-requests'),
            ] : null,
        ])->filter()->values();

        return [
            'greeting'        => $greeting,
            'firstName'       => str($user->name)->before(' ')->toString(),
            'can'             => $can,
            'kpis'            => $kpis,
            'modules'         => $this->modules($can, $invoices, $overdueInvoices, $awaitingQuotes, $newMessages, $activeProjects),
            'overdueInvoices' => $overdueInvoices->take(5),
            'awaitingQuotes'  => $awaitingQuotes->take(5),
            'newMessages'     => $newMessages->take(3),
            'activity'        => $this->recentActivity($can),
        ];
    }

    /**
     * @return Collection<int, array{label: string, total: float}>
     */
    private function collectedByMonth(): Collection
    {
        $start = now()->startOfMonth()->subMonths(5);

        $payments = QuotePayment::where('status', 'completed')
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (QuotePayment $payment) => $payment->paid_at->format('Y-m'));

        return collect(range(5, 0))->map(function (int $monthsAgo) use ($payments) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'total' => (float) ($payments->get($month->format('Y-m'))?->sum('amount') ?? 0),
            ];
        });
    }

    /**
     * @param  array<string, bool>  $can
     * @return Collection<int, array{name: string, icon: string, gradient: string, url: string, stat: string, detail: string, alert: string|null}>
     */
    private function modules(array $can, Collection $invoices, Collection $overdueInvoices, Collection $awaitingQuotes, Collection $newMessages, int $activeProjects): Collection
    {
        $modules = collect();

        if ($can['clients']) {
            $modules->push([
                'name' => 'Clients', 'icon' => 'user-group', 'gradient' => 'from-amber-400 to-orange-500',
                'url' => route('admin.clients'),
                'stat' => User::whereHas('roles', fn ($q) => $q->where('name', 'client'))->count().' clients',
                'detail' => User::whereHas('roles', fn ($q) => $q->where('name', 'client'))->where('created_at', '>=', now()->startOfMonth())->count().' new this month',
                'alert' => null,
            ]);
        }

        if ($can['quotes']) {
            $modules->push([
                'name' => 'Quotes', 'icon' => 'document-text', 'gradient' => 'from-sky-400 to-blue-600',
                'url' => route('admin.quotes'),
                'stat' => Quote::notInvoiced()->whereIn('status', ['draft', 'sent'])->count().' open',
                'detail' => $awaitingQuotes->count().' awaiting approval',
                'alert' => null,
            ]);
            $modules->push([
                'name' => 'Invoices', 'icon' => 'document-currency-dollar', 'gradient' => 'from-violet-400 to-purple-600',
                'url' => route('admin.quotes', ['tab' => 'invoices']),
                'stat' => $invoices->count().' invoices',
                'detail' => $invoices->where('payment_status', 'paid')->count().' paid',
                'alert' => $overdueInvoices->isNotEmpty() ? $overdueInvoices->count().' overdue' : null,
            ]);
        }

        if ($can['projects']) {
            $modules->push([
                'name' => 'Projects', 'icon' => 'folder', 'gradient' => 'from-emerald-400 to-teal-600',
                'url' => route('admin.projects'),
                'stat' => $activeProjects.' active',
                'detail' => Project::count().' total',
                'alert' => null,
            ]);
            $modules->push([
                'name' => 'Calendar', 'icon' => 'calendar-days', 'gradient' => 'from-rose-400 to-pink-600',
                'url' => route('admin.projects.calendar'),
                'stat' => Project::whereBetween('start_date', [now()->startOfDay(), now()->addDays(30)])->count().' starting soon',
                'detail' => 'Next 30 days',
                'alert' => null,
            ]);
        }

        if ($can['messages']) {
            $modules->push([
                'name' => 'Messages', 'icon' => 'inbox', 'gradient' => 'from-indigo-400 to-blue-700',
                'url' => route('admin.quote-requests'),
                'stat' => $newMessages->count().' new',
                'detail' => QuoteRequest::count().' total',
                'alert' => $newMessages->isNotEmpty() ? $newMessages->count().' unread' : null,
            ]);
        }

        if ($can['contractors']) {
            $modules->push([
                'name' => 'Contractors', 'icon' => 'building-office-2', 'gradient' => 'from-slate-500 to-zinc-700',
                'url' => route('admin.contractors'),
                'stat' => Contractor::where('is_active', true)->count().' active',
                'detail' => 'Partner companies',
                'alert' => null,
            ]);
        }

        if ($can['time']) {
            $modules->push([
                'name' => 'Time Entries', 'icon' => 'clock', 'gradient' => 'from-cyan-400 to-sky-600',
                'url' => route('admin.time-entries'),
                'stat' => TimeEntry::whereNull('clock_out_at')->count().' clocked in',
                'detail' => 'Right now',
                'alert' => null,
            ]);
        }

        if ($can['quotes']) {
            $modules->push([
                'name' => 'Products', 'icon' => 'cube', 'gradient' => 'from-lime-400 to-green-600',
                'url' => route('admin.products'),
                'stat' => Product::where('is_active', true)->count().' active',
                'detail' => 'Catalog',
                'alert' => null,
            ]);
        }

        if (auth()->user()->hasRole('worker')) {
            $modules->push([
                'name' => 'Time Clock', 'icon' => 'finger-print', 'gradient' => 'from-cyan-400 to-sky-600',
                'url' => route('worker.timeclock'),
                'stat' => 'Clock in / out',
                'detail' => 'Your shifts',
                'alert' => null,
            ]);
        }

        return $modules;
    }

    /**
     * @param  array<string, bool>  $can
     * @return Collection<int, array{icon: string, text: string, sub: string, time: \Illuminate\Support\Carbon, url: string|null}>
     */
    private function recentActivity(array $can): Collection
    {
        $events = collect();

        if ($can['quotes']) {
            QuotePayment::with('quote')->where('status', 'completed')->latest('updated_at')->take(5)->get()
                ->each(fn (QuotePayment $payment) => $events->push([
                    'icon' => 'banknotes',
                    'text' => 'Payment of $'.number_format((float) $payment->amount, 2),
                    'sub'  => $payment->quote?->client_name ?? '—',
                    'time' => $payment->updated_at,
                    'url'  => $payment->quote ? route('admin.quotes.edit', $payment->quote) : null,
                ]));

            Quote::latest()->take(5)->get()
                ->each(fn (Quote $quote) => $events->push([
                    'icon' => 'document-plus',
                    'text' => 'Quote '.$quote->number.' created',
                    'sub'  => $quote->title ?: $quote->client_name,
                    'time' => $quote->created_at,
                    'url'  => route('admin.quotes.edit', $quote),
                ]));
        }

        if ($can['clients']) {
            User::whereHas('roles', fn ($q) => $q->where('name', 'client'))->latest()->take(5)->get()
                ->each(fn (User $client) => $events->push([
                    'icon' => 'user-plus',
                    'text' => 'New client',
                    'sub'  => $client->name,
                    'time' => $client->created_at,
                    'url'  => route('admin.clients'),
                ]));
        }

        if ($can['messages']) {
            QuoteRequest::latest()->take(5)->get()
                ->each(fn (QuoteRequest $message) => $events->push([
                    'icon' => 'envelope',
                    'text' => 'Message from '.$message->name,
                    'sub'  => $message->project_type ?: 'Website form',
                    'time' => $message->created_at,
                    'url'  => route('admin.quote-requests'),
                ]));
        }

        return $events->filter(fn (array $event) => $event['time'] !== null)->sortByDesc('time')->take(7)->values();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-8 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-400">{{ now()->format('l, F j') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                {{ $greeting }}, {{ $firstName }} 👋
            </h1>
            <p class="mt-1 text-sm text-zinc-500">Here's what's happening with your business today.</p>
        </div>
        @if($can['quotes'])
            <flux:button href="{{ route('admin.quotes.create') }}" variant="primary" icon="plus" wire:navigate>New Quote</flux:button>
        @endif
    </div>

    {{-- KPIs --}}
    @if($kpis->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 {{ match ($kpis->count()) { 1 => 'xl:grid-cols-1', 2 => 'xl:grid-cols-2', 3 => 'xl:grid-cols-3', default => 'xl:grid-cols-4' } }}">
            @foreach($kpis as $kpi)
                <a href="{{ $kpi['url'] }}" wire:navigate wire:key="kpi-{{ $loop->index }}"
                    class="group relative flex flex-col justify-between gap-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-lg hover:shadow-zinc-200/50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:shadow-black/20">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wider text-zinc-400">{{ $kpi['label'] }}</p>
                            <p class="mt-1.5 text-3xl font-semibold tracking-tight tabular-nums text-zinc-900 dark:text-white">{{ $kpi['value'] }}</p>
                        </div>
                        @if(! empty($kpi['alert']))
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600 ring-1 ring-inset ring-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400">
                                <flux:icon.exclamation-triangle variant="micro" class="size-3" /> {{ $kpi['alert'] }}
                            </span>
                        @endif
                    </div>

                    @if(isset($kpi['chart']))
                        @php $max = max($kpi['chart']->max('total'), 1); @endphp
                        <div class="flex h-10 items-end gap-0.5" role="img" aria-label="Collected per month, last 6 months">
                            @foreach($kpi['chart'] as $month)
                                <div class="group/bar relative flex h-full flex-1 items-end" wire:key="bar-{{ $loop->index }}">
                                    <div class="w-full rounded-t-[4px] transition-colors {{ $loop->last ? 'bg-zinc-900 dark:bg-white' : 'bg-zinc-200 group-hover/bar:bg-zinc-400 dark:bg-zinc-700 dark:group-hover/bar:bg-zinc-500' }}"
                                        style="height: {{ max($month['total'] / $max * 100, 6) }}%"></div>
                                    <div class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/bar:opacity-100 dark:bg-white dark:text-zinc-900">
                                        {{ $month['label'] }} · ${{ number_format($month['total'], 0) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="text-xs text-zinc-500">{{ $kpi['hint'] }}</p>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Modules --}}
    <section>
        <h2 class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Modules</h2>
        @if($modules->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 py-14 text-center dark:border-zinc-700">
                <flux:icon.squares-2x2 class="size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-3 text-sm text-zinc-400">You don't have access to any modules yet.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @foreach($modules as $module)
                    <a href="{{ $module['url'] }}" wire:navigate wire:key="module-{{ $module['name'] }}"
                        class="group relative flex items-center gap-4 rounded-2xl border border-zinc-200/80 bg-white p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-lg hover:shadow-zinc-200/50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:shadow-black/20">
                        <div class="relative flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br {{ $module['gradient'] }} text-white shadow-sm transition-transform duration-300 group-hover:scale-105">
                            <flux:icon :icon="$module['icon']" class="size-5" />
                            @if($module['alert'])
                                <span class="absolute -right-1 -top-1 flex size-3">
                                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-rose-500 opacity-60"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-rose-500 ring-2 ring-white dark:ring-zinc-900"></span>
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $module['name'] }}</p>
                            <p class="truncate text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ $module['stat'] }}</p>
                            <p class="truncate text-xs {{ $module['alert'] ? 'font-medium text-rose-600 dark:text-rose-400' : 'text-zinc-400' }}">
                                {{ $module['alert'] ?? $module['detail'] }}
                            </p>
                        </div>
                        <flux:icon.arrow-up-right variant="micro" class="size-4 shrink-0 -translate-x-1 text-zinc-400 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Needs attention & Recent activity --}}
    @if($can['quotes'] || $can['messages'] || $activity->isNotEmpty())
        <div class="grid gap-4 lg:grid-cols-5">

            <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 lg:col-span-3 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Needs attention</h2>
                    @php $attentionCount = $overdueInvoices->count() + $awaitingQuotes->count() + $newMessages->count(); @endphp
                    @if($attentionCount > 0)
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $attentionCount }}</span>
                    @endif
                </div>

                @if($overdueInvoices->isEmpty() && $awaitingQuotes->isEmpty() && $newMessages->isEmpty())
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-500/10">
                            <flux:icon.check variant="mini" class="size-6 text-emerald-500" />
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">All caught up</p>
                        <p class="text-xs text-zinc-400">No overdue invoices, pending approvals or new messages.</p>
                    </div>
                @else
                    <ul class="-mx-2 flex flex-col">
                        @foreach($overdueInvoices as $invoice)
                            <li wire:key="att-inv-{{ $invoice->id }}">
                                <a href="{{ route('admin.quotes.edit', $invoice) }}" wire:navigate class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-500/10"><flux:icon.exclamation-triangle variant="micro" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $invoice->title ?: $invoice->invoice_number }} <span class="font-normal text-zinc-400">· {{ $invoice->client_name }}</span></p>
                                        <p class="text-xs text-rose-600 dark:text-rose-400">Overdue · due {{ $invoice->invoice_due_date->format('M j') }}</p>
                                    </div>
                                    <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-white">${{ number_format($invoice->balance_due, 2) }}</span>
                                    <flux:icon.chevron-right variant="micro" class="text-zinc-300 transition-transform group-hover:translate-x-0.5 dark:text-zinc-600" />
                                </a>
                            </li>
                        @endforeach
                        @foreach($awaitingQuotes as $quote)
                            <li wire:key="att-quote-{{ $quote->id }}">
                                <a href="{{ route('admin.quotes.edit', $quote) }}" wire:navigate class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-500 dark:bg-amber-500/10"><flux:icon.clock variant="micro" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $quote->title ?: $quote->number }} <span class="font-normal text-zinc-400">· {{ $quote->client_name }}</span></p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">Awaiting approval · sent {{ $quote->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="font-mono text-xs text-zinc-400">{{ $quote->number }}</span>
                                    <flux:icon.chevron-right variant="micro" class="text-zinc-300 transition-transform group-hover:translate-x-0.5 dark:text-zinc-600" />
                                </a>
                            </li>
                        @endforeach
                        @foreach($newMessages as $message)
                            <li wire:key="att-msg-{{ $message->id }}">
                                <a href="{{ route('admin.quote-requests') }}" wire:navigate class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-500/10"><flux:icon.envelope variant="micro" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $message->name }} <span class="font-normal text-zinc-400">· {{ $message->project_type ?: 'Website form' }}</span></p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">New message · {{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                    <flux:icon.chevron-right variant="micro" class="text-zinc-300 transition-transform group-hover:translate-x-0.5 dark:text-zinc-600" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 lg:col-span-2 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold text-zinc-900 dark:text-white">Recent activity</h2>
                @if($activity->isEmpty())
                    <p class="py-10 text-center text-xs text-zinc-400">No activity yet.</p>
                @else
                    <ol class="relative flex flex-col">
                        @foreach($activity as $event)
                            <li class="relative flex gap-3 pb-4 last:pb-0" wire:key="act-{{ $loop->index }}">
                                @unless($loop->last)
                                    <span class="absolute left-[15px] top-8 bottom-0 w-px bg-zinc-100 dark:bg-zinc-800"></span>
                                @endunless
                                <span class="relative flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 ring-4 ring-white dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-900">
                                    <flux:icon :icon="$event['icon']" variant="micro" />
                                </span>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    @if($event['url'])
                                        <a href="{{ $event['url'] }}" wire:navigate class="block truncate text-sm font-medium text-zinc-800 hover:underline dark:text-zinc-100">{{ $event['text'] }}</a>
                                    @else
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $event['text'] }}</p>
                                    @endif
                                    <p class="truncate text-xs text-zinc-400">{{ $event['sub'] }} · {{ $event['time']->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

        </div>
    @endif

</div>
