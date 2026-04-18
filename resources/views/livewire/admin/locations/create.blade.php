<?php

use App\Models\Location;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name     = '';
    public string $address  = '';
    public string $city     = '';
    public string $state    = '';
    public string $zip      = '';
    public string $phone    = '';
    public string $email    = '';
    public bool   $isActive = true;

    public function save(): void
    {
        $this->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:locations,name'],
            'address' => ['nullable', 'string', 'max:255'],
            'city'    => ['nullable', 'string', 'max:100'],
            'state'   => ['nullable', 'string', 'max:100'],
            'zip'     => ['nullable', 'string', 'max:20'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
        ]);

        Location::create([
            'name'      => $this->name,
            'address'   => $this->address ?: null,
            'city'      => $this->city ?: null,
            'state'     => $this->state ?: null,
            'zip'       => $this->zip ?: null,
            'phone'     => $this->phone ?: null,
            'email'     => $this->email ?: null,
            'is_active' => $this->isActive,
        ]);

        session()->flash('success', "Location \"{$this->name}\" created.");
        $this->redirectRoute('admin.locations', navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6 max-w-2xl">

    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.locations') }}" icon="arrow-left" size="sm" variant="ghost" wire:navigate />
        <div>
            <flux:heading size="xl">New Location</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Add a new office location.</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        <flux:field>
            <flux:label>Name <flux:badge size="sm" color="red" class="ml-1">Required</flux:badge></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Lincoln Office" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Address</flux:label>
            <flux:input wire:model="address" placeholder="Street address" />
            <flux:error name="address" />
        </flux:field>

        <div class="grid grid-cols-3 gap-4">
            <flux:field class="col-span-1">
                <flux:label>City</flux:label>
                <flux:input wire:model="city" placeholder="City" />
                <flux:error name="city" />
            </flux:field>
            <flux:field>
                <flux:label>State</flux:label>
                <flux:input wire:model="state" placeholder="NE" />
                <flux:error name="state" />
            </flux:field>
            <flux:field>
                <flux:label>ZIP</flux:label>
                <flux:input wire:model="zip" placeholder="68501" />
                <flux:error name="zip" />
            </flux:field>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input wire:model="phone" placeholder="402-555-0100" />
                <flux:error name="phone" />
            </flux:field>
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" placeholder="office@example.com" />
                <flux:error name="email" />
            </flux:field>
        </div>

        <flux:field>
            <flux:checkbox wire:model="isActive" label="Active" />
        </flux:field>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Save Location</flux:button>
            <flux:button href="{{ route('admin.locations') }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
