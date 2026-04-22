<?php

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public int    $livewireMaxPayloadMb  = 8;
    public bool   $ipRestrictionEnabled  = false;
    public string $allowedIps            = '';
    public string $currentIp             = '';

    // Backup settings
    public bool   $backupEnabled      = false;
    public string $backupFrequency    = 'weekly';
    public string $backupDay          = 'sunday';
    public int    $backupDayOfMonth   = 1;
    public string $backupTime         = '02:00';
    public string $backupEmail        = '';

    public function mount(): void
    {
        $settings = CompanySetting::current();
        $this->livewireMaxPayloadMb = $settings->livewire_max_payload_mb ?? 8;
        $this->ipRestrictionEnabled = (bool) ($settings->ip_restriction_enabled ?? false);
        $this->allowedIps           = $settings->allowed_ips ?? '';
        $this->currentIp            = request()->ip();
        $this->backupEnabled      = (bool) ($settings->backup_enabled ?? false);
        $this->backupFrequency    = $settings->backup_frequency ?? 'weekly';
        $this->backupDay          = $settings->backup_day ?? 'sunday';
        $this->backupDayOfMonth   = (int) ($settings->backup_day_of_month ?? 1);
        $this->backupTime         = $settings->backup_time ?? '02:00';
        $this->backupEmail        = $settings->backup_email ?? '';
    }

    public function saveBackupSettings(): void
    {
        $this->validate([
            'backupFrequency'  => ['required', 'in:weekly,monthly'],
            'backupDay'        => ['required_if:backupFrequency,weekly', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'backupDayOfMonth' => ['required_if:backupFrequency,monthly', 'integer', 'min:1', 'max:28'],
            'backupTime'       => ['required', 'date_format:H:i'],
            'backupEmail'      => ['nullable', 'email'],
        ]);

        CompanySetting::current()->update([
            'backup_enabled'      => $this->backupEnabled,
            'backup_frequency'    => $this->backupFrequency,
            'backup_day'          => $this->backupDay,
            'backup_day_of_month' => $this->backupDayOfMonth,
            'backup_time'         => $this->backupTime,
            'backup_email'        => $this->backupEmail ?: null,
        ]);

        session()->flash('backupSuccess', 'Backup settings saved.');
    }

    public function runManualBackup(): void
    {
        \Illuminate\Support\Facades\Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

        unset($this->storedBackups);

        $this->dispatch('backup-ready', url: route('admin.backup.download'));
    }

    #[Computed]
    public function storedBackups(): \Illuminate\Support\Collection
    {
        return collect(Storage::disk('local')->files('Laravel'))
            ->filter(fn (string $f) => str_ends_with($f, '.zip'))
            ->sortDesc()
            ->values()
            ->map(fn (string $f) => [
                'name' => basename($f),
                'size' => round(Storage::disk('local')->size($f) / 1024, 1),
                'date' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d-H-i-s', pathinfo(basename($f), PATHINFO_FILENAME))?->format('M d, Y  H:i'),
            ]);
    }

    public function save(): void
    {
        $this->validate([
            'livewireMaxPayloadMb' => ['required', 'integer', 'min:1', 'max:64'],
        ]);

        CompanySetting::current()->update([
            'livewire_max_payload_mb' => $this->livewireMaxPayloadMb,
        ]);

        session()->flash('success', 'System settings saved.');
    }

    public function saveIpSettings(): void
    {
        $this->validate([
            'allowedIps' => ['nullable', 'string'],
        ]);

        CompanySetting::current()->update([
            'ip_restriction_enabled' => $this->ipRestrictionEnabled,
            'allowed_ips' => $this->allowedIps,
        ]);

        session()->flash('ipSuccess', 'IP restriction settings saved.');
    }

    public function addCurrentIp(): void
    {
        $existing = collect(explode(',', $this->allowedIps))
            ->map(fn (string $ip) => trim($ip))
            ->filter()
            ->values();

        if (! $existing->contains($this->currentIp)) {
            $existing->push($this->currentIp);
            $this->allowedIps = $existing->implode(', ');
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">System Settings</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Advanced application configuration. Superadmin only.</flux:text>
        </div>
        <flux:badge color="red" size="lg">Superadmin</flux:badge>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if(session('ipSuccess'))
        <flux:callout variant="success" icon="check-circle">{{ session('ipSuccess') }}</flux:callout>
    @endif

    @if(session('backupSuccess'))
        <flux:callout variant="success" icon="check-circle">{{ session('backupSuccess') }}</flux:callout>
    @endif

    {{-- Livewire Settings --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="mb-4 text-sm font-semibold text-blue-700 dark:text-blue-400">Livewire</h3>

        <div class="max-w-sm space-y-4">
            <flux:field>
                <flux:label>Max Payload Size (MB)</flux:label>
                <flux:description>Maximum data size per Livewire request. Increase if you get payload errors when saving annotated photos. (1–64 MB)</flux:description>
                <flux:input wire:model="livewireMaxPayloadMb" type="number" min="1" max="64" />
                <flux:error name="livewireMaxPayloadMb" />
            </flux:field>

            <flux:button wire:click="save" variant="primary" icon="check">Save Settings</flux:button>
        </div>
    </div>

    {{-- Database Backup --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="mb-1 text-sm font-semibold text-red-700 dark:text-red-400">Database Backup</h3>
        <flux:text class="mb-5 text-sm text-zinc-500">Manual and scheduled database backups.</flux:text>

        {{-- Manual backup --}}
        <div x-data x-on:backup-ready.window="window.location.href = $event.detail.url"
            class="mb-6 flex items-center justify-between rounded-xl border border-zinc-100 dark:border-zinc-800 px-5 py-4">
            <div>
                <p class="text-sm font-medium text-yellow-500 dark:text-yellow-300">Manual Backup</p>
                <p class="text-xs text-zinc-400 mt-0.5">Generate and download a full database backup now.</p>
            </div>
            <flux:button wire:click="runManualBackup" wire:loading.attr="disabled" variant="filled" icon="arrow-down-tray">
                <span wire:loading.remove wire:target="runManualBackup">Download Backup</span>
                <span wire:loading wire:target="runManualBackup">Generating...</span>
            </flux:button>
        </div>

        {{-- Backup history --}}
        @if($this->storedBackups->isNotEmpty())
            <div class="mt-2 mb-4 overflow-hidden rounded-xl border border-zinc-100 dark:border-zinc-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-500">File</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-500">Date</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-zinc-500">Size</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->storedBackups as $backup)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-2 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $backup['name'] }}</td>
                                <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $backup['date'] }}</td>
                                <td class="px-4 py-2 text-right text-zinc-500">{{ $backup['size'] }} KB</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Scheduled backup --}}
        <div class="space-y-4">
            <p class="text-sm font-medium text-yellow-500 dark:text-yellow-300">Scheduled Backup</p>

            <flux:field>
                <flux:switch wire:model="backupEnabled" label="Enable automatic scheduled backup" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-4">
                <flux:field>
                    <flux:label>Frequency</flux:label>
                    <flux:select wire:model.live="backupFrequency">
                        <flux:select.option value="weekly">Weekly</flux:select.option>
                        <flux:select.option value="monthly">Monthly</flux:select.option>
                    </flux:select>
                </flux:field>

                @if($backupFrequency === 'weekly')
                    <flux:field>
                        <flux:label>Day of the week</flux:label>
                        <flux:select wire:model="backupDay">
                            @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                <flux:select.option value="{{ $day }}">{{ ucfirst($day) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @else
                    <flux:field>
                        <flux:label>Day of the month</flux:label>
                        <flux:select wire:model="backupDayOfMonth">
                            @for($d = 1; $d <= 28; $d++)
                                <flux:select.option value="{{ $d }}">{{ $d }}</flux:select.option>
                            @endfor
                        </flux:select>
                        <flux:description>Max 28 to avoid month-end issues.</flux:description>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Time</flux:label>
                    <flux:input wire:model="backupTime" type="time" />
                    <flux:error name="backupTime" />
                </flux:field>

                <flux:field>
                    <flux:label>Send backup to email</flux:label>
                    <flux:input wire:model="backupEmail" type="email" placeholder="admin@example.com" />
                    <flux:error name="backupEmail" />
                </flux:field>
            </div>

            <flux:button wire:click="saveBackupSettings" variant="primary" icon="check">Save Backup Settings</flux:button>
        </div>
    </div>

    {{-- Printers --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="mb-4 text-sm font-semibold text-blue-700 dark:text-blue-400">Printers</h3>
        <flux:text class="mb-5 text-sm text-zinc-500">
            Label printers configured for this system. Each printer requires QZ Tray running on the local computer.
        </flux:text>

        {{-- PM-241-BT --}}
        <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <button type="button" @click="open = !open"
                class="flex w-full items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <flux:icon.printer class="size-5 text-zinc-400" />
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Zhuhai PM-241-BT</p>
                        <p class="text-xs text-zinc-400">Bluetooth label printer — 104mm</p>
                    </div>
                </div>
                <flux:badge color="green" size="sm">Active</flux:badge>
            </button>

            <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Setup instructions per computer</p>
                <ol class="flex flex-col gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                    <li class="flex items-start gap-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">1</span>
                        Download and install <strong>QZ Tray</strong> from <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">qz.io</span> — free desktop agent for browser printing.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">2</span>
                        Turn on the PM-241-BT and pair it via <strong>Windows Bluetooth Settings → Add device</strong>.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">3</span>
                        Open QZ Tray — it should appear as an icon in the Windows taskbar (bottom right).
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">4</span>
                        Open <strong>Chrome</strong> and go to Clients — use the 🖨️ button on any client row to print a label.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30">5</span>
                        On first use, QZ Tray will ask for permission — click <strong>Allow</strong> and check <strong>"Remember this decision"</strong>.
                    </li>
                </ol>
                <div class="mt-4 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                    <p class="text-xs text-zinc-500"><strong>Label content:</strong> Client name (large) → Phone → Address</p>
                    <p class="text-xs text-zinc-500 mt-1"><strong>Label size:</strong> 104mm wide</p>
                    <p class="text-xs text-zinc-500 mt-1"><strong>Connection:</strong> Bluetooth (BTH001)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- IP Restriction Settings --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-red-700 dark:text-red-400">IP Access Restriction</h3>
            @if($ipRestrictionEnabled)
                <flux:badge color="red" size="sm">Enabled</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">Disabled</flux:badge>
            @endif
        </div>

        <flux:text class="mb-5 text-sm text-zinc-500">
            When enabled, the dashboard will only be accessible from the IP addresses listed below.
            All other IPs will be blocked with a 403 error. The public website is never affected.
        </flux:text>

        <div class="space-y-5">
            <flux:field>
                <flux:switch wire:model="ipRestrictionEnabled" label="Enable IP Restriction" />
            </flux:field>

            <flux:field>
                <flux:label>Allowed IP Addresses</flux:label>
                <flux:description>Enter one or more IP addresses separated by commas. Example: <code class="font-mono text-xs">192.168.1.10, 10.0.0.5</code></flux:description>
                <flux:textarea wire:model="allowedIps" rows="3" placeholder="e.g. 192.168.1.10, 203.0.113.42" />
                <flux:error name="allowedIps" />
            </flux:field>

            {{-- Current IP helper --}}
            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.computer-desktop class="size-4 shrink-0 text-zinc-400" />
                <div class="flex-1">
                    <p class="text-xs text-zinc-500">Your current IP address</p>
                    <p class="font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $currentIp }}</p>
                </div>
                <flux:button wire:click="addCurrentIp" size="sm" variant="ghost" icon="plus">Add</flux:button>
            </div>

            <flux:button wire:click="saveIpSettings" variant="primary" icon="shield-check">Save IP Settings</flux:button>
        </div>
    </div>

</div>
