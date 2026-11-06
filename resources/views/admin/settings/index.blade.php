@extends('admin.layouts.admin')

@section('title', 'Settings — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Settings</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Runtime knobs, changed without a deploy. Saved in one transaction — if anything is rejected, nothing is written.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.firebase.index') }}" class="rounded-lg bg-pink-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-pink-700 transition">
                🔔 Firebase Push Settings
            </a>
            <a href="{{ route('admin.notification-logs.index') }}" class="rounded-lg border px-3.5 py-2 text-sm font-semibold hover:bg-white/50" style="border-color:var(--border-default)">
                📋 Notification Logs
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        @forelse ($groups as $group => $settings)
            <section class="card p-5">
                {{-- The section headings are `settings.group` values, not a
                     hardcoded list: a new row lands in the right section with
                     no edit to this view. --}}
                <h2 class="card-title">{{ str_replace('_', ' ', ucfirst($group)) }}</h2>

                <div class="mt-3 space-y-4">
                    @foreach ($settings as $setting)
                        <div>
                            <label for="setting-{{ $setting->key }}" class="block text-sm font-medium">
                                {{ str_replace(['_', '.'], ' ', ucfirst($setting->key)) }}
                                <span class="ml-1 font-mono text-xs font-normal" style="color:var(--text-muted)">{{ $setting->key }}</span>
                                @if ($setting->is_public)
                                    {{-- Worth flagging on the row: a public
                                         setting is served to signed-out
                                         visitors by GET /settings/public. --}}
                                    <span class="ml-1 rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">Public</span>
                                @endif
                            </label>

                            @if ($setting->type === 'bool')
                                {{-- Hidden 0 first: an unticked checkbox
                                     submits nothing, and a key absent from the
                                     payload reads as "unchanged" — so without
                                     this a toggle could be switched on and
                                     never off. --}}
                                <label class="mt-1 flex items-center gap-2 text-sm">
                                    <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                    <input id="setting-{{ $setting->key }}" type="checkbox" name="settings[{{ $setting->key }}]" value="1"
                                           @checked($setting->typed())
                                           class="rounded border" style="border-color:var(--border-default)">
                                    Enabled
                                </label>

                            @elseif ($setting->type === 'int')
                                <input id="setting-{{ $setting->key }}" type="number" name="settings[{{ $setting->key }}]"
                                       value="{{ $setting->typed() }}"
                                       class="mt-1 w-48 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">

                            @elseif ($setting->type === 'json')
                                <textarea id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="4"
                                          class="mt-1 w-full rounded-md border px-3 py-2 font-mono text-xs" style="border-color:var(--border-default)">{{ $setting->value }}</textarea>
                                <p class="mt-1 text-xs" style="color:var(--text-muted)">
                                    Must parse as JSON. A stray comma here would otherwise read as “not set” everywhere this key is used.
                                </p>

                            @else
                                <input id="setting-{{ $setting->key }}" type="text" name="settings[{{ $setting->key }}]"
                                       value="{{ $setting->value }}"
                                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
                No settings rows. `SettingsSeeder` normally creates them.
            </p>
        @endforelse

        @can('settings.manage')
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                Save settings
            </button>
        @else
            <p class="text-sm" style="color:var(--text-muted)">
                You can read these but not change them — that needs <span class="font-mono">settings.manage</span>.
            </p>
        @endcan
    </form>
@endsection
