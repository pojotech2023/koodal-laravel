@extends('admin.layouts.admin')

@section('title', ($isEdit ? 'Edit' : 'Create') . ' Find It Challenge — Admin')

@section('admin-body')
@php
$defaultDifferences = [
    ['id' => 1, 'name' => 'Gold Coin', 'type' => 'general', 'x' => 20, 'y' => 73],
    ['id' => 2, 'name' => 'Flying Bird', 'type' => 'bird', 'x' => 67, 'y' => 25],
    ['id' => 3, 'name' => 'Canopy Color', 'type' => 'general', 'x' => 67, 'y' => 60],
    ['id' => 4, 'name' => 'Gopuram Flag', 'type' => 'general', 'x' => 80, 'y' => 22],
    ['id' => 5, 'name' => 'Flower Mistake', 'type' => 'flower', 'x' => 37, 'y' => 25],
    ['id' => 6, 'name' => 'Yellow Umbrella', 'type' => 'general', 'x' => 35, 'y' => 76],
    ['id' => 7, 'name' => 'Moved Coconut', 'type' => 'general', 'x' => 22, 'y' => 80],
];
$initialDifferences = old('differences', !empty($challenge->differences) ? array_slice(array_values($challenge->differences), 0, 7) : $defaultDifferences);
@endphp

<div class="mx-auto max-w-4xl space-y-6" x-data="findItAdminForm()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">{{ $isEdit ? 'Edit Find It Challenge' : 'Create Find It Challenge' }}</h1>
            <p class="text-sm text-ink-500">Configure visual challenge images and difference hotspots with interactive click-to-pick coordinates.</p>
        </div>
        <a href="{{ route('admin.games.find_it.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-ink-600 hover:bg-gray-50">
            ← Back to List
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.games.find_it.update', $challenge->id) : route('admin.games.find_it.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-bold text-ink-800">Challenge Title *</label>
            <input type="text" name="title" value="{{ old('title', $challenge->title) }}" required placeholder="e.g. Madurai Meenakshi Temple Courtyard & Differences" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-cyan-600 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {{-- Image A (Original) --}}
            <div class="space-y-2 rounded-xl bg-slate-50 p-4 border border-gray-200">
                <label class="block text-sm font-extrabold text-cyan-900">Original Image (Image A) *</label>
                <input type="file" name="image_a_file" accept="image/*" @change="handleFileUpload($event, 'A')" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-cyan-700 file:text-white hover:file:bg-cyan-800">
                <div class="mt-1">
                    <span class="text-[11px] font-semibold text-gray-500">Or Image Path / URL:</span>
                    <input type="text" name="image_a" x-model="imagePathA" value="{{ old('image_a', $challenge->image_a ?? '/image/02_Koodal_Find_It.png') }}" class="mt-0.5 w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-mono">
                </div>
                <div class="mt-2 text-xs font-bold text-gray-600" x-show="previewUrlA || imagePathA">
                    <span x-text="previewUrlA ? 'New Uploaded Image Preview:' : 'Current Image:'"></span>
                    <img :src="previewUrlA || imagePathA" class="mt-1 h-24 w-auto rounded border shadow-sm object-contain bg-white">
                </div>
            </div>

            {{-- Image B (With 7 Differences) --}}
            <div class="space-y-2 rounded-xl bg-slate-50 p-4 border border-gray-200">
                <label class="block text-sm font-extrabold text-cyan-900">7 Differences Image (Image B) *</label>
                <input type="file" name="image_b_file" accept="image/*" @change="handleFileUpload($event, 'B')" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-cyan-700 file:text-white hover:file:bg-cyan-800">
                <div class="mt-1">
                    <span class="text-[11px] font-semibold text-gray-500">Or Image Path / URL:</span>
                    <input type="text" name="image_b" x-model="imagePathB" value="{{ old('image_b', $challenge->image_b ?? '/image/02_Koodal_Find_It.png') }}" class="mt-0.5 w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-mono">
                </div>
                <div class="mt-2 text-xs font-bold text-gray-600" x-show="previewUrlB || imagePathB">
                    <span x-text="previewUrlB ? 'New Uploaded Image Preview:' : 'Current Image:'"></span>
                    <img :src="previewUrlB || imagePathB" class="mt-1 h-24 w-auto rounded border shadow-sm object-contain bg-white">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $challenge->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
            <label for="is_active" class="text-sm font-bold text-ink-800">Active (Visible in Game Pool)</label>
        </div>

        {{-- Interactive Visual Hotspot Picker --}}
        <div class="rounded-2xl border-2 border-cyan-400 bg-slate-900 p-4 text-white space-y-3 shadow-md">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-700 pb-2">
                <div>
                    <h3 class="text-sm font-extrabold text-cyan-300 flex items-center gap-1.5">
                        <span>🎯</span> Visual Click-to-Set Hotspot Coordinates
                    </h3>
                    <p class="text-xs text-slate-300">Click on any difference spot on this image preview to automatically fill X% and Y% coordinates!</p>
                </div>
                <div>
                    <span class="text-xs font-black text-amber-300 bg-amber-950/80 px-3 py-1 rounded-full border border-amber-500/40"
                          x-text="`Target: #${selectedRow + 1} (${differences[selectedRow]?.name || 'Difference'})`"></span>
                </div>
            </div>

            {{-- Clickable Image Canvas Preview with Image-relative overlay --}}
            <div class="relative overflow-hidden rounded-xl border border-slate-700 bg-black min-h-[220px] max-h-[360px] cursor-crosshair select-none flex items-center justify-center p-2" @click="pickCoordinates($event)">
                <div class="relative inline-block mx-auto max-h-[350px]">
                    <img :src="imagePreviewUrl" id="adminPickerImg" alt="Hotspot Picker Image" class="max-h-[350px] w-auto object-contain block mx-auto pointer-events-none select-none rounded-lg">
                    
                    {{-- Render Hotspot Pin Badges for all items --}}
                    <template x-for="(diff, index) in differences" :key="`pin-${index}`">
                        <div class="absolute transform -translate-x-1/2 -translate-y-1/2 transition-all cursor-pointer z-20"
                             :style="`left: ${diff.x}%; top: ${diff.y}%;`"
                             @click.stop="selectRow(index)">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full font-black text-xs shadow-xl border-2 transition-all"
                                 :class="selectedRow === index ? 'bg-amber-400 text-slate-950 border-white scale-125 ring-4 ring-amber-400/50 animate-bounce' : 'bg-cyan-600 text-white border-white hover:scale-110'">
                                <span x-text="`#${index + 1}`"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Hotspots Table & Dynamic Editor --}}
        <div class="space-y-4 rounded-xl bg-gray-50 p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-ink-900">Difference Hotspots List & Animations</h3>
                    <p class="text-xs text-ink-500">Click a row to select it as target for image clicking, or type values manually.</p>
                </div>
                <button type="button" @click="addRow()" class="rounded-lg bg-cyan-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-cyan-800">
                    + Add Hotspot Row
                </button>
            </div>

            <div class="space-y-2">
                <template x-for="(diff, index) in differences" :key="index">
                    <div class="grid grid-cols-12 gap-2 items-center rounded-xl p-3 border shadow-sm text-xs transition-all cursor-pointer"
                         :class="selectedRow === index ? 'bg-cyan-50 border-cyan-400 ring-2 ring-cyan-300' : 'bg-white border-gray-200 hover:bg-gray-50'"
                         @click="selectRow(index)">
                        
                        <div class="col-span-1 font-black text-center" :class="selectedRow === index ? 'text-cyan-800' : 'text-gray-500'" x-text="`#${index + 1}`"></div>
                        
                        {{-- Name --}}
                        <div class="col-span-4" @click.stop>
                            <label class="block text-[10px] font-bold text-gray-500">Difference Label</label>
                            <input type="text" :name="`differences[${index}][name]`" x-model="diff.name" @focus="selectRow(index)" required placeholder="e.g. Tower Clock / Flying Bird" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5">
                        </div>

                        {{-- Type --}}
                        <div class="col-span-3" @click.stop>
                            <label class="block text-[10px] font-bold text-gray-500">Animation Type</label>
                            <select :name="`differences[${index}][type]`" x-model="diff.type" @focus="selectRow(index)" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 font-semibold">
                                <option value="clock">⏰ Clock (Big Zoom & Enlarge)</option>
                                <option value="bird">🐦 Flying Bird (Fly Away)</option>
                                <option value="flower">🌸 Flower Mistake (Falling Petals)</option>
                                <option value="general">✨ General Spot (Pulse Sparkle)</option>
                            </select>
                        </div>

                        {{-- X Position --}}
                        <div class="col-span-1.5" @click.stop>
                            <label class="block text-[10px] font-bold text-gray-500">X %</label>
                            <input type="number" step="0.1" :name="`differences[${index}][x]`" x-model="diff.x" @focus="selectRow(index)" required placeholder="X%" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 font-mono">
                        </div>

                        {{-- Y Position --}}
                        <div class="col-span-1.5" @click.stop>
                            <label class="block text-[10px] font-bold text-gray-500">Y %</label>
                            <input type="number" step="0.1" :name="`differences[${index}][y]`" x-model="diff.y" @focus="selectRow(index)" required placeholder="Y%" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 font-mono">
                        </div>

                        <input type="hidden" :name="`differences[${index}][id]`" :value="index + 1">
                        <input type="hidden" :name="`differences[${index}][radius]`" value="10">

                        {{-- Delete row --}}
                        <div class="col-span-1 text-center" @click.stop>
                            <button type="button" @click="removeRow(index)" class="text-rose-600 font-bold hover:underline">✕</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.games.find_it.index') }}" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-ink-600">Cancel</a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Update Challenge' : 'Save Challenge' }}
            </button>
        </div>
    </form>
</div>

<script>
function findItAdminForm() {
    return {
        differences: {!! json_encode($initialDifferences) !!},
        selectedRow: 0,
        imagePathA: '{{ old("image_a", $challenge->image_a ?? "/image/02_Koodal_Find_It.png") }}',
        imagePathB: '{{ old("image_b", $challenge->image_b ?? "/image/02_Koodal_Find_It.png") }}',
        previewUrlA: null,
        previewUrlB: null,

        get imagePreviewUrl() {
            return this.previewUrlB || this.imagePathB || this.previewUrlA || this.imagePathA || '/image/02_Koodal_Find_It.png';
        },

        handleFileUpload(event, side) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const objectUrl = URL.createObjectURL(file);
            if (side === 'B') {
                if (this.previewUrlB) URL.revokeObjectURL(this.previewUrlB);
                this.previewUrlB = objectUrl;
            } else {
                if (this.previewUrlA) URL.revokeObjectURL(this.previewUrlA);
                this.previewUrlA = objectUrl;
            }
        },

        selectRow(index) {
            this.selectedRow = index;
        },

        pickCoordinates(event) {
            const imgEl = document.getElementById('adminPickerImg') || event.currentTarget;
            const rect = imgEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            const x = Math.round((((event.clientX - rect.left) / rect.width) * 100) * 10) / 10;
            const y = Math.round((((event.clientY - rect.top) / rect.height) * 100) * 10) / 10;

            if (this.differences[this.selectedRow]) {
                this.differences[this.selectedRow].x = Math.max(0, Math.min(100, x));
                this.differences[this.selectedRow].y = Math.max(0, Math.min(100, y));
            }
        },

        addRow() {
            const nextId = this.differences.length + 1;
            this.differences.push({
                id: nextId,
                name: `Difference #${nextId}`,
                type: 'general',
                x: 50,
                y: 50,
                radius: 10
            });
            this.selectedRow = this.differences.length - 1;
        },

        removeRow(index) {
            if (this.differences.length <= 1) {
                alert('At least one difference hotspot is required.');
                return;
            }
            this.differences.splice(index, 1);
            if (this.selectedRow >= this.differences.length) {
                this.selectedRow = this.differences.length - 1;
            }
        }
    }
}
</script>
@endsection
