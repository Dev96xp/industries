<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component {
    public string $activeTab = 'users';

    // User editing
    public ?int $editingUserId = null;
    public string $selectedRole = '';

    /** Permissions exposed for editing in the UI (others are preserved but not shown) */
    private array $editablePermissions = ['manage company settings', 'manage photos', 'manage projects', 'manage quotes', 'manage time entries'];

    // Role editing
    public ?int $editingRoleId = null;
    /** @var array<string> */
    public array $selectedPermissions = [];

    public function editUser(User $user): void
    {
        $this->editingUserId = $user->id;
        $this->selectedRole  = $user->roles->first()?->name ?? '';
    }

    public function saveRole(): void
    {
        $user = User::findOrFail($this->editingUserId);

        if ($this->selectedRole) {
            $user->syncRoles([$this->selectedRole]);
        } else {
            $user->syncRoles([]);
        }

        $this->editingUserId = null;
        $this->selectedRole  = '';

        session()->flash('success', 'Role updated successfully.');
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->selectedRole  = '';
    }

    public function editRole(Role $role): void
    {
        $this->editingRoleId      = $role->id;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function saveRolePermissions(): void
    {
        $role = Role::findOrFail($this->editingRoleId);

        // Preserve permissions not shown in the UI (e.g. manage users)
        $preserved = $role->permissions
            ->pluck('name')
            ->filter(fn (string $p) => ! in_array($p, $this->editablePermissions))
            ->values()
            ->toArray();

        $role->syncPermissions(array_merge($preserved, $this->selectedPermissions));

        $this->editingRoleId       = null;
        $this->selectedPermissions = [];

        session()->flash('success', 'Permissions updated for role "' . $role->name . '".');
    }

    public function cancelRoleEdit(): void
    {
        $this->editingRoleId       = null;
        $this->selectedPermissions = [];
    }

    public function with(): array
    {
        return [
            'users'       => User::with('roles')->whereHas('roles', fn ($q) => $q->where('name', '!=', 'client'))->orderBy('name')->get(),
            'clients'     => User::with('roles')->whereHas('roles', fn ($q) => $q->where('name', 'client'))->orderBy('name')->get(),
            'roles'       => Role::with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::whereIn('name', $this->editablePermissions)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">User Management</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage users and role permissions.</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:badge color="blue" size="lg">{{ $users->count() }} Staff</flux:badge>
            <flux:badge color="amber" size="lg">{{ $clients->count() }} Clients</flux:badge>
        </div>
    </div>

    {{-- Success message --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800 w-fit">
        <button
            wire:click="$set('activeTab', 'users')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeTab === 'users' ? 'bg-white shadow text-zinc-900 dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Staff
        </button>
        <button
            wire:click="$set('activeTab', 'clients')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeTab === 'clients' ? 'bg-white shadow text-zinc-900 dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Clients
        </button>
        <button
            wire:click="$set('activeTab', 'roles')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeTab === 'roles' ? 'bg-white shadow text-zinc-900 dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Roles & Permissions
        </button>
    </div>

    {{-- USERS TAB --}}
    @if($activeTab === 'users')
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Role</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($users as $user)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/30">
                                            {{ $user->initials() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</p>
                                            <p class="text-xs text-zinc-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($editingUserId === $user->id)
                                        <flux:select wire:model="selectedRole" class="w-40">
                                            <flux:select.option value="">— No role —</flux:select.option>
                                            @foreach($roles as $role)
                                                <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @else
                                        @php $role = $user->roles->first() @endphp
                                        @if($role)
                                            <flux:badge color="{{ match($role->name) { 'superadmin' => 'red', 'admin' => 'blue', 'editor' => 'green', 'client' => 'amber', default => 'zinc' } }}">
                                                {{ ucfirst($role->name) }}
                                            </flux:badge>
                                        @else
                                            <span class="text-xs text-zinc-400">No role</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($editingUserId === $user->id)
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button wire:click="saveRole" variant="filled" size="sm">Save</flux:button>
                                            <flux:button wire:click="cancelEdit" variant="ghost" size="sm">Cancel</flux:button>
                                        </div>
                                    @else
                                        <flux:button wire:click="editUser({{ $user->id }})" variant="ghost" size="sm" icon="pencil">
                                            Edit Role
                                        </flux:button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- CLIENTS TAB --}}
    @if($activeTab === 'clients')
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Registered</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($clients as $client)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-600 dark:bg-amber-900/30">
                                            {{ $client->initials() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $client->name }}</p>
                                            <p class="text-xs text-zinc-400">{{ $client->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-zinc-500 text-xs">
                                    {{ $client->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($editingUserId === $client->id)
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button wire:click="saveRole" variant="filled" size="sm">Save</flux:button>
                                            <flux:button wire:click="cancelEdit" variant="ghost" size="sm">Cancel</flux:button>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-end gap-2">
                                            @can('manage quotes')
                                                <flux:button href="{{ route('admin.quotes.create', ['client' => $client->id]) }}" variant="primary" size="sm" icon="document-text" wire:navigate>
                                                    Create Quote
                                                </flux:button>
                                            @endcan
                                            <flux:button wire:click="editUser({{ $client->id }})" variant="ghost" size="sm" icon="pencil">
                                                Change Role
                                            </flux:button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @if($editingUserId === $client->id)
                                <tr class="bg-zinc-50 dark:bg-zinc-800/30">
                                    <td colspan="3" class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm text-zinc-500">Assign role:</span>
                                            <flux:select wire:model="selectedRole" class="w-40">
                                                <flux:select.option value="">— No role —</flux:select.option>
                                                @foreach($roles as $role)
                                                    <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-sm text-zinc-400">
                                    No clients have registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ROLES TAB --}}
    @if($activeTab === 'roles')
        <div class="flex flex-col gap-4">
            @foreach($roles as $role)
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <flux:badge color="{{ match($role->name) { 'superadmin' => 'red', 'admin' => 'blue', 'editor' => 'green', 'client' => 'amber', default => 'zinc' } }}" size="lg">
                                    {{ ucfirst($role->name) }}
                                </flux:badge>
                                <span class="text-xs text-zinc-400">{{ $role->permissions->count() }} permission(s)</span>
                            </div>

                            @if($editingRoleId === $role->id)
                                {{-- Permission checkboxes --}}
                                <div class="flex flex-col gap-2 mt-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <flux:checkbox
                                                wire:model="selectedPermissions"
                                                value="{{ $permission->name }}"
                                            />
                                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ ucfirst($permission->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex gap-2 mt-4">
                                    <flux:button wire:click="saveRolePermissions" variant="filled" size="sm">Save Permissions</flux:button>
                                    <flux:button wire:click="cancelRoleEdit" variant="ghost" size="sm">Cancel</flux:button>
                                </div>
                            @else
                                {{-- Current permissions list --}}
                                <div class="flex flex-wrap gap-2">
                                    @forelse($role->permissions as $permission)
                                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ ucfirst($permission->name) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-zinc-400">No permissions assigned.</span>
                                    @endforelse
                                </div>
                            @endif
                        </div>

                        @if($editingRoleId !== $role->id)
                            <flux:button wire:click="editRole({{ $role->id }})" variant="ghost" size="sm" icon="pencil">
                                Edit
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
