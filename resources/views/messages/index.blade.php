@extends('layouts.member')

@section('title', ($active?->participant?->profile ? $active->participant->profile->displayNameFor($viewer).' — ' : '').'Messages — '.config('app.name'))

{{-- Read by resources/js/chat.js — Reverb connection details, built server
     side from config('reverb.*') rather than a GET /chat/config round trip,
     since this page is already a server response (docs/05-website-guide.md
     §7). Only present when a thread is open; the list-only page has nothing
     to subscribe to. --}}
@if ($active)
    {{-- Built as a variable, not inlined into @json(...) directly — Blade's
         @json compiler naively explode(',')s its raw argument text to split
         value/options/depth, so an inline array literal with more than two
         top-level commas gets silently truncated (everything past the third
         comma dropped, `[` left unclosed). One variable sidesteps that. --}}
    @php
        $chatConfig = [
            'key' => config('reverb.apps.apps.0.key'),
            'host' => config('reverb.apps.apps.0.options.host'),
            'port' => (int) config('reverb.apps.apps.0.options.port'),
            'scheme' => config('reverb.apps.apps.0.options.scheme'),
            'authEndpoint' => route('broadcasting.auth'),
            'conversationId' => $active->id,
            'pollUrl' => route('messages.show', $active),
        ];
    @endphp
    @push('scripts')
        <script>
            window.__chatConfig = @json($chatConfig);
        </script>
        @vite('resources/js/chat.js')
    @endpush
@endif

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Messages</h1>

        <div class="mt-6 flex overflow-hidden rounded-xl border shadow-sm" style="border-color:var(--border-default);background-color:var(--bg-surface);height:calc(100vh - 14rem);min-height:28rem">
            {{-- List pane --}}
            <div class="flex w-full flex-col border-r sm:w-80 {{ $active ? 'hidden sm:flex' : 'flex' }}" style="border-color:var(--border-default)">
                <div class="flex-1 overflow-y-auto">
                    @forelse ($conversations as $conversation)
                        @php
                            $participant = $conversation->participant;
                            $profile = $participant?->profile;
                            $unread = $conversation->unreadCountFor($viewer->id);
                        @endphp
                        <a
                            href="{{ route('messages.show', $conversation) }}"
                            class="flex items-center gap-3 border-b p-3 hover:bg-primary-50 {{ $active?->id === $conversation->id ? 'bg-primary-50' : '' }}"
                            style="border-color:var(--border-default)"
                        >
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-gray-100">
                                @if ($profile?->primaryPhoto)
                                    @php $blurred = ! app(\App\Services\Profile\ProfileVisibility::class)->canViewPhotos($viewer, $participant); @endphp
                                    <img src="{{ $profile->primaryPhoto->urlFor($blurred ? 'blurred' : 'thumbnail') }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm font-medium">
                                        {{ $profile ? $profile->displayNameFor($viewer) : 'Deleted account' }}
                                    </p>
                                    @if ($conversation->last_message_at)
                                        <span class="shrink-0 text-xs" style="color:var(--text-muted)">{{ $conversation->last_message_at->diffForHumans(null, true) }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs" style="color:var(--text-muted)">
                                        @if ($conversation->last_message_preview)
                                            {{ $conversation->last_message_sender_id === $viewer->id ? 'You: ' : '' }}{{ $conversation->last_message_preview }}
                                        @else
                                            Say hello
                                        @endif
                                    </p>
                                    @if ($unread > 0)
                                        <span class="shrink-0 rounded-full bg-primary-500 px-1.5 py-0.5 text-xs font-medium text-white">{{ $unread }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-sm" style="color:var(--text-muted)">
                            No conversations yet. Message someone you've connected with from their profile.
                        </p>
                    @endforelse
                </div>

                @if ($conversations->hasPages())
                    <div class="border-t p-2" style="border-color:var(--border-default)">
                        {{ $conversations->onEachSide(0)->links() }}
                    </div>
                @endif
            </div>

            {{-- Thread pane --}}
            <div class="flex w-full flex-1 flex-col {{ $active ? 'flex' : 'hidden sm:flex' }}">
                @if (! $active)
                    <div class="flex flex-1 items-center justify-center p-6 text-center text-sm" style="color:var(--text-muted)">
                        Select a conversation to see the thread.
                    </div>
                @else
                    @php
                        $participant = $active->participant;
                        $profile = $participant?->profile;
                    @endphp

                    <div class="flex items-center justify-between gap-3 border-b p-3" style="border-color:var(--border-default)">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('messages.index') }}" class="sm:hidden" aria-label="Back to conversations">&larr;</a>

                            @if ($profile)
                                <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($participant)) }}" class="flex items-center gap-3 hover:text-primary-600">
                                    <span class="font-medium">{{ $profile->displayNameFor($viewer) }}</span>
                                </a>
                                @if (($active->participant_online ?? false))
                                    <span class="text-xs text-primary-600">&bull; Online</span>
                                @endif
                            @else
                                <span class="font-medium" style="color:var(--text-muted)">Deleted account</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('messages.archive', $active) }}">
                            @csrf
                            <button type="submit" class="rounded-md border px-3 py-1.5 text-xs font-medium" style="border-color:var(--border-default)">Archive</button>
                        </form>
                    </div>

                    {{-- Newest message is at the bottom; this is a page reload,
                         not a live scroll — resources/js/chat.js appends new
                         rows in place once the socket (or the poll fallback)
                         confirms one arrived, without needing a full reload. --}}
                    <div id="chat-thread" class="flex-1 space-y-3 overflow-y-auto p-4">
                        @if ($hasOlder)
                            <div class="text-center">
                                <a href="{{ route('messages.show', $active) }}?before_id={{ $messages->first()?->id }}" class="text-xs font-medium text-primary-600 hover:underline">
                                    Load earlier messages
                                </a>
                            </div>
                        @endif

                        @forelse ($messages as $message)
                            @php
                                $mine = $message->sender_id === $viewer->id;
                                $attachment = $message->type === 'image' ? $message->attachmentPayload() : null;
                            @endphp
                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}" data-mine="{{ $mine ? '1' : '0' }}" data-body="{{ $message->body }}">
                                <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm {{ $mine ? 'bg-primary-500 text-white' : 'border' }}" style="{{ $mine ? '' : 'border-color:var(--border-default)' }}">
                                    @if ($attachment)
                                        <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener noreferrer" class="block my-1">
                                            <img src="{{ $attachment['url'] }}" alt="Photo" class="max-w-xs max-h-72 rounded-md object-cover shadow-sm hover:opacity-95" loading="lazy">
                                        </a>
                                    @else
                                        <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                                    @endif
                                    <p class="mt-1 text-right text-xs {{ $mine ? 'text-white/70' : '' }}" style="{{ $mine ? '' : 'color:var(--text-muted)' }}">
                                        {{ $message->created_at->format('g:i A') }}{{ $mine && $message->read_at ? ' · Read' : '' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-sm" style="color:var(--text-muted)">Say hello — this is the start of your conversation.</p>
                        @endforelse
                    </div>

                    @if (! ($canAccessChat ?? true))
                        {{-- Chat Locked Banner for Free Users who have not completed all 4 games --}}
                        <div class="border-t p-6 bg-gradient-to-b from-pink-50/60 to-rose-50/30 text-center" style="border-color:var(--border-default)">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm text-2xl border border-pink-200">
                                🔒
                            </div>
                            <h3 class="mt-3 text-sm font-bold text-ink-900">Chat is locked for Free Members</h3>
                            <p class="mt-1 text-xs text-ink-600 max-w-md mx-auto">
                                You can access direct chat with <strong class="text-pink-700">{{ $active->participant?->name ?? 'this member' }}</strong> only after playing all 4 games together, or by upgrading to a Premium membership.
                            </p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                @if (isset($otherUser) && $otherUser)
                                    <a href="{{ route('games.hub', $otherUser->id) }}"
                                       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black text-white shadow-md transition hover:scale-105 active:scale-95"
                                       style="background: linear-gradient(135deg, #E0245E, #B81B5C);">
                                        <span class="text-sm">🎮</span>
                                        <span>Play All Games to Unlock Chat</span>
                                    </a>
                                @endif
                                <a href="{{ route('membership.index') }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-pink-300 bg-white px-4 py-2.5 text-xs font-bold text-pink-700 hover:bg-pink-50 transition shadow-xs">
                                    <span>⭐ Upgrade Plan</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="border-t p-3" style="border-color:var(--border-default)"
                             x-data="{
                                 gender: '{{ Auth::guard('web')->user()?->gender === 'female' ? 'female' : 'male' }}',
                                 maleTemplates: [
                                     'Hi! I liked your profile and would like to connect.',
                                     'Hello! Hope you are having a wonderful day.',
                                     'Hi, could we connect with each other\'s families?',
                                     'Hello, are you open to having a conversation?',
                                     'Hi! Please feel free to view my full profile.',
                                     'Namaste! Interested in taking this forward.'
                                 ],
                                 femaleTemplates: [
                                     'Hi! Thank you for showing interest in my profile.',
                                     'Hello! I saw your profile and would love to connect.',
                                     'Hi! Are your parents also managing your profile?',
                                     'Hello! Hope you are doing well today.',
                                     'Hi! Feel free to connect with my family as well.',
                                     'Namaste! Would love to know more about you.'
                                 ],
                                 setMessage(text) {
                                     const textarea = this.$refs.messageBody;
                                     if (textarea) {
                                         textarea.value = text;
                                         textarea.focus();
                                     }
                                 }
                             }">
                            
                            {{-- Quick Messages Pill Tray --}}
                            <div class="mb-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-ink-500 uppercase tracking-wider flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Quick messages:
                                    </span>
                                    <div class="inline-flex rounded-lg border bg-white p-0.5 text-xs shadow-sm" style="border-color:var(--border-default)">
                                        <button type="button" @click="gender = 'male'"
                                                :class="gender === 'male' ? 'bg-primary-500 text-white font-semibold' : 'text-ink-600 hover:text-primary-600'"
                                                class="rounded-md px-2.5 py-0.5 transition-colors">
                                            For Groom (Male)
                                        </button>
                                        <button type="button" @click="gender = 'female'"
                                                :class="gender === 'female' ? 'bg-primary-500 text-white font-semibold' : 'text-ink-600 hover:text-primary-600'"
                                                class="rounded-md px-2.5 py-0.5 transition-colors">
                                            For Bride (Female)
                                        </button>
                                    </div>
                                </div>

                                <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
                                    <template x-for="(tpl, idx) in (gender === 'female' ? femaleTemplates : maleTemplates)" :key="idx">
                                        <button type="button" @click="setMessage(tpl)"
                                                class="shrink-0 rounded-full border border-primary-200 bg-primary-50/50 px-3 py-1 text-xs font-medium text-ink-800 transition hover:border-primary-400 hover:bg-primary-100 hover:text-primary-800 active:scale-95 shadow-sm">
                                            <span x-text="tpl"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('messages.store', $active) }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="type" value="text">
                                <input type="hidden" name="client_message_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <textarea x-ref="messageBody" name="body" rows="1" required maxlength="{{ config('matrimony.chat.message_max_length') }}" placeholder="Type a message…" class="flex-1 resize-none rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                                <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Send</button>
                            </form>

                            <form method="POST" action="{{ route('messages.store', $active) }}" enctype="multipart/form-data" class="mt-2 flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="type" value="image">
                                <input type="hidden" name="client_message_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <input type="file" name="attachment" accept="image/*" required class="text-xs">
                                <button type="submit" class="rounded-md border px-3 py-1.5 text-xs font-medium" style="border-color:var(--border-default)">Send photo</button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if ($active)
        <x-floating-chat />
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var partnerName = @json($active->participant?->profile ? $active->participant->profile->displayNameFor($viewer) : ($active->participant?->name ?? 'Partner'));
            var pollUrl = @json(route('messages.show', $active));
            var storeUrl = @json(route('messages.store', $active));
            var csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

            function syncDomThreadToCelebration() {
                var thread = document.getElementById('chat-thread');
                if (!thread || !window.syncKoodalChatMessages) return;
                var nodes = thread.querySelectorAll('[data-message-id]');
                var mapped = [];
                nodes.forEach(function (el) {
                    mapped.push({
                        id: el.getAttribute('data-message-id'),
                        message: el.getAttribute('data-body') || '',
                        mine: el.getAttribute('data-mine') === '1',
                        sender_name: el.getAttribute('data-mine') === '1' ? 'You' : partnerName
                    });
                });
                window.syncKoodalChatMessages(mapped, 0, partnerName);
            }

            async function pollConversationThread() {
                try {
                    var res = await fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) return;
                    var html = await res.text();
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.getElementById('chat-thread');
                    var current = document.getElementById('chat-thread');
                    if (fresh && current) {
                        var wasAtBottom = current.scrollTop + current.clientHeight >= current.scrollHeight - 40;
                        current.innerHTML = fresh.innerHTML;
                        if (wasAtBottom) {
                            current.scrollTop = current.scrollHeight;
                        }
                        syncDomThreadToCelebration();
                    }
                } catch (e) {}
            }

            setTimeout(syncDomThreadToCelebration, 200);
            setInterval(pollConversationThread, 2500);

            window.addEventListener('koodal-quick-chat-send', function (e) {
                if (!e.detail || !e.detail.message) return;
                var fd = new FormData();
                fd.append('_token', csrf);
                fd.append('type', 'text');
                fd.append('client_message_id', 'quick-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9));
                fd.append('body', e.detail.message);

                fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: fd
                }).then(function () {
                    pollConversationThread();
                });
            });
        });
        </script>
    @endif
@endsection
