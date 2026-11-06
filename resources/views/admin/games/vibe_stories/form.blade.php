@extends('admin.layouts.admin')

@section('title', ($isEdit ? 'Edit Vibe Story' : 'Add Vibe Story') . ' — Koodal Matrimony')

@section('admin-body')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">{{ $isEdit ? 'Edit Vibe Story Template' : 'Create New Vibe Story Template' }}</h1>
            <p class="text-sm text-ink-500">Configure relationship story content, score boundaries, and dynamic narrative tokens.</p>
        </div>
        <a href="{{ route('admin.games.vibe_stories.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-ink-600 hover:bg-gray-50">
            ← Back to List
        </a>
    </div>

    {{-- Placeholders Cheat Sheet --}}
    <div class="rounded-2xl border border-pink-200 bg-pink-50/70 p-4 shadow-sm text-xs space-y-2">
        <div class="flex items-center gap-2 font-black text-pink-900">
            <span>✨</span>
            <span>Available Dynamic Tokens (Click any token to insert into Story Content)</span>
        </div>
        <div class="flex flex-wrap gap-2 pt-1 font-mono text-[11px]">
            <button type="button" onclick="insertToken('{groom_name}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {groom_name}</button>
            <button type="button" onclick="insertToken('{bride_name}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {bride_name}</button>
            <button type="button" onclick="insertToken('{score}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {score}</button>
            <button type="button" onclick="insertToken('{category_name}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {category_name}</button>
            <button type="button" onclick="insertToken('{observation_detail}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {observation_detail}</button>
            <button type="button" onclick="insertToken('{memory_detail}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {memory_detail}</button>
            <button type="button" onclick="insertToken('{teamwork_detail}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {teamwork_detail}</button>
            <button type="button" onclick="insertToken('{compatibility_detail}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {compatibility_detail}</button>
            <button type="button" onclick="insertToken('{favourite_scene}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {favourite_scene}</button>
            <button type="button" onclick="insertToken('{call_to_action}')" class="px-2.5 py-1 rounded-md bg-white border border-pink-300 text-pink-700 font-bold hover:bg-pink-100 cursor-pointer">+ {call_to_action}</button>
        </div>
    </div>

    @if(session('error') || $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900 text-sm font-bold flex items-start gap-3 shadow-sm">
            <span class="text-xl shrink-0">⚠️</span>
            <div>
                <p class="font-extrabold">{{ session('error') ?? 'Please check the form for errors:' }}</p>
                @if($errors->any() && !session('error'))
                    <ul class="list-disc list-inside mt-1 font-semibold text-xs text-rose-700">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.games.vibe_stories.update', $story->id) : route('admin.games.vibe_stories.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Row 1: Key, Name, Sort Order --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-bold text-ink-800">Category Identifier Key *</label>
                <input type="text" name="category_key" value="{{ old('category_key', $story->category_key) }}" required placeholder="e.g. strong_connection" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <span class="text-[11px] text-gray-400">Unique slug, letters & underscores only.</span>
                @error('category_key')
                    <p class="text-xs text-rose-600 font-black mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-ink-800">Category Display Name *</label>
                <input type="text" name="category_name" value="{{ old('category_name', $story->category_name) }}" required placeholder="e.g. Strong Connection (80–100%)" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('category_name')
                    <p class="text-xs text-rose-600 font-black mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-ink-800">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $story->sort_order ?? 1) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
        </div>

        {{-- Row 2: Score Range --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 border border-gray-200">
            <div>
                <label class="block text-sm font-bold text-ink-800">Minimum Vibe Score (%) *</label>
                <input type="number" name="min_score" min="0" max="100" value="{{ old('min_score', $story->min_score ?? 80) }}" required class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <span class="text-[11px] text-gray-400">Inclusive lower bound (e.g. 80).</span>
                @error('min_score')
                    <p class="text-xs text-rose-600 font-black mt-1.5">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-ink-800">Maximum Vibe Score (%) *</label>
                <input type="number" name="max_score" min="0" max="100" value="{{ old('max_score', $story->max_score ?? 100) }}" required class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <span class="text-[11px] text-gray-400">Inclusive upper bound (e.g. 100).</span>
                @error('max_score')
                    <p class="text-xs text-rose-600 font-black mt-1.5">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Title Template --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Story Title Template *</label>
            <input type="text" name="title" value="{{ old('title', $story->title) }}" required placeholder="e.g. {groom_name} ❤️ {bride_name} — Strong Connection" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
        </div>

        {{-- Tagline Template --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Tagline / Subheading</label>
            <input type="text" name="tagline" value="{{ old('tagline', $story->tagline) }}" placeholder="e.g. Your Koodal Vibe: {score}% — Strong Connection!" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
        </div>

        {{-- Story Content --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Full Relationship Story Content (Tamil / Tanglish) *</label>
            <textarea id="storyContentField" name="story_content" rows="14" required placeholder="Type the beautiful couple story here. Separate paragraphs with double enter (blank line)..." class="mt-1 w-full rounded-xl border border-gray-300 p-4 text-sm leading-relaxed font-sans focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('story_content', $story->story_content) }}</textarea>
            <span class="text-[11px] text-gray-500 block mt-1">Separate paragraphs with a blank line. Bold text with **word**. Emojis and tokens are preserved!</span>
        </div>

        {{-- Call To Action Settings --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-ink-800">Call to Action (CTA) Button Text</label>
                <input type="text" name="call_to_action" value="{{ old('call_to_action', $story->call_to_action ?? '❤️ PREMIUM CALL') }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold text-pink-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-ink-800">CTA Redirect URL</label>
                <input type="text" name="cta_url" value="{{ old('cta_url', $story->cta_url ?? '/membership') }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
        </div>

        {{-- Status --}}
        <div class="flex items-center gap-3 pt-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="isActiveCheck" name="is_active" value="1" {{ old('is_active', $story->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            <label for="isActiveCheck" class="text-sm font-bold text-ink-800 cursor-pointer">Active and available for Vibe Meter story generation</label>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.games.vibe_stories.index') }}" class="rounded-xl border border-gray-300 px-6 py-2.5 text-sm font-semibold text-ink-600 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #E0245E, #B81B5C); color: #fff; padding: 10px 24px;">
                {{ $isEdit ? 'Save Changes' : 'Create Vibe Story' }}
            </button>
        </div>
    </form>
</div>

<script>
function insertToken(token) {
    const el = document.getElementById('storyContentField');
    if (!el) return;
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const text = el.value;
    el.value = text.substring(0, start) + token + text.substring(end);
    el.focus();
    el.selectionStart = el.selectionEnd = start + token.length;
}
</script>
@endsection
