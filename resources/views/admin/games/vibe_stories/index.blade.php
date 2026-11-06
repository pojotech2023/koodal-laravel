@extends('admin.layouts.admin')

@section('title', 'Koodal Vibe Meter Stories — Admin')

@section('admin-body')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">❤️ Koodal Vibe Meter Relationship Stories</h1>
            <p class="text-sm text-ink-500">Configure dynamic relationship story templates across Vibe percentage score categories.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                🎯 Questions
            </a>
            <a href="{{ route('admin.games.find_it.index') }}" class="rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">
                🔍 Find It
            </a>
            <a href="{{ route('admin.games.memory_snap.index') }}" class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-100">
                🧠 Memory Snap
            </a>
            <a href="{{ route('admin.flip-tiles.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                🧩 Flip Tiles
            </a>
            <a href="{{ route('admin.games.vibe_stories.create') }}" class="btn-primary" style="background: linear-gradient(135deg, #E0245E, #B81B5C); color: #fff;">
                + Add New Story
            </a>
        </div>
    </div>

    {{-- Story Placeholders Guide --}}
    <div class="rounded-2xl border border-pink-200 bg-pink-50/60 p-4 shadow-sm text-xs space-y-2">
        <div class="flex items-center gap-2 font-black text-pink-900">
            <span>💡</span>
            <span>Dynamic Placeholders in Story Content</span>
        </div>
        <p class="text-pink-800 leading-relaxed font-medium">
            You can use these placeholders in any story template; they are dynamically replaced with real game data & couple details:
        </p>
        <div class="flex flex-wrap gap-2 pt-1 font-mono text-[11px]">
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{groom_name}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{bride_name}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{score}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{category_name}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{observation_detail}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{memory_detail}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{teamwork_detail}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{compatibility_detail}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{favourite_scene}</span>
            <span class="px-2 py-1 rounded-md bg-white border border-pink-200 text-pink-700 font-bold">{call_to_action}</span>
        </div>
    </div>

    {{-- Stories Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-700">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-ink-500">
                    <tr>
                        <th class="px-6 py-3.5">Score Range</th>
                        <th class="px-6 py-3.5">Category Name</th>
                        <th class="px-6 py-3.5">Title Template</th>
                        <th class="px-6 py-3.5">CTA Button</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($stories as $story)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold shadow-sm
                                    {{ $story->min_score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                                       ($story->min_score >= 60 ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                                       ($story->min_score >= 40 ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                                       ($story->min_score >= 20 ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-rose-50 text-rose-700 border border-rose-200'))) }}">
                                    <span>🎯</span>
                                    <span>{{ $story->min_score }}% – {{ $story->max_score }}%</span>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-extrabold text-ink-900 block">{{ $story->category_name }}</span>
                                <span class="text-xs text-ink-400 font-mono">{{ $story->category_key }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-ink-800 line-clamp-1">{{ $story->title }}</span>
                                @if($story->tagline)
                                    <span class="text-xs text-ink-400 block line-clamp-1">{{ $story->tagline }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-pink-50 border border-pink-200 text-pink-700 font-bold text-xs">
                                    {{ $story->call_to_action ?: '❤️ PREMIUM CALL' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($story->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                <a href="{{ route('admin.games.vibe_stories.edit', $story->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 hover:bg-primary-100">
                                    ✏️ Edit
                                </a>
                                <form method="POST" action="{{ route('admin.games.vibe_stories.copy', $story->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-100 cursor-pointer">
                                        📋 Copy
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.games.vibe_stories.destroy', $story->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this story?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-ink-400">
                                No Vibe Story templates found. <a href="{{ route('admin.games.vibe_stories.create') }}" class="text-primary-600 font-bold underline">Create one now</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
