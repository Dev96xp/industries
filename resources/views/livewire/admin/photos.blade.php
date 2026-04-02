<?php

use App\Models\Photo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['uploads.*' => 'image|max:10240'])]
    public array $uploads = [];

    public string $filterCategory = '';

    public function saveUploads(): void
    {
        $this->validate();

        $count = 0;
        foreach ($this->uploads as $file) {
            $path = $file->store('photos', 'public');

            Photo::create([
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
                'category'  => $this->filterCategory ?: null,
                'disk'      => 'public',
            ]);

            $count++;
        }

        $this->uploads = [];
        $this->dispatch('photos-saved');

        session()->flash('success', "{$count} photo(s) saved successfully.");
    }

    public function toggleFeatured(Photo $photo): void
    {
        $photo->update(['is_featured' => ! $photo->is_featured]);
    }

    public function setAsHero(Photo $photo): void
    {
        Photo::query()->update(['is_hero' => false]);
        $photo->update(['is_hero' => true]);
    }

    public function setAsAbout(Photo $photo): void
    {
        Photo::query()->update(['is_about' => false]);
        $photo->update(['is_about' => true]);
    }

    public function deletePhoto(Photo $photo): void
    {
        \Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();
    }

    public function updateTitle(Photo $photo, string $title): void
    {
        $photo->update(['title' => $title]);
    }

    public function with(): array
    {
        return [
            'photos' => Photo::query()
                ->when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-1">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Photo Management</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Upload and manage photos for the website.</flux:text>
            </div>
            <flux:badge color="blue" size="lg">{{ $photos->count() }} Photos</flux:badge>
        </div>

        {{-- Success message --}}
        @if(session('success'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        @endif

        {{-- Upload Zone --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Upload Photos</flux:heading>

            <div
                x-data="{
                    isDragging: false,
                    previews: [],
                    ready: false,
                    uploading: false,
                    progress: 0,
                    compressImage(file, maxBytes = 900 * 1024) {
                        return new Promise((resolve) => {
                            if (file.size <= maxBytes) { resolve(file); return; }
                            const img = new Image();
                            const url = URL.createObjectURL(file);
                            img.onload = () => {
                                URL.revokeObjectURL(url);
                                const canvas = document.createElement('canvas');
                                let { width, height } = img;
                                const scale = Math.sqrt(maxBytes / file.size);
                                width = Math.round(width * scale);
                                height = Math.round(height * scale);
                                canvas.width = width;
                                canvas.height = height;
                                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                                const mime = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                                let quality = 0.85;
                                const tryCompress = () => {
                                    canvas.toBlob((blob) => {
                                        if (blob.size <= maxBytes || quality <= 0.3) {
                                            resolve(new File([blob], file.name, { type: mime }));
                                        } else {
                                            quality -= 0.1;
                                            canvas.toBlob((b) => resolve(new File([b], file.name, { type: mime })), mime, quality);
                                        }
                                    }, mime, quality);
                                };
                                tryCompress();
                            };
                            img.src = url;
                        });
                    },
                    async uploadFiles(files) {
                        if (!files.length) return;
                        this.previews = [];
                        this.ready = false;
                        this.uploading = true;
                        this.progress = 0;
                        const compressed = await Promise.all(files.map(f => this.compressImage(f)));
                        compressed.forEach(file => {
                            const reader = new FileReader();
                            reader.onload = (e) => this.previews.push(e.target.result);
                            reader.readAsDataURL(file);
                        });
                        $wire.uploadMultiple(
                            'uploads',
                            compressed,
                            () => { this.uploading = false; this.ready = true; },
                            () => { this.uploading = false; alert('Upload failed. Please try again.'); },
                            (e) => { this.progress = e.detail.progress; }
                        );
                    },
                    handleDrop(event) {
                        this.isDragging = false;
                        const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
                        if (files.length) this.uploadFiles(files);
                    },
                    reset() {
                        this.previews = [];
                        this.ready = false;
                        this.uploading = false;
                        this.progress = 0;
                        document.getElementById('file-picker').value = '';
                        $wire.set('uploads', []);
                    }
                }"
                x-on:photos-saved.window="reset()"
                class="relative"
            >
                {{-- Real file input (click to browse, change triggers upload) --}}
                <input
                    type="file"
                    id="file-picker"
                    multiple
                    accept="image/*"
                    class="hidden"
                    @change="uploadFiles(Array.from($event.target.files))"
                >

                {{-- Dropzone area --}}
                <div
                    @click="document.getElementById('file-picker').click()"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop($event)"
                    :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' : 'border-zinc-300 dark:border-zinc-600'"
                    class="flex min-h-[180px] cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed transition-colors hover:border-blue-400 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <div class="flex size-12 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-900/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="font-medium text-zinc-700 dark:text-zinc-300">Drag & drop photos here</p>
                        <p class="text-sm text-zinc-400">or click to browse — PNG, JPG, WEBP up to 10MB each</p>
                    </div>
                </div>

                {{-- Upload progress --}}
                <div x-show="uploading && previews.length > 0" class="mt-4">
                    <div class="flex items-center justify-between text-sm text-zinc-500">
                        <span>Uploading to server...</span>
                        <span x-text="progress + '%'"></span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full rounded-full bg-blue-500 transition-all duration-300" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                {{-- Save button — only shown once upload to temp storage is complete --}}
                <div x-show="ready" class="mt-4 flex items-center justify-between">
                    <p class="text-sm text-zinc-500"><span x-text="previews.length"></span> photo(s) ready to save</p>
                    <div class="flex gap-3">
                        <flux:button @click="reset()" variant="ghost">Clear</flux:button>
                        <flux:button wire:click="saveUploads" variant="filled" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveUploads">Save Photos</span>
                            <span wire:loading wire:target="saveUploads">Saving...</span>
                        </flux:button>
                    </div>
                </div>

                {{-- Preview grid --}}
                <div x-show="previews.length > 0" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6">
                    <template x-for="(src, index) in previews" :key="index">
                        <div class="aspect-square overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <img :src="src" class="h-full w-full object-cover">
                        </div>
                    </template>
                </div>

                @error('uploads.*')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-zinc-500">Filter:</span>
            @foreach(['', 'residential', 'commercial', 'industrial', 'renovation'] as $cat)
                <button
                    wire:click="$set('filterCategory', '{{ $cat }}')"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $filterCategory === $cat ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' }}"
                >
                    {{ $cat === '' ? 'All' : ucfirst($cat) }}
                </button>
            @endforeach
        </div>

        {{-- Photo grid --}}
        @if($photos->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 py-16 dark:border-zinc-700">
                <div class="text-4xl">🖼️</div>
                <p class="mt-3 font-medium text-zinc-500">No photos yet. Upload some above!</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($photos as $photo)
                    <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        {{-- Image --}}
                        <div class="aspect-square overflow-hidden">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->title }}" class="h-full w-full object-cover transition group-hover:scale-105">
                        </div>

                        {{-- Badges --}}
                        <div class="absolute left-2 top-2 flex flex-col gap-1">
                            @if($photo->is_hero)
                                <span class="rounded-full bg-yellow-400 px-2 py-0.5 text-xs font-semibold text-yellow-900">⭐ Hero</span>
                            @endif
                            @if($photo->is_about)
                                <span class="rounded-full bg-green-500 px-2 py-0.5 text-xs font-semibold text-white">About</span>
                            @endif
                            @if($photo->is_featured)
                                <span class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">Featured</span>
                            @endif
                        </div>

                        {{-- Actions overlay — desktop hover only --}}
                        <div class="absolute inset-0 hidden flex-col justify-end bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition group-hover:opacity-100 sm:flex">
                            <div class="flex items-center justify-between p-3">
                                <div class="flex gap-1.5">
                                    {{-- Hero star --}}
                                    <button
                                        wire:click="setAsHero({{ $photo->id }})"
                                        title="{{ $photo->is_hero ? 'Current hero photo' : 'Set as hero photo' }}"
                                        class="flex size-8 items-center justify-center rounded-full {{ $photo->is_hero ? 'bg-yellow-400 text-yellow-900' : 'bg-white/20 text-white hover:bg-yellow-400 hover:text-yellow-900' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="{{ $photo->is_hero ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                    </button>

                                    {{-- About section --}}
                                    <button
                                        wire:click="setAsAbout({{ $photo->id }})"
                                        title="{{ $photo->is_about ? 'Current About photo' : 'Set as About photo' }}"
                                        class="flex size-8 items-center justify-center rounded-full {{ $photo->is_about ? 'bg-green-500 text-white' : 'bg-white/20 text-white hover:bg-green-500' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                    </button>

                                    {{-- Featured --}}
                                    <button
                                        wire:click="toggleFeatured({{ $photo->id }})"
                                        title="{{ $photo->is_featured ? 'Remove from featured' : 'Mark as featured' }}"
                                        class="flex size-8 items-center justify-center rounded-full {{ $photo->is_featured ? 'bg-blue-500 text-white' : 'bg-white/20 text-white hover:bg-blue-500' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </button>
                                </div>

                                <button
                                    wire:click="deletePhoto({{ $photo->id }})"
                                    wire:confirm="Are you sure you want to delete this photo?"
                                    class="flex size-8 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-red-500"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Title / category --}}
                        <div class="p-3">
                            <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $photo->title ?: pathinfo($photo->path, PATHINFO_FILENAME) }}
                            </p>
                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                @if($photo->category)
                                    <span class="inline-block rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 dark:bg-zinc-800">
                                        {{ ucfirst($photo->category) }}
                                    </span>
                                @endif
                                @if($photo->size)
                                    <span class="inline-block rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] text-zinc-400 dark:bg-zinc-800">
                                        {{ $photo->size >= 1048576
                                            ? number_format($photo->size / 1048576, 1) . ' MB'
                                            : number_format($photo->size / 1024, 0) . ' KB' }}
                                    </span>
                                @endif
                            </div>

                            {{-- Mobile action buttons — visible only on small screens --}}
                            <div class="mt-2 flex items-center justify-between sm:hidden">
                                <div class="flex gap-1">
                                    {{-- Hero --}}
                                    <button
                                        wire:click="setAsHero({{ $photo->id }})"
                                        title="{{ $photo->is_hero ? 'Current hero photo' : 'Set as hero photo' }}"
                                        class="flex size-7 items-center justify-center rounded-full {{ $photo->is_hero ? 'bg-yellow-400 text-yellow-900' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="{{ $photo->is_hero ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                    </button>

                                    {{-- About --}}
                                    <button
                                        wire:click="setAsAbout({{ $photo->id }})"
                                        title="{{ $photo->is_about ? 'Current About photo' : 'Set as About photo' }}"
                                        class="flex size-7 items-center justify-center rounded-full {{ $photo->is_about ? 'bg-green-500 text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                    </button>

                                    {{-- Featured --}}
                                    <button
                                        wire:click="toggleFeatured({{ $photo->id }})"
                                        title="{{ $photo->is_featured ? 'Remove from featured' : 'Mark as featured' }}"
                                        class="flex size-7 items-center justify-center rounded-full {{ $photo->is_featured ? 'bg-blue-500 text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }} transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Delete --}}
                                <button
                                    wire:click="deletePhoto({{ $photo->id }})"
                                    wire:confirm="Are you sure you want to delete this photo?"
                                    class="flex size-7 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 transition hover:bg-red-500 hover:text-white dark:bg-zinc-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
