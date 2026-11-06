@extends('admin.layouts.admin')

@section('title', $isEdit ? 'Edit Puzzle & Hotspots' : 'Create Puzzle')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/animations.css') }}">
@endpush

@section('admin-body')
<div x-data="hotspotEditor({
    differences: {{ json_encode($isEdit ? $puzzle->differences : []) }},
    originalUrl: '{{ $isEdit ? Storage::disk('public')->url($puzzle->original_image) : '' }}',
    modifiedUrl: '{{ $isEdit ? Storage::disk('public')->url($puzzle->modified_image) : '' }}',
    puzzleId: {{ $isEdit ? $puzzle->id : 'null' }}
})" class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">{{ $isEdit ? 'Edit Puzzle & Hotspot Differences' : 'Create Spot the Difference Puzzle' }}</h1>
            <p class="mt-1 text-sm text-ink-500">Upload high quality image pairs, place hotspot markers, and define animations.</p>
        </div>
        <a href="{{ route('admin.puzzles.index') }}" class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-slate-50" style="border-color:var(--border-default)">
            &larr; Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-bold">Please fix the following errors:</p>
            <ul class="mt-1.5 list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.puzzles.update', $puzzle->id) : route('admin.puzzles.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Puzzle Settings Card --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm space-y-6" style="border-color:var(--border-default)">
            <h2 class="text-lg font-bold text-ink-900">1. Puzzle Information</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Title</label>
                    <input type="text" name="title" value="{{ old('title', $puzzle->title) }}" required class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Difficulty</label>
                    <select name="difficulty" class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                        <option value="easy" {{ old('difficulty', $puzzle->difficulty) == 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="medium" {{ old('difficulty', $puzzle->difficulty) == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="hard" {{ old('difficulty', $puzzle->difficulty) == 'hard' ? 'selected' : '' }}>Hard</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Time Limit (Seconds)</label>
                    <input type="number" name="time_limit_seconds" value="{{ old('time_limit_seconds', $puzzle->time_limit_seconds ?? 180) }}" min="30" max="600" required class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                </div>

                <div class="flex items-center pt-6">
                    <label class="relative inline-flex items-center cursor-pointer gap-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $puzzle->is_active) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <div>
                            <span class="text-sm font-bold text-ink-900">Active Puzzle</span>
                            <span class="block text-xs text-ink-500">Requires at least {{ $minDifferences }} differences to activate.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 pt-4 border-t" style="border-color:var(--border-default)">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Original Image {{ $isEdit ? '(Leave empty to keep current)' : '*' }}</label>
                    <input type="file" name="original_image" @change="previewImage($event, 'orig')" accept="image/png,image/jpeg,image/webp" {{ $isEdit ? '' : 'required' }} class="mt-1.5 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="mt-1 text-xs text-ink-400">Max 5MB (JPG, PNG, WebP). Must match modified image dimensions.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Modified Image {{ $isEdit ? '(Leave empty to keep current)' : '*' }}</label>
                    <input type="file" name="modified_image" @change="previewImage($event, 'mod')" accept="image/png,image/jpeg,image/webp" {{ $isEdit ? '' : 'required' }} class="mt-1.5 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="mt-1 text-xs text-ink-400">Max 5MB (JPG, PNG, WebP). Must match original image dimensions.</p>
                </div>
            </div>
        </div>

        {{-- Hotspot Visual Editor Card --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm space-y-6" style="border-color:var(--border-default)">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-ink-900">2. Visual Hotspot Editor</h2>
                    <p class="text-xs text-ink-500">Click anywhere on the image preview to add a difference marker. Drag or edit parameters below.</p>
                </div>

                <button type="button" @click="suggestHotspots()" :disabled="comparing" class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-3.5 py-2 text-xs font-bold text-primary-700 hover:bg-primary-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span x-text="comparing ? 'Analyzing Images...' : 'Suggest Hotspots (Auto-Diff)'"></span>
                </button>
            </div>

            {{-- Dual Canvas Preview --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Original Image Canvas --}}
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-ink-600">Original Image</span>
                    <div class="relative border rounded-xl overflow-hidden bg-slate-900 select-none cursor-crosshair min-h-[300px]" @click="addSpotFromClick($event, 'original')" x-ref="origContainer">
                        <template x-if="originalUrl">
                            <img :src="originalUrl" class="w-full h-auto block select-none pointer-events-none" @load="updateContainerSize()">
                        </template>
                        <template x-if="!originalUrl">
                            <div class="h-64 grid place-items-center text-xs text-slate-400">Upload original image to view preview</div>
                        </template>

                        {{-- Hotspot Markers --}}
                        <template x-for="(diff, index) in differences" :key="index">
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 group"
                                 :style="`left: ${diff.x_pct}%; top: ${diff.y_pct}%;`"
                                 @click.stop="selectSpot(index)">
                                {{-- Radius circle preview --}}
                                <div class="absolute rounded-full border-2 border-primary-500 bg-primary-500/20 pointer-events-none -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                     :style="`width: ${diff.radius_pct * 2}vw; height: ${diff.radius_pct * 2}vw;`"
                                     :class="{ 'border-emerald-400 bg-emerald-500/30': activeIndex === index }"></div>
                                {{-- Marker badge --}}
                                <div class="relative grid h-7 w-7 place-items-center rounded-full bg-primary-600 text-xs font-bold text-white shadow-lg transition-transform hover:scale-110"
                                     :class="[ activeIndex === index ? '!bg-emerald-600 ring-4 ring-emerald-300' : '', previewingIndex === index ? getAnimClass(diff.animation_type) : '' ]">
                                    <span x-text="index + 1"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Modified Image Canvas --}}
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-ink-600">Modified Image</span>
                    <div class="relative border rounded-xl overflow-hidden bg-slate-900 select-none cursor-crosshair min-h-[300px]" @click="addSpotFromClick($event, 'modified')" x-ref="modContainer">
                        <template x-if="modifiedUrl">
                            <img :src="modifiedUrl" class="w-full h-auto block select-none pointer-events-none">
                        </template>
                        <template x-if="!modifiedUrl">
                            <div class="h-64 grid place-items-center text-xs text-slate-400">Upload modified image to view preview</div>
                        </template>

                        {{-- Hotspot Markers --}}
                        <template x-for="(diff, index) in differences" :key="index">
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 group"
                                 :style="`left: ${diff.x_pct}%; top: ${diff.y_pct}%;`"
                                 @click.stop="selectSpot(index)">
                                <div class="absolute rounded-full border-2 border-accent-500 bg-accent-500/20 pointer-events-none -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                     :style="`width: ${diff.radius_pct * 2}vw; height: ${diff.radius_pct * 2}vw;`"
                                     :class="{ 'border-emerald-400 bg-emerald-500/30': activeIndex === index }"></div>
                                <div class="relative grid h-7 w-7 place-items-center rounded-full bg-accent-600 text-xs font-bold text-white shadow-lg transition-transform hover:scale-110"
                                     :class="[ activeIndex === index ? '!bg-emerald-600 ring-4 ring-emerald-300' : '', previewingIndex === index ? getAnimClass(diff.animation_type) : '' ]">
                                    <span x-text="index + 1"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Differences List / Hotspot Inspector --}}
            <div class="border-t pt-6 space-y-4" style="border-color:var(--border-default)">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-ink-900">Defined Hotspots (<span x-text="differences.length"></span>)</h3>
                    <button type="button" @click="addSpotManually()" class="rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-800">
                        + Add Spot Manually
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(diff, index) in differences" :key="index">
                        <div class="rounded-xl border bg-slate-50/70 p-4 transition-all" :class="{ '!border-emerald-500 !bg-emerald-50/20 ring-2 ring-emerald-400/30': activeIndex === index }">
                            <input type="hidden" :name="`differences[${index}][id]`" :value="diff.id || ''">

                            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between pb-3 border-b border-slate-200">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white" x-text="index + 1"></span>
                                    <input type="text" :name="`differences[${index}][label]`" x-model="diff.label" placeholder="Spot Label (e.g., Magic Flower)" required class="rounded-lg border px-3 py-1.5 text-xs font-bold w-60">
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" @click="previewAnimation(index)" class="inline-flex items-center gap-1.5 rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-primary-700 shadow-sm hover:bg-primary-50">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                        Preview Animation
                                    </button>
                                    <button type="button" @click="removeSpot(index)" class="rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 pt-3 text-xs">
                                <div>
                                    <label class="block font-bold text-ink-600">X Position (%)</label>
                                    <input type="number" step="0.0001" min="0" max="100" :name="`differences[${index}][x_pct]`" x-model.number="diff.x_pct" required class="mt-1 w-full rounded-lg border px-2.5 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-ink-600">Y Position (%)</label>
                                    <input type="number" step="0.0001" min="0" max="100" :name="`differences[${index}][y_pct]`" x-model.number="diff.y_pct" required class="mt-1 w-full rounded-lg border px-2.5 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-ink-600">Radius (%)</label>
                                    <input type="number" step="0.1" min="0.5" max="30" :name="`differences[${index}][radius_pct]`" x-model.number="diff.radius_pct" required class="mt-1 w-full rounded-lg border px-2.5 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-ink-600">Animation Type</label>
                                    <select :name="`differences[${index}][animation_type]`" x-model="diff.animation_type" class="mt-1 w-full rounded-lg border px-2.5 py-1.5 text-xs bg-white">
                                        <option value="fly_away">Fly Away</option>
                                        <option value="fall">Fall</option>
                                        <option value="swing_open">Swing Open</option>
                                        <option value="drift">Drift</option>
                                        <option value="pulse">Pulse</option>
                                        <option value="blink">Blink</option>
                                        <option value="fade_out">Fade Out</option>
                                        <option value="lottie">Lottie JSON</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-ink-600">Animate On</label>
                                    <select :name="`differences[${index}][animate_on]`" x-model="diff.animate_on" class="mt-1 w-full rounded-lg border px-2.5 py-1.5 text-xs bg-white">
                                        <option value="modified">Modified Image</option>
                                        <option value="original">Original Image</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-ink-600">Sprite PNG/SVG</label>
                                    <input type="file" :name="`differences[${index}][sprite]`" accept="image/png,image/svg+xml" class="mt-1 block w-full text-[10px]">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.puzzles.index') }}" class="rounded-xl border bg-white px-5 py-2.5 text-sm font-bold text-ink-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-xl bg-brand-gradient px-6 py-2.5 text-sm font-bold text-white shadow-glow hover:-translate-y-0.5 transition-transform">
                {{ $isEdit ? 'Save Changes' : 'Create Puzzle' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('hotspotEditor', (config) => ({
        differences: config.differences || [],
        originalUrl: config.originalUrl || '',
        modifiedUrl: config.modifiedUrl || '',
        puzzleId: config.puzzleId || null,
        activeIndex: null,
        previewingIndex: null,
        comparing: false,

        previewImage(e, type) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                if (type === 'orig') this.originalUrl = url;
                if (type === 'mod') this.modifiedUrl = url;
            }
        },

        addSpotFromClick(e, type) {
            const rect = e.currentTarget.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const xPct = Math.round((x / rect.width) * 10000) / 100;
            const yPct = Math.round((y / rect.height) * 10000) / 100;

            this.differences.push({
                label: `Spot ${this.differences.length + 1}`,
                x_pct: xPct,
                y_pct: yPct,
                radius_pct: 6.0,
                animation_type: 'pulse',
                animate_on: type,
                sort_order: this.differences.length + 1
            });

            this.activeIndex = this.differences.length - 1;
        },

        addSpotManually() {
            this.differences.push({
                label: `Spot ${this.differences.length + 1}`,
                x_pct: 50,
                y_pct: 50,
                radius_pct: 6.0,
                animation_type: 'pulse',
                animate_on: 'modified',
                sort_order: this.differences.length + 1
            });
            this.activeIndex = this.differences.length - 1;
        },

        selectSpot(index) {
            this.activeIndex = index;
        },

        removeSpot(index) {
            this.differences.splice(index, 1);
            if (this.activeIndex === index) this.activeIndex = null;
        },

        previewAnimation(index) {
            this.previewingIndex = index;
            setTimeout(() => {
                this.previewingIndex = null;
            }, 1600);
        },

        getAnimClass(type) {
            const map = {
                'fly_away': 'animate-fly-away',
                'fall': 'animate-fall',
                'swing_open': 'animate-swing-open',
                'drift': 'animate-drift',
                'pulse': 'animate-pulse-custom',
                'blink': 'animate-blink',
                'fade_out': 'animate-fade-out'
            };
            return map[type] || 'animate-pulse-custom';
        },

        async suggestHotspots() {
            this.comparing = true;
            try {
                const formData = new FormData();
                if (this.puzzleId) {
                    formData.append('puzzle_id', this.puzzleId);
                }
                formData.append('_token', '{{ csrf_token() }}');

                const res = await fetch('{{ route("admin.puzzles.compare") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.suggestions && data.suggestions.length > 0) {
                    data.suggestions.forEach(s => {
                        this.differences.push(s);
                    });
                    alert(`Added ${data.suggestions.length} suggested hotspots from image comparison!`);
                } else {
                    alert('No distinct hotspot clusters auto-detected. Try adjusting images or add spots manually.');
                }
            } catch (err) {
                console.error(err);
                alert('Image comparison failed.');
            } finally {
                this.comparing = false;
            }
        }
    }));
});
</script>
@endpush
@endsection
