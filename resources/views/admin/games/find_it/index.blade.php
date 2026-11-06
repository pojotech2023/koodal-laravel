@extends('admin.layouts.admin')

@section('title', 'Koodal Find It Challenges — Admin')

@section('admin-body')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">🔍 Koodal Find It Challenges</h1>
            <p class="text-sm text-ink-500">Manage visual spot-the-difference challenges and hotspots.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-ink-600 hover:bg-gray-50">
                🎯 Question Bank
            </a>
            <a href="{{ route('admin.games.memory_snap.index') }}" class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-100">
                🧠 Memory Snap
            </a>
            <a href="{{ route('admin.flip-tiles.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                🧩 Flip Tiles
            </a>
            <a href="{{ route('admin.games.vibe_stories.index') }}" class="rounded-xl border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-pink-700 hover:bg-pink-100">
                ❤️ Vibe Stories
            </a>
            <a href="{{ route('admin.games.find_it.create') }}" class="btn-primary">
                + Create Challenge
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @forelse($challenges as $c)
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-ink-900">{{ $c->title }}</h3>
                        <p class="text-xs text-ink-500">{{ is_array($c->differences) ? count($c->differences) : 0 }} Spot differences configured</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.games.find_it.edit', $c->id) }}" class="text-xs font-semibold text-cyan-700 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.games.find_it.destroy', $c->id) }}" onsubmit="return confirm('Delete challenge?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
                <div class="p-4 grid grid-cols-2 gap-4 bg-gray-50">
                    <div class="aspect-video bg-slate-900 rounded-xl overflow-hidden relative border border-gray-300 flex items-center justify-center">
                        <span class="absolute top-2 left-2 z-10 bg-black/75 text-white text-[10px] font-bold px-2 py-0.5 rounded border border-white/20">Image A (Original)</span>
                        @if($c->image_a)
                            <img src="{{ $c->image_a }}" alt="{{ $c->title }} A" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs font-semibold text-gray-400">No Image</span>
                        @endif
                    </div>
                    <div class="aspect-video bg-slate-900 rounded-xl overflow-hidden relative border border-gray-300 flex items-center justify-center">
                        <span class="absolute top-2 left-2 z-10 bg-black/75 text-white text-[10px] font-bold px-2 py-0.5 rounded border border-white/20">Image B (Differences)</span>
                        @if($c->image_b)
                            <img src="{{ $c->image_b }}" alt="{{ $c->title }} B" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs font-semibold text-gray-400">No Image</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 rounded-2xl border border-dashed border-gray-300 p-12 text-center text-ink-400">
                No Find It challenges created yet.
            </div>
        @endforelse
    </div>

    @if($challenges->hasPages())
        <div class="pt-4">
            {{ $challenges->links() }}
        </div>
    @endif
</div>
@endsection
