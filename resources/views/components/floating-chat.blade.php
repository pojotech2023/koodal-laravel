@php
    $isPaidMember = false;
    try {
        $isPaidMember = auth()->check() && (bool)(auth()->user()?->hasPaidPlan());
    } catch (\Throwable $e) {}
    $currentUserName = auth()->user()?->name ?? 'You';
@endphp

<div x-data="koodalFloatingChat()"
     x-init="initWidget()"
     class="kfc-root">

    {{-- FULL-PAGE CELEBRATION (Non-blocking: Text rises from bottom to top, Confetti & Emojis rain from top to bottom) --}}
    <div x-show="celebration.active"
         x-cloak
         class="kfc-celebration-overlay">

        {{-- Confetti Pieces raining from top to bottom --}}
        <template x-for="p in celebration.particles" :key="p.id">
            <span class="kfc-confetti-piece"
                  :style="`left:${p.left}%; background:${p.color}; animation-delay:${p.delay}s; animation-duration:${p.duration}s; width:${p.size}px; height:${p.size * 1.6}px;`"></span>
        </template>

        {{-- Falling Emojis raining from top to bottom --}}
        <template x-for="em in celebration.floatingEmojis" :key="em.id">
            <span class="kfc-falling-emoji"
                  :style="`left:${em.left}%; animation-delay:${em.delay}s; animation-duration:${em.duration}s; font-size:${em.size}px;`"
                  x-text="em.char"></span>
        </template>

        {{-- Multiple Rising Text Badges floating from bottom to top across the screen --}}
        <template x-for="t in celebration.risingTexts" :key="t.id">
            <div class="kfc-rising-text-badge"
                 :style="`left:${t.left}%; animation-delay:${t.delay}s; animation-duration:${t.duration}s; transform:scale(${t.scale}); border-color:${celebration.accentColor}; box-shadow:0 8px 30px ${celebration.glowColor}; background:${celebration.badgeBg};`">
                <span class="text-xl shrink-0" x-text="t.emoji"></span>
                <span class="font-black text-sm sm:text-base text-white tracking-wide" x-text="t.text"></span>
                <span class="text-xs text-amber-200">✨</span>
            </div>
        </template>

        {{-- Falling Celebration Banners cascading from top to bottom without blocking background screen --}}
        <template x-for="b in celebration.fallingBanners" :key="b.id">
            <div class="kfc-falling-banner"
                 :style="`left:${b.left}%; animation-delay:${b.delay}s; animation-duration:${b.duration}s; border-color:${celebration.accentColor}; box-shadow:0 10px 30px ${celebration.glowColor}; background:${celebration.badgeBg};`">
                <span class="text-2xl drop-shadow" x-text="celebration.mainEmoji"></span>
                <div class="flex flex-col text-left">
                    <span class="text-[10px] font-black uppercase tracking-wider text-white/90 leading-tight" x-text="celebration.senderLabel"></span>
                    <span class="text-sm sm:text-base font-black text-white leading-tight" x-text="celebration.title"></span>
                </div>
                <span class="text-lg">✨</span>
            </div>
        </template>
    </div>

    {{-- FLOATING CHAT WIDGET (Mobile & Laptop - Hover & Click) --}}
    <div class="kfc-widget-wrap"
         @mouseenter="handleHoverOpen()"
         @mouseleave="handleHoverLeave()">

        {{-- Chat Popover Panel --}}
        <div x-show="isOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             @click.away="if (!pinnedOpen) isOpen = false"
             class="kfc-panel">

            {{-- Header --}}
            <div class="kfc-header">
                <div class="flex items-center gap-2.5">
                    <span class="kfc-header-icon">💬</span>
                    <div>
                        <h4 class="text-xs font-black tracking-wide text-white leading-tight">Quick Chat & Reactions</h4>
                        <p class="text-[10px] font-semibold text-pink-100">Tap any default message to celebrate! 🎉</p>
                    </div>
                </div>
                <button type="button" @click="closePanel()" class="kfc-close-btn" aria-label="Close chat">✕</button>
            </div>

            {{-- Default Messages (Unlocked for Everyone) --}}
            <div class="kfc-defaults-section">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-pink-700">✨ Default Messages (Free to Use)</span>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Unlocked 🔓</span>
                </div>
                <div class="kfc-defaults-grid">
                    <template x-for="item in defaultMessages" :key="item.text">
                        <button type="button"
                                @click="sendDefaultMessage(item)"
                                class="kfc-default-chip">
                            <span class="text-sm" x-text="item.emoji"></span>
                            <span class="font-extrabold text-xs text-slate-800" x-text="item.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Recent Activity / Sent Messages Stream --}}
            <div class="kfc-stream" id="kfcStreamBox">
                <template x-if="recentMessages.length === 0">
                    <div class="text-center py-3 text-[11px] font-semibold text-slate-400">
                        Click a default message above to send & celebrate on full screen! 🎊
                    </div>
                </template>
                <template x-for="m in recentMessages" :key="m.id">
                    <div class="kfc-msg-row" :class="m.mine ? 'mine' : 'theirs'">
                        <div class="kfc-msg-bubble">
                            <span x-text="m.text"></span>
                            <small x-text="m.time"></small>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Custom Chat Input: Paid/Upgraded members can type custom chat; Free members use default messages & see Upgrade lock --}}
            <div class="kfc-footer">
                @if($isPaidMember)
                    <form @submit.prevent="sendCustomMessage()" class="flex items-center gap-2">
                        <input type="text"
                               x-model="customText"
                               maxlength="200"
                               placeholder="Type a message to partner..."
                               class="flex-1 rounded-xl border border-pink-200 px-3 py-2 text-xs font-semibold text-slate-800 focus:border-pink-500 focus:outline-none shadow-inner bg-slate-50 focus:bg-white transition">
                        <button type="submit"
                                class="rounded-xl px-3.5 py-2 text-xs font-black text-white shadow-sm transition active:scale-95 shrink-0"
                                style="background: linear-gradient(135deg, #E0245E, #B81B5C);">
                            Send ➤
                        </button>
                    </form>
                @else
                    <div class="kfc-locked-box">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="text-sm shrink-0">🔒</span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black text-amber-950 leading-tight">Custom Chat Locked</p>
                                    <p class="text-[9.5px] font-semibold text-amber-800 truncate">Default messages are free! Upgrade plan to type custom chat.</p>
                                </div>
                            </div>
                            <a href="{{ route('membership.index') }}" class="kfc-upgrade-btn shrink-0">
                                Upgrade ⭐
                            </a>
                        </div>
                        <div class="mt-2 flex items-center gap-2 opacity-60 pointer-events-none select-none">
                            <input type="text" disabled placeholder="🔒 Upgrade to type custom messages..." class="flex-1 rounded-lg border border-amber-200 bg-amber-50/80 px-2.5 py-1.5 text-[11px] text-amber-900">
                            <button type="button" disabled class="rounded-lg bg-amber-300 px-3 py-1.5 text-[11px] font-bold text-amber-900">Send</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Floating Trigger Button --}}
        <button type="button"
                @click="togglePanel()"
                class="kfc-fab"
                aria-label="Open Quick Chat & Reactions">
            <span class="kfc-fab-pulse"></span>
            <span class="text-xl sm:text-2xl relative z-10">💬</span>
            <span class="kfc-fab-label relative z-10">Chat</span>
            <template x-if="unreadCount > 0 && !isOpen">
                <span class="relative z-10 ml-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-amber-400 text-slate-950 text-[11px] font-black shadow"
                      x-text="unreadCount"></span>
            </template>
        </button>
    </div>
</div>

<style>
.kfc-celebration-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    pointer-events: none;
    overflow: hidden;
    background: transparent !important;
    backdrop-filter: none !important;
}
.kfc-falling-banner {
    position: absolute;
    top: -90px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    border-radius: 999px;
    border-width: 2px;
    border-style: solid;
    color: #ffffff;
    pointer-events: none;
    white-space: nowrap;
    animation: kfcFallingDown linear forwards;
    z-index: 20;
}
.kfc-falling-emoji {
    position: absolute;
    top: -60px;
    pointer-events: none;
    animation: kfcFallingDown linear forwards;
    z-index: 15;
}
.kfc-confetti-piece {
    position: absolute;
    top: -30px;
    border-radius: 4px;
    pointer-events: none;
    animation: kfcConfettiFall linear forwards;
    z-index: 10;
}
.kfc-rising-text-badge {
    position: absolute;
    bottom: -80px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 9999px;
    border-width: 2px;
    border-style: solid;
    color: #ffffff;
    font-weight: 900;
    font-size: 13px;
    letter-spacing: 0.3px;
    pointer-events: none;
    white-space: nowrap;
    animation: kfcRisingUpward linear forwards;
    z-index: 25;
    will-change: transform, opacity;
}
@keyframes kfcRisingUpward {
    0% {
        transform: translateY(0) scale(0.65) rotate(-3deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
        transform: translateY(-50px) scale(1.05) rotate(2deg);
    }
    45% {
        opacity: 1;
        transform: translateY(-45vh) scale(1.0) rotate(-1deg);
    }
    75% {
        opacity: 0.95;
        transform: translateY(-85vh) scale(0.98) rotate(2deg);
    }
    100% {
        transform: translateY(-120vh) scale(0.9) rotate(0deg);
        opacity: 0;
    }
}
@keyframes kfcFallingDown {
    0% {
        transform: translateY(-20px);
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    85% {
        opacity: 1;
    }
    100% {
        transform: translateY(115vh);
        opacity: 0;
    }
}
@keyframes kfcPopBounce {
    0% { opacity: 0; transform: scale(0.4) translateY(40px); }
    65% { opacity: 1; transform: scale(1.08) translateY(-6px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes kfcEmojiPulse {
    from { transform: scale(1) rotate(-6deg); }
    to { transform: scale(1.18) rotate(6deg); }
}
@keyframes kfcConfettiFall {
    0% { transform: translateY(-20px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(105vh) rotate(720deg); opacity: 0; }
}
@keyframes kfcFloatUp {
    0% { transform: translateY(30px) scale(0.5); opacity: 0; }
    30% { opacity: 1; transform: translateY(-15px) scale(1.15); }
    100% { transform: translateY(-120px) scale(0.9); opacity: 0; }
}

.kfc-widget-wrap {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9990;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
.kfc-fab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 999px;
    background: linear-gradient(135deg, #E0245E 0%, #FF4081 50%, #9D174D 100%);
    color: #ffffff;
    font-weight: 900;
    font-size: 13px;
    border: 2px solid rgba(255,255,255,0.85);
    box-shadow: 0 10px 28px rgba(224, 36, 94, 0.45);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kfc-fab:hover {
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 14px 34px rgba(224, 36, 94, 0.6);
}
.kfc-fab-pulse {
    position: absolute;
    inset: -3px;
    border-radius: 999px;
    background: rgba(244, 63, 94, 0.4);
    animation: kfcPulseRing 2s infinite;
}
@keyframes kfcPulseRing {
    0% { transform: scale(0.95); opacity: 0.7; }
    70% { transform: scale(1.15); opacity: 0; }
    100% { transform: scale(1.15); opacity: 0; }
}
.kfc-panel {
    width: min(350px, calc(100vw - 28px));
    background: #ffffff;
    border-radius: 24px;
    border: 2px solid #FBCFE8;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
    overflow: hidden;
    margin-bottom: 12px;
}
.kfc-header {
    background: linear-gradient(135deg, #E0245E 0%, #9D174D 100%);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.kfc-header-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.kfc-close-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.18);
    color: #ffffff;
    font-size: 12px;
    font-weight: 900;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.kfc-defaults-section {
    padding: 12px 14px;
    background: #FFF5F8;
    border-bottom: 1px solid #FCE7F3;
    max-height: 185px;
    overflow-y: auto;
}
.kfc-defaults-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.kfc-default-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 12px;
    background: #ffffff;
    border: 1.5px solid #FBCFE8;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 5px rgba(224, 36, 94, 0.06);
}
.kfc-default-chip:hover {
    background: #FDF2F8;
    border-color: #E0245E;
    transform: translateY(-1px);
}
.kfc-default-chip:active {
    transform: scale(0.96);
}
.kfc-stream {
    max-height: 135px;
    overflow-y: auto;
    padding: 10px 14px;
    background: #F8FAFC;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.kfc-msg-row {
    display: flex;
}
.kfc-msg-row.mine {
    justify-content: flex-end;
}
.kfc-msg-row.theirs {
    justify-content: flex-start;
}
.kfc-msg-bubble {
    max-width: 82%;
    padding: 6px 11px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}
.kfc-msg-row.mine .kfc-msg-bubble {
    background: linear-gradient(135deg, #E0245E, #BE185D);
    color: #ffffff;
    border-bottom-right-radius: 4px;
}
.kfc-msg-row.theirs .kfc-msg-bubble {
    background: #ffffff;
    color: #1E293B;
    border: 1px solid #E2E8F0;
    border-bottom-left-radius: 4px;
}
.kfc-msg-bubble small {
    font-size: 9px;
    opacity: 0.75;
}
.kfc-footer {
    padding: 10px 14px;
    background: #ffffff;
    border-top: 1px solid #F1F5F9;
}
.kfc-locked-box {
    background: #FFFBEB;
    border: 1.5px dashed #F59E0B;
    border-radius: 14px;
    padding: 8px 10px;
}
.kfc-upgrade-btn {
    background: linear-gradient(135deg, #D97706, #B45309);
    color: #ffffff;
    font-size: 10px;
    font-weight: 900;
    padding: 5px 10px;
    border-radius: 999px;
    text-decoration: none;
    white-space: nowrap;
}
@media (max-width: 640px) {
    .kfc-widget-wrap {
        right: 12px;
        bottom: 14px;
    }
    .kfc-fab {
        padding: 10px 15px;
    }
}
</style>

<script>
function koodalFloatingChat() {
    return {
        isOpen: false,
        pinnedOpen: false,
        hoverTimeout: null,
        customText: '',
        unreadCount: 0,
        lastSeenServerMsgId: null,
        seenServerMsgIds: {},
        initializedSync: false,
        recentMessages: [],
        defaultMessages: [
            { label: 'Play Quickly', emoji: '⏳', text: 'Play Quickly ⏳' },
            { label: 'Ohh No', emoji: '🙈', text: 'Ohh No 🙈' },
            { label: 'Oops', emoji: '😅', text: 'Oops 😅' },
            { label: 'Your Turn', emoji: '⏱️', text: 'Your Turn ⏱️' },
            { label: 'Almost There', emoji: '🎯', text: 'Almost There 🎯' },
            { label: 'Good', emoji: '😊', text: 'Good 😊' },
            { label: 'Super', emoji: '👍', text: 'Super 👍' },
            { label: 'Wow', emoji: '👏', text: 'Wow 👏' },
            { label: 'Excellent', emoji: '⭐', text: 'Excellent ⭐' },
            { label: 'Nice Play', emoji: '🔥', text: 'Nice Play 🔥' },
            { label: 'All the Best', emoji: '💖', text: 'All the Best 💖' },
            { label: 'One More Game?', emoji: '🎮', text: 'One More Game? 🎮' },
        ],
        celebration: {
            active: false,
            timer: null,
            mainEmoji: '😊',
            title: 'GOOD! 😊',
            subtitle: 'Celebrating great vibes together!',
            senderLabel: '🎉 Quick Reaction',
            accentColor: '#10B981',
            glowColor: 'rgba(16, 185, 129, 0.45)',
            badgeBg: 'linear-gradient(135deg, #10B981, #059669)',
            titleGradient: 'linear-gradient(135deg, #059669, #10B981)',
            bgOverlay: 'radial-gradient(circle, rgba(16,185,129,0.22) 0%, rgba(15,23,42,0.35) 100%)',
            particles: [],
            floatingEmojis: [],
            risingTexts: [],
            fallingBanners: [],
        },

        initWidget() {
            const self = this;
            window.triggerKoodalCelebration = function(messageText, senderName = '{{ addslashes($currentUserName) }}') {
                self.launchFullPageCelebration(messageText, senderName);
            };
            window.pushKoodalChatStream = function(messageText, mine = true) {
                self.addStreamMessage(messageText, mine);
            };
            window.syncKoodalChatMessages = function(messages, currentUserId, partnerName = 'Partner') {
                self.syncServerMessages(messages, currentUserId, partnerName);
            };
        },

        syncServerMessages(messages, currentUserId, partnerName = 'Partner') {
            if (!Array.isArray(messages)) return;
            const chatOnly = messages.filter(m => m && m.type !== 'system');
            const mapped = chatOnly.slice(-25).map((m, idx) => {
                const senderId = Number(m.sender_id ?? m.user_id ?? 0);
                const isMine = Number(currentUserId) > 0 ? senderId === Number(currentUserId) : Boolean(m.mine);
                return {
                    id: String(m.id || ('srv-' + idx + '-' + (m.message || m.text || '') + '-' + (m.created_at || m.time || ''))),
                    text: m.message || m.text || '',
                    mine: isMine,
                    senderName: m.sender_name || m.sender || m.name || partnerName,
                    time: m.created_at || m.time || '',
                };
            });

            if (!this.initializedSync) {
                mapped.forEach(m => {
                    this.seenServerMsgIds[m.id] = true;
                });
                if (mapped.length > 0) {
                    this.lastSeenServerMsgId = mapped[mapped.length - 1].id;
                }
                this.initializedSync = true;
            } else {
                const newPartnerMessages = [];
                mapped.forEach(m => {
                    if (!this.seenServerMsgIds[m.id]) {
                        this.seenServerMsgIds[m.id] = true;
                        if (!m.mine && m.text) {
                            newPartnerMessages.push(m);
                        }
                    }
                });

                if (newPartnerMessages.length > 0) {
                    if (!this.isOpen) {
                        this.unreadCount += newPartnerMessages.length;
                    }
                    const latestPartnerMsg = newPartnerMessages[newPartnerMessages.length - 1];
                    this.launchFullPageCelebration(latestPartnerMsg.text, latestPartnerMsg.senderName || partnerName);
                }

                if (mapped.length > 0) {
                    this.lastSeenServerMsgId = mapped[mapped.length - 1].id;
                }
            }

            this.recentMessages = mapped;
            this.$nextTick(() => {
                const box = document.getElementById('kfcStreamBox');
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        handleHoverOpen() {
            if (this.hoverTimeout) clearTimeout(this.hoverTimeout);
            this.isOpen = true;
            this.unreadCount = 0;
        },

        handleHoverLeave() {
            if (this.pinnedOpen) return;
            this.hoverTimeout = setTimeout(() => {
                this.isOpen = false;
            }, 350);
        },

        togglePanel() {
            this.pinnedOpen = !this.pinnedOpen;
            this.isOpen = this.pinnedOpen;
            if (this.isOpen) {
                this.unreadCount = 0;
            }
        },

        closePanel() {
            this.pinnedOpen = false;
            this.isOpen = false;
        },

        addStreamMessage(text, mine = true) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            this.recentMessages.push({
                id: 'local-' + Date.now() + '-' + Math.random(),
                text: text,
                mine: mine,
                time: timeStr,
            });
            if (this.recentMessages.length > 25) {
                this.recentMessages.shift();
            }
            if (!mine && !this.isOpen) {
                this.unreadCount++;
            }
            this.$nextTick(() => {
                const box = document.getElementById('kfcStreamBox');
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        sendDefaultMessage(item) {
            this.addStreamMessage(item.text, true);
            this.launchFullPageCelebration(item.text, '{{ addslashes($currentUserName) }}');
            this.bridgeSendToActivePage(item.text, 'quick_reply');
        },

        sendCustomMessage() {
            const txt = (this.customText || '').trim();
            if (!txt) return;
            this.customText = '';
            this.addStreamMessage(txt, true);
            this.launchFullPageCelebration(txt, '{{ addslashes($currentUserName) }}');
            this.bridgeSendToActivePage(txt, 'text');
        },

        bridgeSendToActivePage(text, type = 'text') {
            // Dispatch global event so any active game page (Sync, Live Room, Find It, Memory Snap, Flip) can also send it to the room
            window.dispatchEvent(new CustomEvent('koodal-quick-chat-send', { detail: { message: text, type: type } }));
        },

        getReactionTheme(rawText) {
            const t = (rawText || '').toLowerCase();
            if (t.includes('quickly') || t.includes('⏳') || t.includes('turn') || t.includes('⏱️')) {
                return {
                    mainEmoji: '⏳',
                    title: 'PLAY QUICKLY! ⏳⚡',
                    subtitle: 'Clock is ticking! Keep the momentum going!',
                    accentColor: '#3B82F6',
                    glowColor: 'rgba(59, 130, 246, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #3B82F6, #1D4ED8)',
                    titleGradient: 'linear-gradient(135deg, #1D4ED8, #06B6D4)',
                    bgOverlay: 'radial-gradient(circle, rgba(59,130,246,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['⏳', '⏱️', '⚡', '🏃‍♂️', '🎯', '🔥'],
                };
            }
            if (t.includes('ohh') || t.includes('no') || t.includes('🙈') || t.includes('oops') || t.includes('😅') || t.includes('close')) {
                return {
                    mainEmoji: '🙈',
                    title: 'OHH NO! 🙈😅',
                    subtitle: 'So close! Don\'t worry, keep trying!',
                    accentColor: '#F59E0B',
                    glowColor: 'rgba(245, 158, 11, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #F59E0B, #D97706)',
                    titleGradient: 'linear-gradient(135deg, #D97706, #EA580C)',
                    bgOverlay: 'radial-gradient(circle, rgba(245,158,11,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['🙈', '😅', '🤏', '💥', '🙃', '✨'],
                };
            }
            if (t.includes('one more') || t.includes('game') || t.includes('🎮')) {
                return {
                    mainEmoji: '🎮',
                    title: 'ONE MORE GAME? 🎮🔥',
                    subtitle: 'Up for another round of fun & connection!',
                    accentColor: '#8B5CF6',
                    glowColor: 'rgba(139, 92, 246, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #8B5CF6, #6D28D9)',
                    titleGradient: 'linear-gradient(135deg, #6D28D9, #EC4899)',
                    bgOverlay: 'radial-gradient(circle, rgba(139,92,246,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['🎮', '🔥', '🎲', '🥳', '✨', '🏆'],
                };
            }
            if (t.includes('good') || t.includes('😊')) {
                return {
                    mainEmoji: '😊',
                    title: 'GOOD! 😊🎉',
                    subtitle: 'Sweet appreciation & positive vibes!',
                    accentColor: '#10B981',
                    glowColor: 'rgba(16, 185, 129, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #10B981, #059669)',
                    titleGradient: 'linear-gradient(135deg, #047857, #10B981)',
                    bgOverlay: 'radial-gradient(circle, rgba(16,185,129,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['😊', '🎉', '✨', '👏', '💚', '🌟'],
                };
            }
            if (t.includes('super') || t.includes('👍')) {
                return {
                    mainEmoji: '👍',
                    title: 'SUPER! 👍🔥',
                    subtitle: 'What an amazing move! Keep it going!',
                    accentColor: '#F59E0B',
                    glowColor: 'rgba(245, 158, 11, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #F59E0B, #D97706)',
                    titleGradient: 'linear-gradient(135deg, #B45309, #F59E0B)',
                    bgOverlay: 'radial-gradient(circle, rgba(245,158,11,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['👍', '🔥', '🎉', '⭐', '🚀', '✨'],
                };
            }
            if (t.includes('wow') || t.includes('👏')) {
                return {
                    mainEmoji: '👏',
                    title: 'WOW! 👏✨',
                    subtitle: 'Incredible chemistry & brilliant play!',
                    accentColor: '#8B5CF6',
                    glowColor: 'rgba(139, 92, 246, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #8B5CF6, #6D28D9)',
                    titleGradient: 'linear-gradient(135deg, #6D28D9, #EC4899)',
                    bgOverlay: 'radial-gradient(circle, rgba(139,92,246,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['👏', '🤩', '✨', '🎊', '💜', '🎉'],
                };
            }
            if (t.includes('excellent') || t.includes('⭐')) {
                return {
                    mainEmoji: '⭐',
                    title: 'EXCELLENT! ⭐🏆',
                    subtitle: 'Top-notch perfection! Celebrate the moment!',
                    accentColor: '#EC4899',
                    glowColor: 'rgba(236, 72, 153, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #EC4899, #BE185D)',
                    titleGradient: 'linear-gradient(135deg, #BE185D, #F43F5E)',
                    bgOverlay: 'radial-gradient(circle, rgba(236,72,153,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['⭐', '🏆', '👑', '🎉', '💖', '✨'],
                };
            }
            if (t.includes('nice') || t.includes('🔥')) {
                return {
                    mainEmoji: '🔥',
                    title: 'NICE PLAY! 🔥🎯',
                    subtitle: 'Sparks are flying! Great teamwork!',
                    accentColor: '#F97316',
                    glowColor: 'rgba(249, 115, 22, 0.5)',
                    badgeBg: 'linear-gradient(135deg, #F97316, #EA580C)',
                    titleGradient: 'linear-gradient(135deg, #C2410C, #F97316)',
                    bgOverlay: 'radial-gradient(circle, rgba(249,115,22,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                    emojis: ['🔥', '🎯', '🎉', '⚡', '👏', '✨'],
                };
            }
            return {
                mainEmoji: '💖',
                title: (rawText || 'CELEBRATE! 💖').toUpperCase(),
                subtitle: 'Sending warm wishes & celebration vibes!',
                accentColor: '#E0245E',
                glowColor: 'rgba(224, 36, 94, 0.5)',
                badgeBg: 'linear-gradient(135deg, #E0245E, #9D174D)',
                titleGradient: 'linear-gradient(135deg, #9D174D, #E0245E)',
                bgOverlay: 'radial-gradient(circle, rgba(224,36,94,0.28) 0%, rgba(15,23,42,0.4) 100%)',
                emojis: ['💖', '🌸', '🎉', '✨', '💫', '🎊'],
            };
        },

        launchFullPageCelebration(messageText, senderName = '') {
            const theme = this.getReactionTheme(messageText);
            const colors = ['#EC4899', '#8B5CF6', '#F59E0B', '#10B981', '#06B6D4', '#F43F5E'];

            const particles = [];
            for (let i = 0; i < 42; i++) {
                particles.push({
                    id: i + '-' + Date.now(),
                    left: Math.round((i * 2.4 + Math.random() * 5) % 100),
                    color: colors[i % colors.length],
                    delay: (Math.random() * 0.45).toFixed(2),
                    duration: (1.5 + Math.random() * 1.1).toFixed(2),
                    size: 7 + Math.round(Math.random() * 6),
                });
            }

            const floatingEmojis = [];
            for (let j = 0; j < 24; j++) {
                floatingEmojis.push({
                    id: j + '-' + Date.now(),
                    char: theme.emojis[j % theme.emojis.length],
                    left: 4 + Math.round((j * 4.1 + Math.random() * 4) % 92),
                    delay: (Math.random() * 0.6).toFixed(2),
                    duration: (2.2 + Math.random() * 1.2).toFixed(2),
                    size: 24 + Math.round(Math.random() * 20),
                });
            }

            // Clean display text for rising badges
            let cleanText = (messageText || '').trim();
            if (!cleanText) cleanText = theme.title;

            // Generate 14 rising text badges floating up from bottom to top across the screen
            const risingTexts = [];
            const leftSlots = [6, 16, 26, 36, 46, 56, 66, 76, 84, 12, 32, 52, 72, 88];
            for (let k = 0; k < 14; k++) {
                risingTexts.push({
                    id: 'rt-' + k + '-' + Date.now(),
                    text: cleanText,
                    emoji: theme.mainEmoji,
                    left: Math.max(3, Math.min(88, leftSlots[k % leftSlots.length] + Math.round((Math.random() - 0.5) * 6))),
                    delay: (k * 0.12 + Math.random() * 0.08).toFixed(2),
                    duration: (2.7 + Math.random() * 0.8).toFixed(2),
                    scale: (0.9 + Math.random() * 0.22).toFixed(2),
                });
            }

            const fallingBanners = [
                { id: 'b1-' + Date.now(), left: 18 + Math.round(Math.random() * 14), delay: '0.05', duration: '3.2' },
                { id: 'b2-' + Date.now(), left: 54 + Math.round(Math.random() * 16), delay: '0.45', duration: '3.4' },
            ];

            if (this.celebration.timer) {
                clearTimeout(this.celebration.timer);
            }

            this.celebration.mainEmoji = theme.mainEmoji;
            this.celebration.title = theme.title;
            this.celebration.subtitle = theme.subtitle;
            this.celebration.senderLabel = senderName ? `🎉 ${senderName} says` : '🎉 Celebration!';
            this.celebration.accentColor = theme.accentColor;
            this.celebration.glowColor = theme.glowColor;
            this.celebration.badgeBg = theme.badgeBg;
            this.celebration.titleGradient = theme.titleGradient;
            this.celebration.bgOverlay = theme.bgOverlay;
            this.celebration.particles = particles;
            this.celebration.floatingEmojis = floatingEmojis;
            this.celebration.risingTexts = risingTexts;
            this.celebration.fallingBanners = fallingBanners;
            this.celebration.active = true;

            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playFinalClapAndFanfare();
            }

            this.celebration.timer = setTimeout(() => {
                this.celebration.active = false;
            }, 3800);
        }
    };
}

/**
 * Universal Game Sound Engine (Preloaded Audio Files + Web Audio Fallback)
 * Supports: start, match, wrong, timer tick (last 5s), win/complete, mute toggle & localStorage
 */
(function() {
    if (window.KoodalGameAudio) return;

    const SOUND_PATHS = {
        start: '/assets/sounds/start.mp3',
        match: '/assets/sounds/match.mp3',
        wrong: '/assets/sounds/wrong.mp3',
        tick:  '/assets/sounds/tick.mp3',
        win:   '/assets/sounds/win.mp3'
    };

    const preloadedAudio = {};

    // Preload audio files
    try {
        Object.keys(SOUND_PATHS).forEach(key => {
            const audio = new Audio();
            audio.preload = 'auto';
            audio.src = SOUND_PATHS[key];
            preloadedAudio[key] = audio;
        });
    } catch (e) {
        console.warn('Audio preloading error:', e);
    }

    const KoodalGameAudio = {
        ctx: null,
        muted: (function() {
            try {
                return localStorage.getItem('koodal_game_sound_muted') === 'true';
            } catch (e) {
                return false;
            }
        })(),
        unlocked: false,

        isMuted() {
            return !!this.muted;
        },

        toggleMute() {
            this.muted = !this.muted;
            try {
                localStorage.setItem('koodal_game_sound_muted', this.muted ? 'true' : 'false');
                window.dispatchEvent(new CustomEvent('koodal-sound-mute-changed', { detail: { muted: this.muted } }));
            } catch (e) {}
            return this.muted;
        },

        setMuted(val) {
            this.muted = !!val;
            try {
                localStorage.setItem('koodal_game_sound_muted', this.muted ? 'true' : 'false');
                window.dispatchEvent(new CustomEvent('koodal-sound-mute-changed', { detail: { muted: this.muted } }));
            } catch (e) {}
        },

        getContext() {
            if (this.muted) return null;
            try {
                if (!this.ctx) {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx) {
                        this.ctx = new AudioCtx();
                    }
                }
                if (this.ctx && this.ctx.state === 'suspended') {
                    this.ctx.resume().catch(() => {});
                }
                return this.ctx;
            } catch (e) {
                return null;
            }
        },

        // Play file helper with clone to support rapid successive triggers
        playFile(type) {
            if (this.muted) return;
            try {
                const base = preloadedAudio[type];
                if (base) {
                    const soundClone = base.cloneNode(true);
                    soundClone.volume = 0.85;
                    const p = soundClone.play();
                    if (p && typeof p.catch === 'function') {
                        p.catch(() => {
                            // Autoplay restricted or failed, fallback to synth if available
                            this._playSynthFallback(type);
                        });
                    }
                    return;
                }
            } catch (e) {}
            this._playSynthFallback(type);
        },

        _playSynthFallback(type) {
            if (type === 'tick') this._synthTick();
            else if (type === 'match') this._synthMatch();
            else if (type === 'wrong') this._synthWrong();
            else if (type === 'win') this.playFinalClapAndFanfare();
            else if (type === 'start') this.playSelect();
        },

        playStart() {
            this.playFile('start');
        },

        playMatch() {
            this.playFile('match');
        },

        playMatchSuccess() {
            this.playMatch();
        },

        playWrong() {
            this.playFile('wrong');
        },

        playTick() {
            this.playFile('tick');
        },

        playWin() {
            this.playFile('win');
        },

        // Countdown Beep / Timer Tick (last 5-4-3-2-1 seconds)
        playCountdown(step = 3) {
            if (this.muted) return;
            if (typeof step === 'number' && step <= 5 && step > 0) {
                this.playFile('tick');
                return;
            }
            if (step === 0 || step === 'go') {
                this.playFile('match');
                return;
            }
            this._synthTick(step);
        },

        _synthTick(step = 3) {
            const ctx = this.getContext();
            if (!ctx) return;
            try {
                const now = ctx.currentTime;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                const isFinal = (step === 1 || step === 0 || step === 'go');
                osc.type = isFinal ? 'triangle' : 'sine';
                const freq = step === 0 || step === 'go' ? 1046.5 : (step === 1 ? 880 : 659.25);
                osc.frequency.setValueAtTime(freq, now);
                if (step === 0 || step === 'go') {
                    osc.frequency.exponentialRampToValueAtTime(1318.5, now + 0.22);
                }

                gain.gain.setValueAtTime(0.001, now);
                gain.gain.exponentialRampToValueAtTime(0.26, now + 0.015);
                gain.gain.exponentialRampToValueAtTime(0.001, now + (isFinal ? 0.24 : 0.15));

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + (isFinal ? 0.26 : 0.16));
            } catch (e) {}
        },

        _synthWrong() {
            const ctx = this.getContext();
            if (!ctx) return;
            try {
                const now = ctx.currentTime;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(260, now);
                osc.frequency.exponentialRampToValueAtTime(150, now + 0.25);

                gain.gain.setValueAtTime(0.001, now);
                gain.gain.exponentialRampToValueAtTime(0.2, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.28);

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.3);
            } catch (e) {}
        },

        _synthMatch() {
            const ctx = this.getContext();
            if (!ctx) return;
            try {
                const now = ctx.currentTime;
                const notes = [523.25, 659.25, 783.99, 1046.50];
                notes.forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    const start = now + idx * 0.075;
                    osc.frequency.setValueAtTime(freq, start);

                    gain.gain.setValueAtTime(0.001, start);
                    gain.gain.exponentialRampToValueAtTime(0.25, start + 0.015);
                    gain.gain.exponentialRampToValueAtTime(0.001, start + 0.25);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(start);
                    osc.stop(start + 0.27);
                });
            } catch (e) {}
        },

        // Option Select / Tile Flip Sound
        playSelect() {
            const ctx = this.getContext();
            if (!ctx) return;
            try {
                const now = ctx.currentTime;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523.25, now);
                osc.frequency.exponentialRampToValueAtTime(783.99, now + 0.11);

                gain.gain.setValueAtTime(0.001, now);
                gain.gain.exponentialRampToValueAtTime(0.22, now + 0.012);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.13);

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.14);
            } catch (e) {}
        },

        // Single Hand-Clap Burst Helper
        _triggerSingleClap(ctx, startTime, volume = 0.28, panVal = 0) {
            try {
                const duration = 0.075 + Math.random() * 0.035;
                const bufferSize = Math.floor(ctx.sampleRate * duration);
                const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    const env = Math.pow(1 - (i / bufferSize), 2.2);
                    data[i] = (Math.random() * 2 - 1) * env;
                }

                const noise = ctx.createBufferSource();
                noise.buffer = buffer;

                const filter = ctx.createBiquadFilter();
                filter.type = 'bandpass';
                filter.frequency.setValueAtTime(1250 + Math.random() * 850, startTime);
                filter.Q.setValueAtTime(1.6, startTime);

                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0.001, startTime);
                gain.gain.linearRampToValueAtTime(volume, startTime + 0.005);
                gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

                noise.connect(filter);
                filter.connect(gain);

                if (ctx.createStereoPanner) {
                    const panner = ctx.createStereoPanner();
                    panner.pan.setValueAtTime(panVal, startTime);
                    gain.connect(panner);
                    panner.connect(ctx.destination);
                } else {
                    gain.connect(ctx.destination);
                }

                noise.start(startTime);
                noise.stop(startTime + duration + 0.01);
            } catch (e) {}
        },

        // Final Applause / Clapping Sound + Victory Fanfare
        playFinalClapAndFanfare() {
            this.playFile('win');
            const ctx = this.getContext();
            if (!ctx) return;
            try {
                const now = ctx.currentTime;
                const fanfare = [
                    { f: 523.25, t: 0.00, d: 0.18 },
                    { f: 659.25, t: 0.14, d: 0.18 },
                    { f: 783.99, t: 0.28, d: 0.18 },
                    { f: 1046.50, t: 0.42, d: 0.42 },
                    { f: 783.99, t: 0.88, d: 0.18 },
                    { f: 1046.50, t: 1.06, d: 0.65 },
                    { f: 1318.51, t: 1.06, d: 0.65 },
                ];
                fanfare.forEach(note => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    const st = now + note.t;
                    osc.frequency.setValueAtTime(note.f, st);

                    gain.gain.setValueAtTime(0.001, st);
                    gain.gain.exponentialRampToValueAtTime(0.22, st + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, st + note.d);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(st);
                    osc.stop(st + note.d + 0.02);
                });

                for (let c = 0; c < 42; c++) {
                    const offset = (c * 0.058) + (Math.random() * 0.035);
                    const fadeOut = c > 30 ? Math.max(0.08, 1 - ((c - 30) / 14)) : 1;
                    const vol = (0.18 + Math.random() * 0.14) * fadeOut;
                    const pan = (Math.random() * 1.4) - 0.7;
                    this._triggerSingleClap(ctx, now + offset, vol, pan);
                }
            } catch (e) {}
        }
    };

    // Unlock AudioContext and Preloaded Audio on first user interaction anywhere on the page
    const unlockAudio = () => {
        KoodalGameAudio.getContext();
        KoodalGameAudio.unlocked = true;
        // Pre-warm audio elements to satisfy iOS/Android autoplay policy
        try {
            Object.values(preloadedAudio).forEach(a => {
                a.volume = 0;
                const p = a.play();
                if (p && typeof p.then === 'function') {
                    p.then(() => {
                        a.pause();
                        a.currentTime = 0;
                        a.volume = 0.85;
                    }).catch(() => {});
                }
            });
        } catch (e) {}
        document.removeEventListener('click', unlockAudio);
        document.removeEventListener('touchstart', unlockAudio);
        document.removeEventListener('keydown', unlockAudio);
    };
    document.addEventListener('click', unlockAudio, { passive: true });
    document.addEventListener('touchstart', unlockAudio, { passive: true });
    document.addEventListener('keydown', unlockAudio, { passive: true });

    window.KoodalGameAudio = KoodalGameAudio;
})();
</script>
