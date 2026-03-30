<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.client')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name  = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password'         => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">

    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl" level="1">My Account</flux:heading>
        <flux:subheading size="lg" class="mt-1">Manage your profile and password</flux:subheading>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    {{-- Profile Information --}}
    <div class="mb-10">
        <flux:heading>Profile</flux:heading>
        <flux:subheading>Update your name and email address</flux:subheading>

        <form wire:submit="updateProfileInformation" class="mt-6 w-full space-y-6">
            <flux:input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" />
            <flux:input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required autocomplete="email" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <flux:separator variant="subtle" class="mb-10" />

    {{-- Update Password --}}
    <div>
        <flux:heading>Update Password</flux:heading>
        <flux:subheading>Use a long, random password to keep your account secure</flux:subheading>

        <form wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input wire:model="current_password" label="{{ __('Current Password') }}" type="password" name="current_password" required autocomplete="current-password" />
            <flux:input wire:model="password" label="{{ __('New Password') }}" type="password" name="password" required autocomplete="new-password" />
            <flux:input wire:model="password_confirmation" label="{{ __('Confirm Password') }}" type="password" name="password_confirmation" required autocomplete="new-password" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    {{-- Back to site --}}
    <div class="mt-10 text-center">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 transition hover:text-zinc-600">
            ← Back to website
        </a>
    </div>

</div>
