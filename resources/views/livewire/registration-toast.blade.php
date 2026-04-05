<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public ?array $toast = null;
    public int $lastSeenId = 0;

    public function poll(): void
    {
        $latest = Cache::get('latest_registration');

        if ($latest && $latest['id'] > $this->lastSeenId) {
            $this->lastSeenId = $latest['id'];
            $this->toast      = $latest;
            $this->dispatch('show-registration-toast');
        }
    }
}; ?>

<div
    @if(auth()->check() && auth()->user()->hasAnyRole(['superadmin', 'admin']))
        wire:poll.5000ms="poll"
    @endif
    x-data="{ visible: false }"
    x-on:show-registration-toast.window="
        visible = true;
        setTimeout(() => visible = false, 3000);
    "
>
    @if(auth()->check() && auth()->user()->hasAnyRole(['superadmin', 'admin']))
        <div
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border border-white/10 px-5 py-4 shadow-2xl"
            style="background-color: rgba(30,58,95,0.97); min-width: 220px; max-width: 300px;"
        >
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-lg">
                👤
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-300">New Registration</p>
                @if($toast)
                    <p class="mt-0.5 text-sm font-bold text-white">
                        {{ $toast['last_name'] }}
                        @if($toast['city'])
                            <span class="font-normal text-zinc-400">· {{ $toast['city'] }}</span>
                        @endif
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>
