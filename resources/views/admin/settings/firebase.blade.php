@extends('admin.layouts.admin')

@section('title', 'Firebase Push Settings — ' . config('app.name') . ' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.settings.index') }}" class="text-sm font-medium text-pink-600 hover:underline">&larr; General Settings</a>
            </div>
            <h1 class="mt-1 font-serif text-3xl font-bold text-ink-900">Firebase & Push Notification Settings</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Configure Firebase Admin SDK (FCM HTTP v1), upload your secure service account JSON, configure sound and channel, and test push notifications.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.notification-logs.index') }}" class="rounded-lg border px-3.5 py-2 text-sm font-semibold hover:bg-white/50" style="border-color:var(--border-default)">
                📋 View Notification Logs
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Settings Form (Left 2 Columns) --}}
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('admin.firebase.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <section class="card p-6">
                    <h2 class="card-title text-lg font-bold text-ink-900">Firebase FCM Configuration</h2>
                    <p class="mt-1 text-xs" style="color:var(--text-muted)">
                        Credentials are stored securely in <code class="font-mono text-pink-700 bg-pink-50 px-1 py-0.5 rounded">storage/app/private/firebase</code> and excluded from version control.
                    </p>

                    <div class="mt-5 space-y-5">
                        {{-- Enable / Disable Toggle --}}
                        <div class="rounded-xl border p-4" style="border-color:var(--border-default); background-color: var(--bg-card)">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="block text-sm font-bold text-ink-900">Global Push Notifications</span>
                                    <span class="text-xs" style="color:var(--text-muted)">Master switch to enable or pause all outgoing FCM pushes.</span>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="hidden" name="firebase_push_enabled" value="0">
                                    <input type="checkbox" name="firebase_push_enabled" value="1" class="peer sr-only" @checked($pushEnabled)>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-pink-600 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Firebase Project ID --}}
                        <div>
                            <label for="firebase_project_id" class="block text-sm font-semibold text-ink-800">
                                Firebase Project ID
                            </label>
                            <input id="firebase_project_id" type="text" name="firebase_project_id"
                                   value="{{ old('firebase_project_id', $projectId) }}"
                                   placeholder="e.g. koodal-matrimony-prod"
                                   class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                                The unique Google Cloud / Firebase project identifier (e.g., from your Firebase console).
                            </p>
                        </div>

                        {{-- Service Account JSON Upload --}}
                        <div>
                            <label class="block text-sm font-semibold text-ink-800">
                                Service Account JSON File
                            </label>
                            <div class="mt-1.5 rounded-xl border border-dashed p-4 text-center" style="border-color:var(--border-default)">
                                @if ($hasCredentials && $credentialsDetails)
                                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Credentials Loaded (Project: {{ $credentialsDetails['project_id'] ?? 'Configured' }})
                                    </div>
                                    <p class="text-xs" style="color:var(--text-muted)">
                                        Service Account: <span class="font-mono">{{ $credentialsDetails['client_email'] ?? 'Private Key Active' }}</span>
                                    </p>
                                @else
                                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
                                        ⚠️ Service Account JSON Not Configured
                                    </div>
                                @endif
                                <input type="file" name="service_account_json" accept=".json,application/json"
                                       class="mt-2 block w-full text-xs file:mr-4 file:rounded-lg file:border-0 file:bg-pink-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-pink-700">
                                <p class="mt-1.5 text-[11px]" style="color:var(--text-muted)">
                                    Upload your Firebase Admin SDK service account JSON downloaded from Firebase Console &gt; Project Settings &gt; Service Accounts.
                                </p>
                            </div>
                        </div>

                        {{-- Notification Sound --}}
                        <div>
                            <label for="firebase_sound_name" class="block text-sm font-semibold text-ink-800">
                                Notification Sound Name
                            </label>
                            <input id="firebase_sound_name" type="text" name="firebase_sound_name"
                                   value="{{ old('firebase_sound_name', $soundName) }}"
                                   placeholder="default"
                                   class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                                Default is <span class="font-mono">default</span>. For custom app sound, enter sound asset name without extension (e.g. <span class="font-mono">notification_chime</span>).
                            </p>
                        </div>

                        {{-- Android Channel ID --}}
                        <div>
                            <label for="firebase_android_channel_id" class="block text-sm font-semibold text-ink-800">
                                Android Channel ID
                            </label>
                            <input id="firebase_android_channel_id" type="text" name="firebase_android_channel_id"
                                   value="{{ old('firebase_android_channel_id', $channelId) }}"
                                   placeholder="koodal_notifications"
                                   class="mt-1.5 w-full rounded-xl border px-3.5 py-2.5 text-sm" style="border-color:var(--border-default)">
                            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                                Used by the Flutter mobile app to register the high-priority Android notification channel.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4" style="border-color:var(--border-default)">
                        <button type="submit" class="rounded-xl bg-pink-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-pink-700 transition">
                            Save Firebase Settings
                        </button>
                    </div>
                </section>
            </form>
        </div>

        {{-- Test Notification Panel (Right Column) --}}
        <div>
            <section class="card p-6" x-data="{ targetType: 'user' }">
                <h2 class="card-title text-lg font-bold text-ink-900">Send Test Push</h2>
                <p class="mt-1 text-xs" style="color:var(--text-muted)">
                    Verify your Firebase credentials and device delivery instantly.
                </p>

                <form method="POST" action="{{ route('admin.firebase.test') }}" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Target Type</label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <button type="button" @click="targetType = 'user'"
                                    :class="targetType === 'user' ? 'bg-pink-600 text-white font-bold' : 'bg-slate-100 text-ink-700 hover:bg-slate-200'"
                                    class="rounded-lg py-2 text-xs transition">
                                Registered Member
                            </button>
                            <button type="button" @click="targetType = 'token'"
                                    :class="targetType === 'token' ? 'bg-pink-600 text-white font-bold' : 'bg-slate-100 text-ink-700 hover:bg-slate-200'"
                                    class="rounded-lg py-2 text-xs transition">
                                Raw FCM Token
                            </button>
                        </div>
                        <input type="hidden" name="test_target_type" :value="targetType">
                    </div>

                    {{-- Member Dropdown --}}
                    <div x-show="targetType === 'user'">
                        <label for="test_user_id" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Select Member</label>
                        <select id="test_user_id" name="user_id" class="mt-1.5 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
                            @forelse ($usersWithTokens as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->profile_code }}) - {{ $u->mobile ?: 'No mobile' }}</option>
                            @empty
                                <option value="">No members with active tokens found</option>
                            @endforelse
                        </select>
                        <p class="mt-1 text-[11px]" style="color:var(--text-muted)">Showing members with registered tokens.</p>
                    </div>

                    {{-- Token Input --}}
                    <div x-cloak x-show="targetType === 'token'">
                        <label for="test_fcm_token" class="block text-xs font-bold uppercase tracking-wider text-ink-700">FCM Device Token</label>
                        <textarea id="test_fcm_token" name="fcm_token" rows="3" placeholder="Paste FCM registration token..."
                                  class="mt-1.5 w-full rounded-xl border px-3 py-2 font-mono text-xs" style="border-color:var(--border-default)"></textarea>
                    </div>

                    <div>
                        <label for="test_title" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Notification Title</label>
                        <input id="test_title" type="text" name="title" value="Koodal Matrimony Test Push 🔔"
                               class="mt-1.5 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)" required>
                    </div>

                    <div>
                        <label for="test_body" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Notification Body</label>
                        <textarea id="test_body" name="body" rows="2"
                                  class="mt-1.5 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)" required>Your Firebase push configuration is working perfectly!</textarea>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-ink-900 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-ink-800 transition">
                        🚀 Send Test Notification
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
