<?php

use App\Models\Project;
use App\Models\TimeEntry;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int    $project_id  = null;
    public ?string $lat         = null;
    public ?string $lng         = null;
    public string  $notes       = '';
    public string  $geoError    = '';

    public function clockIn(): void
    {
        $this->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        // Check if already clocked in
        $active = TimeEntry::where('user_id', auth()->id())
            ->whereNull('clock_out_at')
            ->first();

        if ($active) {
            session()->flash('error', 'You are already clocked in. Please clock out first.');
            return;
        }

        $project  = Project::findOrFail($this->project_id);
        $lat      = $this->lat ? (float) $this->lat : null;
        $lng      = $this->lng ? (float) $this->lng : null;
        $verified = ($lat && $lng) ? TimeEntry::isWithinRadius($project, $lat, $lng) : false;

        TimeEntry::create([
            'user_id'           => auth()->id(),
            'project_id'        => $this->project_id,
            'clock_in_at'       => now(),
            'clock_in_lat'      => $lat,
            'clock_in_lng'      => $lng,
            'clock_in_verified' => $verified,
            'notes'             => $this->notes ?: null,
        ]);

        $this->notes = '';
        $this->lat   = null;
        $this->lng   = null;

        session()->flash('success', 'Clocked in successfully' . ($verified ? ' ✓' : ' (location not verified)') . '.');
    }

    public function clockOut(): void
    {
        $entry = TimeEntry::where('user_id', auth()->id())
            ->whereNull('clock_out_at')
            ->first();

        if (! $entry) {
            session()->flash('error', 'No active clock-in found.');
            return;
        }

        $project  = $entry->project;
        $lat      = $this->lat ? (float) $this->lat : null;
        $lng      = $this->lng ? (float) $this->lng : null;
        $verified = ($lat && $lng) ? TimeEntry::isWithinRadius($project, $lat, $lng) : false;

        $entry->update([
            'clock_out_at'       => now(),
            'clock_out_lat'      => $lat,
            'clock_out_lng'      => $lng,
            'clock_out_verified' => $verified,
        ]);

        $this->lat = null;
        $this->lng = null;

        session()->flash('success', 'Clocked out. Duration: ' . $entry->fresh()->formatted_duration . ($verified ? ' ✓' : ' (location not verified)') . '.');
    }

    public function with(): array
    {
        $activeEntry = TimeEntry::where('user_id', auth()->id())
            ->whereNull('clock_out_at')
            ->with('project')
            ->first();

        $recentEntries = TimeEntry::where('user_id', auth()->id())
            ->whereNotNull('clock_out_at')
            ->with('project')
            ->orderByDesc('clock_in_at')
            ->limit(5)
            ->get();

        $projects = Project::where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'name', 'number']);

        return compact('activeEntry', 'recentEntries', 'projects');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6 max-w-lg mx-auto"
    x-data="{
        locating: false,
        locationReady: false,
        geoError: '',
        getLocation() {
            this.locating = true; this.geoError = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    $wire.set('lat', pos.coords.latitude.toFixed(7));
                    $wire.set('lng', pos.coords.longitude.toFixed(7));
                    this.locating = false;
                    this.locationReady = true;
                },
                (err) => {
                    this.geoError = 'Could not get location. Make sure location is enabled.';
                    this.locating = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Time Clock</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ auth()->user()->name }}</flux:text>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle">{{ session('error') }}</flux:callout>
    @endif

    {{-- CLOCKED IN STATE --}}
    @if($activeEntry)
        <div class="rounded-2xl border-2 border-green-300 bg-green-50 p-6 dark:border-green-800 dark:bg-green-950/30">
            <div class="flex items-center gap-3 mb-4">
                <span class="relative flex size-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                </span>
                <p class="text-sm font-semibold text-green-700 dark:text-green-400">Currently Clocked In</p>
            </div>
            <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $activeEntry->project->name }}</p>
            @if($activeEntry->project->number)
                <p class="text-xs font-mono text-zinc-400">{{ $activeEntry->project->number }}</p>
            @endif
            <p class="mt-2 text-sm text-zinc-500">
                Since {{ $activeEntry->clock_in_at->format('g:i A') }}
                ({{ $activeEntry->clock_in_at->diffForHumans() }})
            </p>
            @if($activeEntry->clock_in_verified)
                <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ Location verified at clock-in</p>
            @else
                <p class="mt-1 text-xs text-amber-500">⚠ Location not verified at clock-in</p>
            @endif
        </div>

        {{-- Clock Out --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Clock Out</h3>

            {{-- GPS capture --}}
            <div class="mb-4">
                <button type="button" @click="getLocation()" :disabled="locating"
                    class="w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 py-3 text-sm font-medium text-zinc-600 transition hover:border-blue-400 hover:text-blue-600 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400">
                    <flux:icon.map-pin class="size-4" />
                    <span x-text="locating ? 'Getting your location...' : (locationReady ? '✓ Location captured — tap to refresh' : 'Capture My Location')"></span>
                </button>
                <p x-show="geoError" x-text="geoError" class="mt-1 text-xs text-red-500"></p>
            </div>

            <flux:button wire:click="clockOut" variant="danger" class="w-full"
                wire:confirm="Are you sure you want to clock out?">
                Clock Out
            </flux:button>
        </div>

    {{-- CLOCKED OUT STATE --}}
    @else
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Clock In</h3>

            <div class="flex flex-col gap-4">
                <flux:select wire:model="project_id" label="Select Project">
                    <flux:select.option value="">— Choose a project —</flux:select.option>
                    @foreach($projects as $project)
                        <flux:select.option value="{{ $project->id }}">
                            {{ $project->number ? $project->number . ' — ' : '' }}{{ $project->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('project_id')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- GPS capture --}}
                <div>
                    <button type="button" @click="getLocation()" :disabled="locating"
                        class="w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 py-3 text-sm font-medium text-zinc-600 transition hover:border-blue-400 hover:text-blue-600 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400">
                        <flux:icon.map-pin class="size-4" />
                        <span x-text="locating ? 'Getting your location...' : (locationReady ? '✓ Location captured — tap to refresh' : 'Capture My Location')"></span>
                    </button>
                    <p x-show="geoError" x-text="geoError" class="mt-1 text-xs text-red-500"></p>
                    <p class="mt-1 text-xs text-zinc-400">Tap the button above so we can verify you are at the worksite.</p>
                </div>

                <flux:textarea wire:model="notes" label="Notes (optional)" rows="2" placeholder="Any notes for this shift..." />

                <flux:button wire:click="clockIn" variant="primary" class="w-full">
                    Clock In
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Recent entries --}}
    @if($recentEntries->isNotEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Recent Shifts</h3>
            <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($recentEntries as $entry)
                    <div class="py-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $entry->project->name }}</p>
                            <p class="text-xs text-zinc-400">
                                {{ $entry->clock_in_at->format('M d') }} ·
                                {{ $entry->clock_in_at->format('g:i A') }} → {{ $entry->clock_out_at->format('g:i A') }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $entry->formatted_duration }}</p>
                            @if($entry->clock_in_verified && $entry->clock_out_verified)
                                <p class="text-xs text-green-500">✓ Verified</p>
                            @else
                                <p class="text-xs text-amber-500">⚠ Unverified</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
