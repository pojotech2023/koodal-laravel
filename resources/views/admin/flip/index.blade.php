@extends('admin.layouts.admin')

@section('title', 'Flip Tiles — Admin Panel')

@section('admin-body')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-ink-900 tracking-tight">🧩 Flip Tiles Image Manager</h1>
            <p class="text-xs font-semibold text-ink-500 mt-1">
                Upload and manage memory-match tile images for Groom & Bride 1v1 game.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                🎯 Questions
            </a>
            <a href="{{ route('admin.games.find_it.index') }}" class="rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">
                🔍 Find It
            </a>
            <a href="{{ route('admin.games.memory_snap.index') }}" class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-100">
                🧠 Memory Snap
            </a>
            <span class="rounded-xl border border-indigo-300 bg-indigo-100 px-4 py-2 text-sm font-bold text-indigo-900 shadow-sm">
                🧩 Flip Tiles
            </span>
            <a href="{{ route('admin.games.vibe_stories.index') }}" class="rounded-xl border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-pink-700 hover:bg-pink-100">
                ❤️ Vibe Stories
            </a>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="rounded-2xl p-4 border flex items-center justify-between gap-4 shadow-sm {{ $activeCount >= 8 ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
        <div class="flex items-center gap-3 text-xs sm:text-sm font-bold">
            <span class="text-xl">{{ $activeCount >= 8 ? '✅' : '⚠️' }}</span>
            <span>Minimum 8 active images required. Currently: <strong>{{ $activeCount }} active</strong>.</span>
        </div>
        @if($activeCount < 8)
            <span class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-amber-200 text-amber-900">
                Action Required
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Upload & Settings Section --}}
        <div class="space-y-6">

            {{-- Multiple Image Upload Form --}}
            <div class="rounded-3xl bg-white p-6 border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-sm font-black uppercase tracking-wider text-ink-800 flex items-center gap-2">
                    <span>📤</span>
                    <span>Upload New Tile Images</span>
                </h2>
                <p class="text-xs text-ink-500">
                    Upload multiple images (Flowers, Birds, Cars, Jewels, etc.). Max 2MB each. Images will be automatically resized to max 300x300.
                </p>

                <form action="{{ route('admin.flip-tiles.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-ink-700 mb-2">Select Images (Multiple)</label>
                        <input type="file"
                               name="images[]"
                               multiple
                               accept="image/jpeg,image/png,image/webp"
                               required
                               class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-gradient text-white font-bold text-xs uppercase tracking-wider shadow-md hover:opacity-95 transition-all">
                        Upload Selected Images
                    </button>
                </form>
            </div>

            {{-- Game Settings Form --}}
            <div class="rounded-3xl bg-white p-6 border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-sm font-black uppercase tracking-wider text-ink-800 flex items-center gap-2">
                    <span>⚙️</span>
                    <span>Game Settings</span>
                </h2>

                <form action="{{ route('admin.flip-tiles.settings') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-ink-700 mb-1">Card Back Image (Optional)</label>
                        @if($setting->card_back_url)
                            <div class="mb-2 flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-200">
                                <img src="{{ $setting->card_back_url }}" alt="Card Back" class="w-10 h-10 object-cover rounded-lg border">
                                <span class="text-xs text-slate-600 font-medium">Current custom card back</span>
                            </div>
                        @endif
                        <input type="file"
                               name="card_back"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Turn Timeout (s)</label>
                            <input type="number"
                                   name="turn_timeout_seconds"
                                   value="{{ old('turn_timeout_seconds', $setting->turn_timeout_seconds ?? 60) }}"
                                   min="10"
                                   max="300"
                                   required
                                   class="w-full px-3 py-2 border rounded-xl text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Preview (s)</label>
                            <input type="number"
                                   name="preview_seconds"
                                   value="{{ old('preview_seconds', $setting->preview_seconds ?? 2) }}"
                                   min="1"
                                   max="10"
                                   required
                                   class="w-full px-3 py-2 border rounded-xl text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Mismatch (s)</label>
                            <input type="number"
                                   name="mismatch_show_seconds"
                                   value="{{ old('mismatch_show_seconds', $setting->mismatch_show_seconds ?? 1) }}"
                                   min="1"
                                   max="5"
                                   required
                                   class="w-full px-3 py-2 border rounded-xl text-xs font-semibold">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-800 font-bold text-xs transition-all">
                        Save Settings
                    </button>
                </form>
            </div>

        </div>

        {{-- Tile Images List Table / Grid --}}
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-black uppercase tracking-wider text-ink-800 flex items-center justify-between">
                <span>🖼️ Uploaded Images List</span>
                <span class="text-xs font-bold text-slate-500">Total: {{ $images->total() }}</span>
            </h2>

            @if($images->isEmpty())
                <div class="py-12 text-center text-xs font-medium text-slate-400 border border-dashed rounded-2xl">
                    No tile images uploaded yet. Upload at least 8 active images to enable the game.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($images as $img)
                        <div class="group relative rounded-2xl border p-2 bg-slate-50 flex flex-col justify-between space-y-2 transition-all hover:shadow-md {{ $img->is_active ? 'border-slate-200' : 'border-red-200 bg-red-50/50' }}">
                            <div class="aspect-square rounded-xl overflow-hidden bg-white border border-slate-100">
                                <img src="{{ $img->url }}" alt="{{ $img->name }}" class="w-full h-full object-cover">
                            </div>

                            <div>
                                <div class="text-xs font-bold text-slate-800 truncate" title="{{ $img->name }}">{{ $img->name }}</div>
                                <span class="inline-block mt-0.5 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-full {{ $img->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $img->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1 pt-1 border-t border-slate-200/60">
                                <form action="{{ route('admin.flip-tiles.toggle', $img) }}" method="POST" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="w-full py-1 text-[10px] font-bold rounded-lg border transition-all {{ $img->is_active ? 'bg-white text-slate-700 hover:bg-slate-100' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                                        {{ $img->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.flip-tiles.destroy', $img) }}" method="POST" onsubmit="return confirm('Delete this image?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-[10px] font-bold rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition-all">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $images->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
