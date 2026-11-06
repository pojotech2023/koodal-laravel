@extends('layouts.app')

@section('title', 'Join Game — Flip the Tiles')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/flip-game.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 text-3xl font-black mb-4 shadow-sm">
                💌
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">You are Invited!</h1>
            <p class="mt-2 text-sm text-slate-600 font-semibold">
                {{ $game->p1_name }} invited you to play Flip the Tiles 1v1!
            </p>
        </div>

        {{-- Flirty Secret Goal Banner --}}
        <div class="mb-6 rounded-2xl p-4 border-2 border-pink-200 shadow-sm text-center"
             style="background: linear-gradient(135deg, #FFF0F5 0%, #FDF2F8 100%);">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-600 text-white text-[10px] font-black uppercase tracking-wider mb-1.5">
                <span>💘</span><span>Secret Love Rule</span>
            </div>
            <p class="text-xs sm:text-sm font-extrabold text-pink-900 leading-relaxed">
                "The real goal here? Help your partner win & steal their heart! 😉💖 Match the tile they open to gift them the point!"
            </p>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-100">
            <form action="{{ route('flip.join.store', ['code' => $game->code]) }}" method="POST" class="space-y-6" onsubmit="const b = document.getElementById('join-submit-btn'); if(b){ b.innerHTML = '<span>⏳</span><span>Joining...</span>'; b.style.opacity = '0.7'; }">
                @csrf
                <div>
                    <label for="nickname" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Your Nickname (Optional)
                    </label>
                    <input type="text"
                           name="nickname"
                           id="nickname"
                           maxlength="30"
                           value="{{ auth()->user()?->name ?? '' }}"
                           placeholder="e.g. Bride, Rahul..."
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                </div>

                <button type="submit"
                        id="join-submit-btn"
                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-extrabold text-sm uppercase tracking-wider shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <span>🚀</span>
                    <span>Accept & Join</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
