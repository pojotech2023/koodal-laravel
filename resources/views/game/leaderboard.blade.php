@extends('layouts.app')

@section('title', 'Spot the Difference Leaderboard')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-black text-ink-900">Leaderboard</h1>
            <p class="mt-1 text-sm text-ink-500">Top Spot the Difference players ranked by wins and average score.</p>
        </div>
        <a href="{{ route('spot.matches.index') }}" class="rounded-xl border bg-white px-4 py-2 text-xs font-bold text-ink-700 hover:bg-slate-50" style="border-color:var(--border-default)">
            &larr; Back to Games
        </a>
    </div>

    <div class="rounded-3xl border bg-white shadow-md overflow-hidden" style="border-color:var(--border-default)">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-ink-500 border-b">
                <tr>
                    <th class="px-6 py-4">Rank</th>
                    <th class="px-6 py-4">Player</th>
                    <th class="px-6 py-4 text-center">Matches Played</th>
                    <th class="px-6 py-4 text-center">Total Wins</th>
                    <th class="px-6 py-4 text-right">Avg Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                @forelse($leaderboard as $index => $row)
                    <tr class="{{ $index < 3 ? 'bg-amber-50/30' : '' }}">
                        <td class="px-6 py-4">
                            @if($index === 0)
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-amber-400 text-xs font-black text-slate-900 shadow">1</span>
                            @elseif($index === 1)
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-300 text-xs font-black text-slate-900 shadow">2</span>
                            @elseif($index === 2)
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-amber-600 text-xs font-black text-white shadow">3</span>
                            @else
                                <span class="text-xs font-bold text-ink-400 pl-2">#{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-ink-900 flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-slate-900 text-white grid place-items-center text-xs font-bold">
                                {{ strtoupper(mb_substr($row->user?->name ?? 'P', 0, 1)) }}
                            </div>
                            {{ $row->user?->name ?? 'Unknown Player' }}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-ink-700">{{ $row->matches_played }}</td>
                        <td class="px-6 py-4 text-center font-bold text-emerald-600">{{ $row->wins }}</td>
                        <td class="px-6 py-4 text-right font-bold text-ink-900">{{ $row->avg_score }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-ink-400">
                            No finished matches yet. Be the first to play and claim top spot!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
