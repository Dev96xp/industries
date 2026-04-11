<?php

namespace App\Providers;

use App\Listeners\CacheNewRegistration;
use App\Models\CompanySetting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, CacheNewRegistration::class);

        $this->overrideLivewirePayloadLimit();
    }

    private function overrideLivewirePayloadLimit(): void
    {
        try {
            $mb = CompanySetting::current()->livewire_max_payload_mb ?? 8;
            config(['livewire.payload.max_size' => $mb * 1024 * 1024]);
        } catch (\Throwable) {
            // DB not ready (e.g. during migrations) — use config default
        }
    }
}
