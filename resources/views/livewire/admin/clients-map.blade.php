<?php

use App\Models\CompanySetting;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $clients = User::role('client')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'latitude', 'longitude']);

        $clientsWithoutCoords = User::role('client')
            ->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'))
            ->count();

        $mapClients = $clients->map(fn ($c) => [
            'id'      => $c->id,
            'name'    => $c->name,
            'email'   => $c->email,
            'phone'   => $c->phone,
            'address' => trim(collect([$c->address, $c->city, $c->state, $c->zip])->filter()->implode(', ')),
            'lat'     => (float) $c->latitude,
            'lng'     => (float) $c->longitude,
        ]);

        $company    = CompanySetting::current();
        $companyPin = ($company->latitude && $company->longitude) ? [
            'name'    => $company->company_name,
            'address' => trim(collect([$company->address, $company->city, $company->state, $company->zip])->filter()->implode(', ')),
            'phone'   => $company->phone,
            'lat'     => (float) $company->latitude,
            'lng'     => (float) $company->longitude,
        ] : null;

        return compact('clients', 'clientsWithoutCoords', 'mapClients', 'companyPin');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Clients Map</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Geographic location of your clients.</flux:text>
        </div>
        @if($clientsWithoutCoords > 0)
            <flux:callout variant="warning" icon="exclamation-triangle" class="max-w-sm">
                {{ $clientsWithoutCoords }} client(s) have no coordinates. Add their address in
                <a href="{{ route('admin.clients') }}" wire:navigate class="underline">Clients</a>.
            </flux:callout>
        @endif
    </div>

    {{-- Map --}}
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-700 dark:bg-zinc-900">
        <div id="clients-map" style="height: 720px; width: 100%;"></div>
    </div>

</div>

@push('scripts')
<script>
    const clientsData = @json($mapClients);
    const companyPin  = @json($companyPin) ?? {
        name: 'Nucleus Industries',
        address: '',
        phone: '',
        lat: 41.2565,
        lng: -95.9345,
    };

    document.addEventListener('livewire:navigated', function () {
        if (!document.getElementById('clients-map')) return;
        initClientsMap();
    });

    function initClientsMap() {
        const container = document.getElementById('clients-map');
        if (!container) return;

        // Destroy previous instance if exists
        if (container._leaflet_id) {
            container._leaflet_id = null;
            container.innerHTML = '';
        }

        const map = L.map('clients-map').setView([41.5, -98.0], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const icon = L.divIcon({
            className: '',
            html: `<div style="
                width: 32px; height: 32px;
                background: #2563eb;
                border: 2px solid white;
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                color: white; font-size: 11px; font-weight: bold;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            ">C</div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -18],
        });

        const bounds = [];

        clientsData.forEach(function (client) {
            const popup = `
                <div style="min-width: 180px; font-family: sans-serif;">
                    <p style="font-weight: 600; font-size: 14px; margin: 0 0 4px;">${client.name}</p>
                    ${client.address ? `<p style="font-size: 12px; color: #6b7280; margin: 2px 0;">${client.address}</p>` : ''}
                    ${client.email   ? `<p style="font-size: 12px; color: #6b7280; margin: 2px 0;">${client.email}</p>` : ''}
                    ${client.phone   ? `<p style="font-size: 12px; color: #6b7280; margin: 2px 0;">${client.phone}</p>` : ''}
                </div>
            `;

            L.marker([client.lat, client.lng], { icon })
                .addTo(map)
                .bindPopup(popup);

            bounds.push([client.lat, client.lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40] });
        }

        // Company marker
        if (companyPin) {
            const companyIcon = L.divIcon({
                className: '',
                html: `<div style="
                    width: 36px; height: 36px;
                    background: #dc2626;
                    border: 3px solid white;
                    border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    color: white; font-size: 11px; font-weight: bold;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                ">&#x1F3E2;</div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20],
            });

            const companyPopup = `
                <div style="min-width: 180px; font-family: sans-serif;">
                    <p style="font-weight: 700; font-size: 14px; color: #dc2626; margin: 0 0 4px;">🏢 ${companyPin.name}</p>
                    ${companyPin.address ? `<p style="font-size: 12px; color: #6b7280; margin: 2px 0;">${companyPin.address}</p>` : ''}
                    ${companyPin.phone   ? `<p style="font-size: 12px; color: #6b7280; margin: 2px 0;">${companyPin.phone}</p>` : ''}
                </div>
            `;

            L.marker([companyPin.lat, companyPin.lng], { icon: companyIcon })
                .addTo(map)
                .bindPopup(companyPopup)
                .openPopup();
        }
    }

    document.addEventListener('DOMContentLoaded', initClientsMap);
</script>
@endpush
