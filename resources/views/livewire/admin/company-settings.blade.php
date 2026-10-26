<?php

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $video = null;

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

    public function updatedVideo(): void
    {
        $this->uploadVideo();
    }

    public function uploadVideo(): void
    {
        $this->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:40960'],
        ], [
            'video.mimetypes' => 'The video must be an MP4, MOV or WebM file.',
            'video.max'       => 'The video cannot be larger than 40 MB.',
        ]);

        $settings = CompanySetting::current();
        $oldPath  = $settings->website_video_path;

        $settings->update([
            'website_video_path' => $this->video->store('videos', 'public'),
        ]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('video');
        session()->flash('success', 'Website video updated.');
    }

    public function removeVideo(): void
    {
        $settings = CompanySetting::current();

        if ($settings->website_video_path) {
            Storage::disk('public')->delete($settings->website_video_path);
            $settings->update(['website_video_path' => null]);
        }

        session()->flash('success', 'Website video removed.');
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

    {{-- Website Video --}}
    @php $videoUrl = \App\Models\CompanySetting::current()->websiteVideoUrl(); @endphp
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Website Video</flux:heading>
        <flux:text class="mt-1 mb-5 text-zinc-500">Shown on the home page in the "See Our Work" section. MP4 recommended, up to 40 MB.</flux:text>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div>
                @if($videoUrl)
                    <video src="{{ $videoUrl }}" controls playsinline preload="metadata"
                        class="aspect-video w-full rounded-xl bg-black" wire:key="video-{{ md5($videoUrl) }}"></video>
                @else
                    <div class="flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-700">
                        <flux:icon.film class="size-8" />
                        <p class="text-sm">No video uploaded yet</p>
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-4"
                x-data="{ uploading: false, progress: 0 }"
                x-on:livewire-upload-start="uploading = true; progress = 0"
                x-on:livewire-upload-finish="uploading = false"
                x-on:livewire-upload-cancel="uploading = false"
                x-on:livewire-upload-error="uploading = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress">
                <flux:field>
                    <flux:label>{{ $videoUrl ? 'Replace video' : 'Upload video' }}</flux:label>
                    <input type="file" wire:model="video" accept="video/mp4,video/quicktime,video/webm"
                        class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
                    <flux:description>The video is saved automatically as soon as the upload finishes.</flux:description>
                    <flux:error name="video" />
                </flux:field>

                <div x-show="uploading" x-cloak class="flex flex-col gap-1.5">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-blue-600 transition-all duration-200" :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-xs text-zinc-500">Uploading… <span x-text="progress"></span>%</p>
                </div>
                <p wire:loading wire:target="video" class="text-xs text-zinc-500">Saving video…</p>

                @if($videoUrl)
                    <div>
                        <flux:button wire:click="removeVideo" wire:confirm="Remove the video from the website?" variant="ghost" icon="trash">
                            Remove video
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
