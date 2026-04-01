<?php

use App\Models\Contractor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $companyName = '';
    public string $contactName = '';
    public string $phone = '';
    public string $phoneSecondary = '';
    public string $email = '';
    public string $address = '';
    public string $specialty = '';
    public string $insuranceCompany = '';
    public bool $hasConstructionLicense = false;
    public string $licenseNumber = '';
    public string $notes = '';
    public bool $isActive = true;

    public function save(): void
    {
        $this->validate([
            'companyName'    => ['required', 'string', 'max:255'],
            'contactName'    => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'phoneSecondary' => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'specialty'         => ['nullable', 'string', 'max:255'],
            'insuranceCompany'       => ['nullable', 'string', 'max:255'],
            'licenseNumber'          => ['nullable', 'string', 'max:100'],
            'notes'                  => ['nullable', 'string'],
        ]);

        Contractor::create([
            'company_name'    => $this->companyName,
            'contact_name'    => $this->contactName ?: null,
            'phone'           => $this->phone ?: null,
            'phone_secondary' => $this->phoneSecondary ?: null,
            'email'           => $this->email ?: null,
            'address'         => $this->address ?: null,
            'specialty'         => $this->specialty ?: null,
            'insurance_company'        => $this->insuranceCompany ?: null,
            'has_construction_license' => $this->hasConstructionLicense,
            'license_number'           => $this->licenseNumber ?: null,
            'notes'                    => $this->notes ?: null,
            'is_active'                => $this->isActive,
        ]);

        session()->flash('success', "Contractor \"{$this->companyName}\" created.");

        $this->redirectRoute('admin.contractors', navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('admin.contractors') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <div>
            <flux:heading size="xl">New Contractor</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Add an external company to your directory.</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">

        {{-- Company Info --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Company Information</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="companyName" label="Company Name *" placeholder="ABC Electrical Inc." />
                </div>
                <flux:input wire:model="contactName" label="Contact Person" placeholder="John Smith" />
                <flux:input wire:model="specialty" label="Specialty / Trade" placeholder="Electrical, Plumbing, HVAC..." />
                <flux:input wire:model="phone" label="Phone" placeholder="+1 (555) 000-0000" />
                <flux:input wire:model="phoneSecondary" label="Secondary Phone" placeholder="+1 (555) 000-0000" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="contact@company.com" />
                <flux:input wire:model="address" label="Address" placeholder="123 Main St, City, State" />
                <flux:input wire:model="insuranceCompany" label="Insurance Company" placeholder="State Farm, Allstate..." />
            </div>
        </div>

        {{-- Notes & Status --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Additional Info</flux:heading>

            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <flux:checkbox wire:model.live="hasConstructionLicense" label="Has valid construction license" />
                    @if($hasConstructionLicense)
                        <flux:input wire:model="licenseNumber" label="License Number" placeholder="e.g. CGC1234567" />
                    @endif
                </div>
                <flux:textarea wire:model="notes" label="Internal Notes" placeholder="Any notes about this contractor..." rows="3" />
                <flux:checkbox wire:model="isActive" label="Active contractor" />
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('admin.contractors') }}" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save Contractor</flux:button>
        </div>

    </form>

</div>
