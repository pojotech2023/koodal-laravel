@extends('admin.layouts.admin')

@section('title', 'Manage Puzzles')

@section('admin-body')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">Spot the Difference Puzzles</h1>
            <p class="mt-1 text-sm text-ink-500">Manage 1v1 multiplayer game puzzles, image hotspots, and animations.</p>
        </div>
        <div>
            <a href="{{ route('admin.puzzles.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-gradient px-4 py-2.5 text-sm font-bold text-white shadow-glow transition-transform hover:-translate-y-0.5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create New Puzzle
            </a>
        </div>
    </div>

    <div class="rounded-2xl border bg-white shadow-sm" style="border-color:var(--border-default)">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                    <tr>
                        <th class="px-6 py-4">Preview</th>
                        <th class="px-6 py-4">Title</th>
                        <th class="px-6 py-4">Dimensions</th>
                        <th class="px-6 py-4">Difficulty</th>
                        <th class="px-6 py-4">Differences</th>
                        <th class="px-6 py-4">Time Limit</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($puzzles as $puzzle)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="{{ Storage::disk('public')->url($puzzle->original_image) }}" alt="Original" class="h-12 w-16 rounded-lg object-cover border shadow-sm">
                                    <img src="{{ Storage::disk('public')->url($puzzle->modified_image) }}" alt="Modified" class="h-12 w-16 rounded-lg object-cover border shadow-sm">
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-ink-900">{{ $puzzle->title }}</td>
                            <td class="px-6 py-4 text-xs font-mono text-ink-600">{{ $puzzle->width }} &times; {{ $puzzle->height }} px</td>
                            <td class="px-6 py-4">
                                <span class="capitalize inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                    {{ $puzzle->difficulty }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-ink-800">
                                {{ $puzzle->differences_count }}
                                @if($puzzle->differences_count < $minDifferences)
                                    <span class="text-xs text-rose-500 font-normal block">(Min {{ $minDifferences }} required to activate)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-ink-600">{{ $puzzle->time_limit_seconds }}s</td>
                            <td class="px-6 py-4">
                                @if($puzzle->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.puzzles.edit', $puzzle->id) }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold text-ink-700 hover:bg-slate-50" style="border-color:var(--border-default)">
                                        Edit / Hotspots
                                    </a>
                                    <form method="POST" action="{{ route('admin.puzzles.destroy', $puzzle->id) }}" onsubmit="return confirm('Delete this puzzle?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-ink-400">
                                No puzzles found. Click <strong>Create New Puzzle</strong> to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($puzzles->hasPages())
            <div class="border-t p-4" style="border-color:var(--border-default)">
                {{ $puzzles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
