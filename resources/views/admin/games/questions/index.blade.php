@extends('admin.layouts.admin')

@section('title', 'Koodal Sync Question Bank — Admin')

@section('admin-body')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">🎯 Koodal Sync Question Bank</h1>
            <p class="text-sm text-ink-500">Manage compatibility questions (300+ questions bank) for profile matching.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.games.find_it.index') }}" class="rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">
                🔍 Find It Challenges
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
            <a href="{{ route('admin.games.questions.create') }}" class="btn-primary">
                + Add New Question
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.games.questions.index') }}" class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search questions in English or Tamil..." class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div class="w-full md:w-64">
                <select name="category" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">All Categories ({{ count($categories) }})</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-ink-800 px-5 py-2 text-sm font-semibold text-white hover:bg-ink-900">Filter</button>
                @if(request('search') || request('category'))
                    <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-ink-600 hover:bg-gray-50">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Questions Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-700">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-ink-500">
                    <tr>
                        <th class="px-6 py-3.5">#</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Question (English / Tamil)</th>
                        <th class="px-6 py-3.5">Options Count</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($questions as $q)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-ink-400">#{{ $q->id }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 border border-primary-200">
                                    {{ $q->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-ink-900">{{ $q->question_text_en }}</div>
                                @if($q->question_text_ta)
                                    <div class="text-xs text-ink-500 mt-0.5">{{ $q->question_text_ta }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-ink-600">
                                {{ is_array($q->options) ? count($q->options) : 0 }} choices
                            </td>
                            <td class="px-6 py-4">
                                @if($q->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                                        ● Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.games.questions.edit', $q->id) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:bg-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.games.questions.destroy', $q->id) }}" onsubmit="return confirm('Delete this question?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-ink-400">
                                No questions found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($questions->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
