<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Read by resources/js/checkout.js — the Razorpay sheet's `name` field
         is the merchant name, not the plan being bought, and checkout.js has
         no server-rendered context of its own to pull it from otherwise. --}}
    <meta name="app-name" content="{{ config('app.name') }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', "India's trusted matrimonial platform — verified profiles, real conversations, real marriages.")">
    {{-- layouts.member overrides this to noindex, nofollow unconditionally
         — docs/05-website-guide.md §3. A public page opts into noindex per
         view with @section('robots', 'noindex, nofollow'); it can only ever
         add noindex here, never clear one set by its layout. --}}
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta name="theme-color" content="#B81B5C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Left-side custom scrollbar for all dropdowns & checklist panels */
        .kg-left-scroll {
            max-height: 220px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            direction: rtl !important;
            scrollbar-width: thin;
            scrollbar-color: #db2777 #fdf2f8;
        }
        .kg-left-scroll > .kg-left-scroll-inner,
        .kg-left-scroll > * {
            direction: ltr !important;
            text-align: left !important;
        }
        .kg-left-scroll::-webkit-scrollbar {
            width: 7px;
        }
        .kg-left-scroll::-webkit-scrollbar-track {
            background: #fdf2f8;
            border-radius: 8px;
        }
        .kg-left-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ec4899, #be185d);
            border-radius: 8px;
        }
        .kg-left-scroll::-webkit-scrollbar-thumb:hover {
            background: #9d174d;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col font-sans antialiased" style="background-color:var(--bg-page);color:var(--text-primary)">
    {{-- Layouts that have their own chrome (nav, sidebar, split panel) declare
         @section('flash_inline') and place <x-flash /> themselves, so the
         message lands inside the page instead of pushing the whole layout down. --}}
    @unless (View::hasSection('flash_inline'))
        <x-flash />
    @endunless

    @yield('content')

    @stack('scripts')

    <script>
        // Refresh CSRF token and keep session alive every 10 minutes so forms never expire
        setInterval(function () {
            fetch('{{ route("csrf.keepalive") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.csrf_token) {
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.csrf_token);
                    document.querySelectorAll('input[name="_token"]').forEach(function (el) {
                        el.value = data.csrf_token;
                    });
                }
            })
            .catch(function () {});
        }, 10 * 60 * 1000);
    </script>
    @auth('web')
    <div id="globalGameInviteContainer" style="position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; max-width: 400px; width: calc(100% - 40px); pointer-events: none;"></div>

    <script>
        (function() {
            function sendPresencePing() {
                var csrf = document.querySelector('meta[name="csrf-token"]');
                var token = csrf ? csrf.getAttribute('content') : '';
                fetch('{{ route("presence.ping") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(function() {});
            }
            sendPresencePing();
            setInterval(sendPresencePing, 25000);

            // ───────────── Global Game Invite Polling & Toast Banner ─────────────
            var shownInviteIds = {};

            function pollGlobalGameInvites() {
                fetch('/games/invites/pending', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    // Check for accepted invite redirect when sender gets accepted
                    if (d.accepted_redirect) {
                        var target = d.accepted_redirect.replace(/^https?:\/\/[^\/]+/, '');
                        var currentLoc = window.location.pathname + window.location.search;
                        if (target && target !== currentLoc && target !== window.location.pathname) {
                            window.location.href = target;
                            return;
                        }
                    }

                    if (!d.invites || !d.invites.length) return;

                    var container = document.getElementById('globalGameInviteContainer');
                    if (!container) return;

                    d.invites.forEach(function(inv) {
                        if (shownInviteIds[inv.id]) return;
                        shownInviteIds[inv.id] = true;

                        var card = document.createElement('div');
                        card.id = 'invite-card-' + inv.id;
                        card.style.cssText = 'pointer-events: auto; background: linear-gradient(135deg, #075E54, #128C7E); color: #ffffff; padding: 16px 18px; border-radius: 20px; border: 2px solid #25D366; box-shadow: 0 20px 40px rgba(0,0,0,0.35); font-family: inherit; animation: globalPopIn 0.4s cubic-bezier(0.2, 1.2, 0.3, 1) both; transition: all 0.3s;';

                        var title = (inv.game_type === 'find_it') ? '🔍 Koodal Find It' : ((inv.game_type === 'flip') ? '🃏 Flip The Tiles' : ((inv.game_type === 'memory_snap') ? '📸 Memory Snap' : (inv.game_type === 'sync_offline' ? '🎯 Compatibility Test' : '🎯 Live Compatibility Test')));
                        var bodyText = inv.custom_message
                            ? inv.custom_message
                            : ((inv.sender_name || 'Partner') + ' wants to play ' + title + ' with you right now! 💕');
                        var acceptLabel = inv.is_offline_waiting ? '📝 Answer Questions' : '🚀 Accept & Join';

                        card.innerHTML =
                            '<div style="display:flex; align-items:center; justify-between; gap:12px; margin-bottom:8px;">' +
                                '<div style="display:flex; align-items:center; gap:8px;">' +
                                    '<span style="font-size:22px; animation: globalBob 1.2s infinite ease-in-out;">💌</span>' +
                                    '<div>' +
                                        '<div style="font-weight:900; font-size:14px; line-height:1.2;">' + title + '</div>' +
                                        '<div style="font-size:11px; font-weight:700; color:#A7F3D0;">Challenge from ' + (inv.sender_name || 'Partner') + '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div style="font-size:12px; font-weight:800; color:#E6FFFA; margin-bottom:12px;">' + bodyText + '</div>' +
                            '<div style="display:flex; gap:8px;">' +
                                '<button type="button" id="accept-btn-' + inv.id + '" style="flex:1; padding:9px 12px; border-radius:12px; border:none; background:#25D366; color:#054740; font-size:12px; font-weight:900; cursor:pointer; shadow:0 4px 12px rgba(37,211,102,0.4);">' + acceptLabel + '</button>' +
                                '<button type="button" id="decline-btn-' + inv.id + '" style="padding:9px 14px; border-radius:12px; border:1px solid rgba(255,255,255,0.4); background:rgba(0,0,0,0.2); color:#ffffff; font-size:12px; font-weight:800; cursor:pointer;">Decline</button>' +
                            '</div>';

                        container.appendChild(card);

                        document.getElementById('accept-btn-' + inv.id).addEventListener('click', function() {
                            var btn = this;
                            btn.innerHTML = '⏳ Joining...';
                            btn.disabled = true;
                            var csrf = document.querySelector('meta[name="csrf-token"]');
                            var token = csrf ? csrf.getAttribute('content') : '';

                            fetch('/games/invites/' + inv.id + '/accept', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(res) {
                                return res.json().catch(function() {
                                    return { redirect: '/games/invites/' + inv.id + '/accept' };
                                });
                            })
                            .then(function(data) {
                                if (data && data.redirect) {
                                    var target = data.redirect.replace(/^https?:\/\/[^\/]+/, '');
                                    window.location.href = target || data.redirect;
                                } else {
                                    window.location.href = '/games/invites/' + inv.id + '/accept';
                                }
                            })
                            .catch(function(err) {
                                console.error('Accept invite error:', err);
                                window.location.href = '/games/invites/' + inv.id + '/accept';
                            });
                        });

                        document.getElementById('decline-btn-' + inv.id).addEventListener('click', function() {
                            var csrf = document.querySelector('meta[name="csrf-token"]');
                            var token = csrf ? csrf.getAttribute('content') : '';

                            fetch('/games/invites/' + inv.id + '/decline', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).catch(function() {});

                            card.remove();
                        });
                    });
                })
                .catch(function() {});
            }

            // Style for animation
            var styleEl = document.createElement('style');
            styleEl.innerHTML = '@keyframes globalPopIn{from{opacity:0;transform:translateY(-20px) scale(0.9)}to{opacity:1;transform:translateY(0) scale(1)}} @keyframes globalBob{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}';
            document.head.appendChild(styleEl);

            pollGlobalGameInvites();
            setInterval(pollGlobalGameInvites, 3500);
        })();
    </script>
    @endauth
</body>
</html>
