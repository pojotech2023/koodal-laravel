{{-- $errors is shared into every view by ShareErrorsFromSession, part of
     the default 'web' middleware group — no controller has to pass it.

     `status` = a completed action; `error` = a refusal the user can act on
     (flashed by WebErrorResponse and the self-rendering domain exceptions);
     $errors = the validation bag. They stay three separate boxes because a
     409 belongs to the request, not to a field, and a refusal must never
     read like a success. --}}
@php
    $icons = [
        'ok' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'bad' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
    ];
@endphp

@if (session('status'))
    <div class="mx-auto w-full max-w-3xl px-4 pt-4 animate-fade-up" role="status" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['ok'] }}" /></svg>
            <p class="flex-1 font-medium">{{ session('status') }}</p>
            <button type="button" @click="show = false" class="text-emerald-700/70 hover:text-emerald-900" aria-label="Dismiss">&times;</button>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="mx-auto w-full max-w-3xl px-4 pt-4 animate-fade-up" role="alert" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['bad'] }}" /></svg>
            <p class="flex-1 font-medium">{{ session('error') }}</p>
            <button type="button" @click="show = false" class="text-red-700/70 hover:text-red-900" aria-label="Dismiss">&times;</button>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto w-full max-w-3xl px-4 pt-4 animate-fade-up" role="alert">
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['bad'] }}" /></svg>
            <div>
                <p class="mb-1 font-semibold">Please fix the following:</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
