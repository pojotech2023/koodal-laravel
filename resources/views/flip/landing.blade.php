@extends('layouts.app')

@section('title', 'Flip the Tiles — 1v1 Online Memory Game')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/flip-game.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange-100 text-orange-600 text-3xl font-black mb-4 shadow-sm">
                🧩
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Flip the Tiles</h1>
            <p class="mt-2 text-sm text-slate-600 font-medium">1v1 Online Memory-Match Game for Groom & Bride</p>
        </div>

        {{-- Flirty Secret Goal Banner --}}
        <div class="mb-6 rounded-2xl p-4 border-2 border-pink-200 shadow-sm text-center"
             style="background: linear-gradient(135deg, #FFF0F5 0%, #FDF2F8 100%);">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-600 text-white text-[10px] font-black uppercase tracking-wider mb-1.5">
                <span>💘</span><span>Secret Love Rule</span>
            </div>
            <p class="text-xs sm:text-sm font-extrabold text-pink-900 leading-relaxed">
                "Winning the game is cute, but making your partner win is how you steal their heart! 😉✨ Match their tile to gift them the point!"
            </p>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-100">
            @if(session('error'))
                <div class="mb-6 rounded-2xl bg-red-50 p-4 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(!$isAvailable)
                <div class="rounded-2xl bg-amber-50 p-5 border border-amber-200 text-amber-800 text-center space-y-3">
                    <div class="text-2xl">🚫</div>
                    <h3 class="font-bold text-sm">Game is not available right now</h3>
                    <p class="text-xs text-amber-700">Minimum 8 active tile images are required in the admin panel to start a game. Currently available: {{ $activeCount }}.</p>
                </div>
            @else
                <form action="{{ route('flip.create') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="nickname" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Your Nickname (Optional)
                        </label>
                        <input type="text"
                               name="nickname"
                               id="nickname"
                               maxlength="30"
                               placeholder="e.g. Player 1, Groom, Priya..."
                               class="w-full px-4 py-3.5 rounded-xl border border-slate-300 text-slate-900 text-sm font-medium focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none">
                    </div>

                    <button type="submit"
                            class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-orange-500 via-pink-500 to-purple-600 text-white font-black text-sm uppercase tracking-wider shadow-lg hover:from-orange-600 hover:to-purple-700 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span>✨</span>
                        <span>Create Game & Invite Partner</span>
                    </button>
                </form>
            @endif

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500 font-medium leading-relaxed">
                    Play live 1v1 memory tile match with your partner. Flip pairs, score points, and chat live in real-time!
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
