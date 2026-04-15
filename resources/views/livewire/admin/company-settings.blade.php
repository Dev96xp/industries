<?php

use App\Models\CompanySetting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $company_name    = '';
    public string $tagline         = '';
    public string $address         = '';
    public string $city            = '';
    public string $state           = '';
    public string $zip             = '';
    public string $phone           = '';
    public string $phone_secondary = '';
    public string $email           = '';
    public string $license_number  = '';
    public string $founded_year    = '';
    public string $facebook        = '';
    public string $instagram       = '';
    public string $linkedin        = '';

    public function mount(): void
    {
        $settings = CompanySetting::current();

        $this->company_name    = $settings->company_name ?? '';
        $this->tagline         = $settings->tagline ?? '';
        $this->address         = $settings->address ?? '';
        $this->city            = $settings->city ?? '';
        $this->state           = $settings->state ?? '';
        $this->zip             = $settings->zip ?? '';
        $this->phone           = $settings->phone ?? '';
        $this->phone_secondary = $settings->phone_secondary ?? '';
        $this->email           = $settings->email ?? '';
        $this->license_number  = $settings->license_number ?? '';
        $this->founded_year    = $settings->founded_year ?? '';
        $this->facebook        = $settings->facebook ?? '';
        $this->instagram       = $settings->instagram ?? '';
        $this->linkedin        = $settings->linkedin ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'company_name'    => 'required|string|max:255',
            'tagline'         => 'nullable|string|max:255',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'zip'             => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'phone_secondary' => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:255',
            'license_number'  => 'nullable|string|max:100',
            'founded_year'    => 'nullable|string|max:4',
            'facebook'        => 'nullable|string|max:255',
            'instagram'       => 'nullable|string|max:255',
            'linkedin'        => 'nullable|string|max:255',
        ]);

        $settings = CompanySetting::current();

        $addressChanged = $settings->address !== ($this->address ?: null)
            || $settings->city !== ($this->city ?: null)
            || $settings->state !== ($this->state ?: null)
            || $settings->zip !== ($this->zip ?: null);

        $settings->update([
            'company_name'    => $this->company_name,
            'tagline'         => $this->tagline ?: null,
            'address'         => $this->address ?: null,
            'city'            => $this->city ?: null,
            'state'           => $this->state ?: null,
            'zip'             => $this->zip ?: null,
            'phone'           => $this->phone ?: null,
            'phone_secondary' => $this->phone_secondary ?: null,
            'email'           => $this->email ?: null,
            'license_number'  => $this->license_number ?: null,
            'founded_year'    => $this->founded_year ?: null,
            'facebook'        => $this->facebook ?: null,
            'instagram'       => $this->instagram ?: null,
            'linkedin'        => $this->linkedin ?: null,
        ]);

        if ($addressChanged) {
            $parts = array_filter([$this->address, $this->city, $this->state, $this->zip]);

            if (! empty($parts)) {
                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'User-Agent' => config('app.name') . ' geo/1.0',
                    ])->get('https://nominatim.openstreetmap.org/search', [
                        'q'      => implode(', ', $parts),
                        'format' => 'json',
                        'limit'  => 1,
                    ]);

                    $results = $response->json();

                    if (! empty($results[0])) {
                        $settings->update([
                            'latitude'  => $results[0]['lat'],
                            'longitude' => $results[0]['lon'],
                        ]);
                    }
                } catch (\Throwable) {
                    // Geocoding failed silently
                }
            }
        }

        session()->flash('success', 'Company information saved successfully.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Company Information</flux:heading>
            <flux:text class="mt-1 text-zinc-500">This information is displayed on the public website.</flux:text>
        </div>
    </div>

    {{-- Success message --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="flex flex-col gap-6">

        {{-- General --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-5">General</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Company Name <flux:badge size="sm" color="red" class="ml-1">Required</flux:badge></flux:label>
                    <flux:input wire:model="company_name" placeholder="Nucleus Industries" />
                    <flux:error name="company_name" />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:label>Tagline</flux:label>
                    <flux:input wire:model="tagline" placeholder="Building Your Vision, Brick by Brick." />
                    <flux:error name="tagline" />
                </flux:field>
                <flux:field>
                    <flux:label>Founded Year</flux:label>
                    <flux:input wire:model="founded_year" placeholder="2010" maxlength="4" />
                    <flux:error name="founded_year" />
                </flux:field>
                <flux:field>
                    <flux:label>License Number</flux:label>
                    <flux:input wire:model="license_number" placeholder="LIC-123456" />
                    <flux:error name="license_number" />
                </flux:field>
            </div>
        </div>

        {{-- Contact --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-5">Contact</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Primary Phone</flux:label>
                    <flux:input wire:model="phone" type="tel" placeholder="(555) 000-0000" />
                    <flux:error name="phone" />
                </flux:field>
                <flux:field>
                    <flux:label>Secondary Phone</flux:label>
                    <flux:input wire:model="phone_secondary" type="tel" placeholder="(555) 000-0001" />
                    <flux:error name="phone_secondary" />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="info@nucleusindustries.com" />
                    <flux:error name="email" />
                </flux:field>
            </div>
        </div>

        {{-- Address --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-5">Address</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Street Address</flux:label>
                    <flux:input wire:model="address" placeholder="123 Main St" />
                    <flux:error name="address" />
                </flux:field>
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="city" placeholder="Miami" />
                    <flux:error name="city" />
                </flux:field>
                <flux:field>
                    <flux:label>State</flux:label>
                    <flux:input wire:model="state" placeholder="FL" />
                    <flux:error name="state" />
                </flux:field>
                <flux:field>
                    <flux:label>ZIP Code</flux:label>
                    <flux:input wire:model="zip" placeholder="33101" />
                    <flux:error name="zip" />
                </flux:field>
            </div>
        </div>

        {{-- Social Media --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-5">Social Media</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Facebook</flux:label>
                    <flux:input wire:model="facebook" placeholder="https://facebook.com/nucleusindustries" />
                    <flux:error name="facebook" />
                </flux:field>
                <flux:field>
                    <flux:label>Instagram</flux:label>
                    <flux:input wire:model="instagram" placeholder="https://instagram.com/nucleusindustries" />
                    <flux:error name="instagram" />
                </flux:field>
                <flux:field>
                    <flux:label>LinkedIn</flux:label>
                    <flux:input wire:model="linkedin" placeholder="https://linkedin.com/company/nucleusindustries" />
                    <flux:error name="linkedin" />
                </flux:field>
            </div>
        </div>

        {{-- Save --}}
        <div class="flex justify-end">
            <flux:button type="submit" variant="filled" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>

    </form>

</div>
