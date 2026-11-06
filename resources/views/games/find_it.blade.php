@extends('layouts.member')

@section('title', '🔍 Koodal Find It — Spot the Difference')

@section('body')
<style>
/* ═══════════ Hotspot animations (original, unchanged) ═══════════ */
@keyframes clockZoomBig {
    0% { transform: scale(0.4) rotate(0deg); opacity: 0; }
    35% { transform: scale(2.8) rotate(-15deg); opacity: 1; filter: drop-shadow(0 0 20px rgba(245, 158, 11, 0.9)); }
    70% { transform: scale(3.2) rotate(15deg); opacity: 1; filter: drop-shadow(0 0 30px rgba(234, 179, 8, 1)); }
    100% { transform: scale(0.2) rotate(360deg); opacity: 0; }
}
@keyframes birdFlyAway {
    0% { transform: translate(0, 0) scale(1) rotate(0deg); opacity: 1; }
    30% { transform: translate(-30px, -60px) scale(1.3) rotate(-15deg); opacity: 1; }
    60% { transform: translate(-100px, -150px) scale(1.5) rotate(-30deg); opacity: 0.9; }
    100% { transform: translate(-220px, -320px) scale(0.4) rotate(-45deg); opacity: 0; }
}
@keyframes birdFlap { 0%, 100% { transform: scaleY(1); } 50% { transform: scaleY(0.4); } }
@keyframes flowerPetalsFall {
    0% { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
    40% { transform: translateY(50px) rotate(45deg) scale(1.3); opacity: 1; }
    75% { transform: translateY(120px) rotate(140deg) scale(1.1); opacity: 0.8; }
    100% { transform: translateY(220px) rotate(270deg) scale(0.4); opacity: 0; }
}
@keyframes sparklePulse { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.6); opacity: 1; } 100% { transform: scale(1); opacity: 0; } }
@keyframes popFindIt { 0% { transform: scale(0.3) translateY(10px); opacity: 0; } 50% { transform: scale(1.2) translateY(-10px); opacity: 1; } 100% { transform: scale(1) translateY(-20px); opacity: 0; } }
.animate-clock-zoom { animation: clockZoomBig 2.4s ease-in-out forwards; }
.animate-bird-fly { animation: birdFlyAway 2.2s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
.animate-bird-wing { animation: birdFlap 0.25s infinite ease-in-out; }
.animate-flower-fall { animation: flowerPetalsFall 2.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
.animate-sparkle-pulse { animation: sparklePulse 1.8s ease-out forwards; }
.animate-pop-findit { animation: popFindIt 1.8s ease-out forwards; }

/* ═══════════ Page styles (self-contained) ═══════════ */
[x-cloak]{display:none !important}
.fi{
    --c1:#0891B2; --c2:#0E7490; --ink:#0B2A33; --muted:#5B7480; --line:#A5F3FC;
    position:relative;min-height:100vh;overflow-x:clip;padding:22px 16px 60px;color:var(--ink);
    background:linear-gradient(135deg,#ECFEFF 0%,#CFFAFE 50%,#E0F2FE 100%);
}
.fi *,.fi *::before,.fi *::after{box-sizing:border-box}
.fi a{text-decoration:none}
.fi button{font-family:inherit;cursor:pointer}
.fi button:focus-visible,.fi a:focus-visible,.fi input:focus-visible{outline:3px solid #67E8F9;outline-offset:2px}
.fi-wrap{position:relative;z-index:1;max-width:1380px;margin:0 auto;display:flex;flex-direction:column;gap:20px}
@keyframes fiUp{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:none}}
@keyframes fiPop{from{opacity:0;transform:scale(.88) translateY(18px)}to{opacity:1;transform:none}}
@keyframes fiBob{50%{transform:translateY(-6px)}}
@keyframes fiPing{0%{box-shadow:0 0 0 0 rgba(16,185,129,.6)}100%{box-shadow:0 0 0 9px rgba(16,185,129,0)}}
@keyframes fiShine{0%,55%{left:-80%}100%{left:140%}}
@keyframes fiBeat{0%,100%{transform:scale(1)}50%{transform:scale(1.12)}}
@keyframes fiSpin{to{transform:rotate(360deg)}}

/* background bubbles */
.fi-bg{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0}
.fi-bub{position:absolute;bottom:-60px;border-radius:50%;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.9),rgba(103,232,249,.25));
    border:1px solid rgba(255,255,255,.7);animation:fiRise 18s linear infinite}
.fi-bub:nth-child(1){left:6%;width:46px;height:46px;animation-delay:0s}
.fi-bub:nth-child(2){left:24%;width:28px;height:28px;animation-delay:5s}
.fi-bub:nth-child(3){left:47%;width:62px;height:62px;animation-delay:9s}
.fi-bub:nth-child(4){left:70%;width:34px;height:34px;animation-delay:2s}
.fi-bub:nth-child(5){left:90%;width:52px;height:52px;animation-delay:12s}
@keyframes fiRise{0%{transform:translateY(0);opacity:0}10%{opacity:.8}100%{transform:translateY(-115vh) translateX(30px);opacity:0}}

/* ───────── Toast ───────── */
.fi-toast{position:fixed;top:22px;left:50%;transform:translateX(-50%);z-index:1000;display:flex;align-items:center;gap:12px;max-width:min(560px,calc(100% - 28px));
    padding:14px 22px;border-radius:18px;border:2px solid #6EE7B7;background:linear-gradient(135deg,#059669,#0D9488 50%,#0891B2);
    color:#fff;font-size:14px;font-weight:900;box-shadow:0 20px 44px -10px rgba(0,0,0,.55);animation:fiToast .45s cubic-bezier(.2,1.2,.3,1) both}
.fi-toast .e{font-size:24px;animation:fiBob 1s ease-in-out infinite}
@keyframes fiToast{from{opacity:0;transform:translate(-50%,-30px) scale(.9)}to{opacity:1;transform:translate(-50%,0) scale(1)}}

/* ───────── Top bar ───────── */
.fi-bar{position:relative;overflow:hidden;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px 18px;
    border-radius:24px;background:#fff;border:1px solid #CFFAFE;box-shadow:0 18px 36px -24px rgba(8,145,178,.7);animation:fiUp .5s both}
.fi-grp{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.fi-exit{display:inline-flex;align-items:center;gap:5px;padding:9px 14px;border-radius:14px;background:#ECFEFF;border:1px solid var(--line);
    color:#155E75;font-size:12.5px;font-weight:900;transition:.2s}
.fi-exit:hover{background:#CFFAFE;transform:translateX(-3px)}
.fi-partner{display:flex;align-items:center;gap:10px;padding:6px 14px 6px 8px;border-radius:16px;background:#F8FAFC;border:1px solid #E2E8F0}
.fi-av{display:grid;place-items:center;width:36px;height:36px;flex:none;border-radius:50%;object-fit:cover;border:2px solid #67E8F9;
    background:linear-gradient(135deg,#0891B2,#0E7490);color:#fff;font-size:13px;font-weight:900}
.fi-pname{font-size:13px;font-weight:900;line-height:1.2;color:#0F172A}
.fi-pst{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:#64748B}
.fi-pst.on{color:#059669}
.fi-dot{width:9px;height:9px;border-radius:50%;background:#9CA3AF}
.fi-pst.on .fi-dot{background:#10B981;animation:fiPing 1.6s ease-out infinite}

.fi-pill{display:flex;align-items:center;gap:8px;padding:9px 18px;border-radius:999px;color:#fff;font-weight:900;box-shadow:0 10px 18px -10px rgba(0,0,0,.5)}
.fi-pill small{font-size:10.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#A5F3FC}
.fi-pill b{font-size:19px;line-height:1}
.fi-pill.timer{background:linear-gradient(135deg,#155E75,#0E7490)}
.fi-pill.timer b{color:#FCD34D;font-variant-numeric:tabular-nums}
.fi-pill.timer b.danger{color:#FDA4AF;animation:fiBeat .6s ease-in-out infinite}
.fi-pill.found{background:linear-gradient(135deg,#0E7490,#0891B2)}
.fi-pill.found b{color:#6EE7B7}
.fi-tbar{position:absolute;left:0;right:0;bottom:0;height:5px;background:#E0F2FE}
.fi-tbar i{display:block;height:100%;background:linear-gradient(90deg,#10B981,#06B6D4);transition:width 1s linear}
.fi-tbar i.danger{background:linear-gradient(90deg,#F43F5E,#F59E0B)}

.fi-start{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border:0;border-radius:999px;
    background:linear-gradient(135deg,#10B981,#059669);color:#fff;font-size:13px;font-weight:900;letter-spacing:.04em;
    box-shadow:0 14px 24px -10px #059669;animation:fiBob 1.6s ease-in-out infinite;transition:transform .2s}
.fi-start:hover{transform:scale(1.07)}
.fi-start::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);
    transform:skewX(-20deg);animation:fiShine 2.6s ease-in-out infinite}
.fi-live{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:#ECFDF5;border:1px solid #A7F3D0;color:#047857;font-size:12px;font-weight:900}
.fi-live::before{content:"";width:9px;height:9px;border-radius:50%;background:#10B981;animation:fiPing 1.2s ease-out infinite}

.fi-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 16px;border-radius:14px;border:1.5px solid transparent;font-size:12.5px;font-weight:900;transition:.2s;position:relative}
.fi-btn:hover{transform:translateY(-2px)}
.fi-btn.invite{background:linear-gradient(135deg,#0891B2,#0E7490);color:#fff;box-shadow:0 12px 20px -10px #0891B2}
.fi-btn.recv{background:#CFFAFE;border-color:#67E8F9;color:#164E63}
.fi-btn.recv:hover{background:#A5F3FC}
.fi-badge{display:grid;place-items:center;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#E11D48;color:#fff;font-size:10px;font-weight:900;animation:fiBeat 1.2s infinite}

/* ───────── Main layout ───────── */
.fi-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:22px;align-items:start}

.fi-game{padding:20px;border-radius:30px;background:#fff;border:1px solid #CFFAFE;box-shadow:0 30px 60px -34px rgba(8,145,178,.8);animation:fiUp .55s .08s both}
.fi-ghead{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px}
.fi-chal{display:flex;align-items:center;gap:12px}
.fi-chal-ico{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,#0891B2,#06B6D4);font-size:20px;
    box-shadow:0 10px 18px -8px #0891B2;animation:fiBob 3s ease-in-out infinite}
.fi-chal small{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#64748B}
.fi-chal strong{font-size:18px;font-weight:900;color:#0E7490;line-height:1.2}
.fi-hint{padding:8px 16px;border-radius:999px;background:#CFFAFE;border:1px solid #67E8F9;color:#155E75;font-size:12px;font-weight:800}

/* ───────── Images ───────── */
.fi-imgs{display:grid;grid-template-columns:minmax(0,1fr);gap:18px;margin-top:18px}
@media (min-width:640px){.fi-imgs{grid-template-columns:repeat(2,minmax(0,1fr))}}

.fi-frame{position:relative;overflow:hidden;padding:8px;border-radius:24px;cursor:crosshair;user-select:none;-webkit-user-select:none;
    background:linear-gradient(135deg,#0891B2,#06B6D4,#8B5CF6,#0891B2);background-size:300% 300%;animation:fiBorder 8s ease infinite;
    box-shadow:0 22px 38px -22px #0E7490;transition:transform .3s}
.fi-frame.b{background:linear-gradient(135deg,#EC4899,#8B5CF6,#06B6D4,#EC4899);background-size:300% 300%}
.fi-frame:hover{transform:translateY(-4px)}
@keyframes fiBorder{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.fi-inner{position:relative;overflow:hidden;border-radius:17px;background:#020617}
/* wrapper must hug the image so % hotspots match */
.fi-pic{position:relative;display:block;width:100%;transition:filter .5s,opacity .5s}
.fi-pic.is-locked{filter:blur(14px);opacity:.45;pointer-events:none}
.fi-pic img,.fi-pic svg{display:block;width:100%;height:auto;user-select:none;-webkit-user-select:none;-webkit-user-drag:none}
.fi-tag{position:absolute;top:10px;left:10px;z-index:20;padding:5px 13px;border-radius:999px;background:rgba(2,6,23,.78);border:1px solid rgba(255,255,255,.25);
    color:#fff;font-size:11.5px;font-weight:800;backdrop-filter:blur(4px)}

.fi-lock{position:absolute;inset:0;z-index:30;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:12px;
    background:rgba(2,6,23,.5);text-align:center}
.fi-lock span.t{padding:7px 16px;border-radius:999px;background:rgba(0,0,0,.8);border:1px solid rgba(252,211,77,.45);color:#FCD34D;font-size:12px;font-weight:900}
.fi-lock .fi-start{animation:fiBeat 1.4s ease-in-out infinite}

/* found markers */
.fi-anim{position:absolute;z-index:30;pointer-events:none;transform:translate(-50%,-50%)}
.fi-mk{position:absolute;z-index:20;pointer-events:none;transform:translate(-50%,-50%)}
.fi-mk i{position:relative;display:grid;place-items:center;width:42px;height:42px;border-radius:50%;border:4px solid #34D399;background:rgba(16,185,129,.35);
    color:#fff;font-style:normal;font-size:15px;font-weight:900;box-shadow:0 10px 22px rgba(0,0,0,.4);animation:fiMark .55s cubic-bezier(.2,1.5,.3,1) both}
.fi-mk i::after{content:"";position:absolute;inset:-4px;border-radius:50%;border:3px solid #34D399;animation:fiRipple 1.8s ease-out infinite}
.fi-mk.p i{border-color:#FBBF24;background:rgba(245,158,11,.35)}
.fi-mk.p i::after{border-color:#FBBF24}
@keyframes fiMark{from{transform:scale(0) rotate(-120deg)}to{transform:scale(1) rotate(0)}}
@keyframes fiRipple{0%{transform:scale(1);opacity:.9}100%{transform:scale(1.9);opacity:0}}

.fi-pop{position:absolute;top:-42px;left:-50px;white-space:nowrap;padding:5px 14px;border-radius:999px;background:#10B981;border:1px solid #fff;
    color:#fff;font-size:12px;font-weight:900;box-shadow:0 12px 22px rgba(0,0,0,.4)}
.fi-clock{display:flex;flex-direction:column;align-items:center}
.fi-clock .e{font-size:60px;filter:drop-shadow(0 10px 14px rgba(0,0,0,.5))}
.fi-clock span{margin-top:4px;padding:2px 10px;border-radius:999px;background:rgba(0,0,0,.8);color:#FCD34D;font-size:12px;font-weight:900}
.fi-flower{display:flex;flex-direction:column;align-items:center}
.fi-flower .e{font-size:48px}
.fi-flower .r{display:flex;gap:4px;margin-top:4px;font-size:15px}
.fi-spark{font-size:48px}

/* legend */
.fi-legend{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;margin-top:20px;padding-top:18px;border-top:1px dashed #A5F3FC}
.fi-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;border:1.5px solid #E2E8F0;background:#F1F5F9;color:#64748B;
    font-size:12px;font-weight:900;transition:.3s}
.fi-chip.me{background:#D1FAE5;border-color:#6EE7B7;color:#065F46;box-shadow:0 8px 14px -8px #10B981;animation:fiPop .45s cubic-bezier(.2,1.4,.3,1) both}
.fi-chip.pt{background:#FEF3C7;border-color:#FCD34D;color:#78350F;box-shadow:0 8px 14px -8px #F59E0B;animation:fiPop .45s cubic-bezier(.2,1.4,.3,1) both}

/* ───────── Chat ───────── */
.fi-chat{display:flex;flex-direction:column;height:min(640px,calc(100vh - 130px));min-height:460px;overflow:hidden;border-radius:28px;border:2px solid #075E54;background:#E5DDD5;
    box-shadow:0 30px 56px -28px rgba(7,94,84,.8);animation:fiUp .55s .16s both}
@media (min-width:1100px){.fi-chat{position:sticky;top:96px}}
@media (max-width:1099px){.fi-chat{height:520px}}
.fi-chat-h{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;background:#075E54;color:#fff;box-shadow:0 4px 10px rgba(0,0,0,.25)}
.fi-chat-h .l{display:flex;align-items:center;gap:10px;min-width:0}
.fi-chat-av{position:relative;flex:none}
.fi-chat-av .fi-av{width:40px;height:40px;border:2px solid #fff;background:#047857}
.fi-chat-av .s{position:absolute;right:0;bottom:0;width:12px;height:12px;border-radius:50%;border:2px solid #fff;background:#9CA3AF}
.fi-chat-av .s.on{background:#34D399}
.fi-chat-h .n{font-size:14px;font-weight:900;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fi-chat-h .st{font-size:10.5px;font-weight:700;opacity:.9}
.fi-wa{flex:none;display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:999px;background:#10B981;color:#fff;font-size:11px;font-weight:900;transition:.2s}
.fi-wa:hover{background:#059669;transform:scale(1.06)}
.fi-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;scrollbar-width:thin;
    background-image:radial-gradient(#cbd5e1 1px,transparent 0);background-size:16px 16px}
.fi-sys{text-align:center}
.fi-sys span{display:inline-block;padding:5px 12px;border-radius:10px;background:#FCF4CB;border:1px solid #F7E8A4;color:#54656F;font-size:10.5px;font-weight:800;box-shadow:0 2px 4px rgba(0,0,0,.08)}
.fi-sys.m span{background:#FEF3C7;border-color:#FCD34D;color:#78350F}
.fi-row{display:flex;flex-direction:column;align-items:flex-start;animation:fiUp .35s both}
.fi-row.me{align-items:flex-end}
.fi-bub2{max-width:84%;padding:8px 13px;border-radius:16px 16px 16px 3px;background:#fff;color:#111827;font-size:12.5px;font-weight:600;box-shadow:0 2px 5px rgba(0,0,0,.12)}
.fi-row.me .fi-bub2{background:#DCF8C6;color:#075E54;border-radius:16px 16px 3px 16px}
.fi-bub2 em{display:block;margin-top:2px;text-align:right;font-style:normal;font-size:9.5px;font-weight:800;color:#6B7280}
.fi-quick{display:flex;gap:6px;padding:8px;overflow-x:auto;background:#fff;border-top:1px solid #D1D5DB;scrollbar-width:none}
.fi-quick::-webkit-scrollbar{display:none}
.fi-quick button{flex:none;padding:6px 12px;border-radius:999px;border:1px solid #E5E7EB;background:#fff;color:#1E293B;font-size:11.5px;font-weight:900;
    box-shadow:0 2px 4px rgba(0,0,0,.06);transition:.15s}
.fi-quick button:hover{background:#ECFDF5;transform:translateY(-2px)}
.fi-quick button:active{transform:scale(.93)}
.fi-form{display:flex;align-items:center;gap:8px;padding:10px;background:#F3F4F6;border-top:1px solid #D1D5DB}
.fi-form input{flex:1;min-width:0;padding:10px 16px;border-radius:999px;border:1px solid #D1D5DB;background:#fff;font-size:12.5px;font-family:inherit;box-shadow:inset 0 1px 3px rgba(0,0,0,.08)}
.fi-form input:focus{outline:none;border-color:#059669;box-shadow:0 0 0 3px #D1FAE5}
.fi-send{flex:none;display:grid;place-items:center;width:42px;height:42px;border:0;border-radius:50%;background:#059669;color:#fff;font-weight:900;
    box-shadow:0 8px 14px -6px #059669;transition:.2s}
.fi-send:hover{background:#047857;transform:scale(1.1) rotate(-12deg)}

/* ───────── Modals ───────── */
.fi-ov{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(2,6,23,.7);backdrop-filter:blur(8px)}
.fi-ov.result{z-index:100}
.fi-ov.recv{z-index:60;background:rgba(2,6,23,.6)}
.fi-dlg{position:relative;width:100%;max-width:440px;padding:30px 26px 26px;border-radius:30px;background:#fff;border:2px solid #67E8F9;text-align:center;
    box-shadow:0 40px 80px -20px rgba(0,0,0,.6);animation:fiPop .45s cubic-bezier(.2,1.2,.3,1) both}
.fi-big{font-size:64px;line-height:1;animation:fiBob 1s ease-in-out infinite}
.fi-dlg h2{margin:14px 0 0;font-size:26px;font-weight:900;color:#0F172A}
.fi-dlg p{margin:6px 0 0;font-size:14px;font-weight:700;color:#475569}
.fi-dlg p b{color:#0E7490;font-weight:900}
.fi-scores{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px}
.fi-sc{padding:14px;border-radius:20px;border:1px solid}
.fi-sc.me{background:#ECFDF5;border-color:#A7F3D0}
.fi-sc.pt{background:#FFFBEB;border-color:#FDE68A}
.fi-sc small{display:block;font-size:10.5px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}
.fi-sc.me small{color:#065F46}.fi-sc.pt small{color:#92400E}
.fi-sc strong{display:block;margin-top:2px;font-size:30px;font-weight:900}
.fi-sc.me strong{color:#047857}.fi-sc.pt strong{color:#B45309}
.fi-comb{margin-top:14px;padding:16px;border-radius:22px;background:linear-gradient(135deg,#ECFEFF,#E0F2FE);border:2px solid #A5F3FC}
.fi-comb small{display:block;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#155E75}
.fi-comb strong{display:block;font-size:46px;font-weight:900;line-height:1.1;background:linear-gradient(135deg,#0891B2,#8B5CF6);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.fi-dact{display:flex;gap:10px;margin-top:20px}
.fi-dact button{flex:1;padding:14px 10px;border-radius:16px;font-size:14px;font-weight:900;transition:.2s}
.fi-dact button:hover{transform:translateY(-3px)}
.fi-dact .a{border:1px solid #67E8F9;background:#CFFAFE;color:#164E63}
.fi-dact .b{border:0;background:linear-gradient(135deg,#0891B2,#0E7490);color:#fff;box-shadow:0 14px 24px -10px #0891B2}
.fi-conf{position:absolute;top:-10px;width:9px;height:15px;border-radius:2px;opacity:0;animation:fiConf 2.6s ease-in var(--dl) infinite;pointer-events:none}
@keyframes fiConf{0%{transform:translateY(0) rotate(0);opacity:1}100%{transform:translateY(120vh) rotate(720deg);opacity:.9}}

.fi-rh{display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #E2E8F0;text-align:left}
.fi-rh h3{margin:0;display:flex;align-items:center;gap:8px;font-size:17px;font-weight:900}
.fi-rh button{width:34px;height:34px;border:0;border-radius:50%;background:#F1F5F9;color:#64748B;font-weight:900;transition:.2s}
.fi-rh button:hover{background:#CFFAFE;color:#0E7490;transform:rotate(90deg)}
.fi-rl{display:flex;flex-direction:column;gap:10px;max-height:270px;margin-top:14px;overflow-y:auto;text-align:left}
.fi-none{padding:24px;text-align:center;font-size:12.5px;font-weight:700;color:#64748B}
.fi-inv{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px;border-radius:18px;background:#ECFEFF;border:1px solid #A5F3FC}
.fi-inv b{display:block;font-size:12.5px;font-weight:900;color:#0F172A}
.fi-inv small{font-size:10.5px;font-weight:800;color:#0E7490}
.fi-inv .g{display:flex;gap:6px;flex:none}
.fi-inv button{padding:8px 14px;border:0;border-radius:12px;font-size:12px;font-weight:900;transition:.2s}
.fi-inv .ok{background:#0E7490;color:#fff;display:inline-flex;align-items:center;gap:5px}
.fi-inv .ok:hover{background:#155E75}
.fi-inv .no{background:#E5E7EB;color:#374151}
.fi-spin{display:inline-block;animation:fiSpin 1s linear infinite}

@media (prefers-reduced-motion:reduce){
    .fi *,.fi *::before,.fi *::after{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition:none !important}
}
</style>

<div class="fi" x-data="findItGame()">

    <div class="fi-bg" aria-hidden="true">
        <span class="fi-bub"></span><span class="fi-bub"></span><span class="fi-bub"></span><span class="fi-bub"></span><span class="fi-bub"></span>
    </div>

    <div class="fi-wrap">

        {{-- Toast Alert Notification (Top Center Floating Banner) --}}
        <div x-cloak x-show="toastMessage" class="fi-toast">
            <span class="e">📢</span>
            <span x-text="toastMessage"></span>
        </div>

        {{-- ───────────── TOP ACTION BAR — ONLINE CHECK, TIMER, FOUND, INVITES ───────────── --}}
        <div class="fi-bar">

            {{-- Left: Exit & Partner Info --}}
            <div class="fi-grp">
                <a href="{{ route('games.hub', $partner->id) }}" class="fi-exit">← Exit</a>

                <div class="fi-partner">
                    {{-- Only when the partner's photo privacy lets this member see it --}}
                    @if(!empty($partnerPhotoUrl))
                        <img src="{{ $partnerPhotoUrl }}" alt="{{ $partner->name ?? 'Partner' }}" class="fi-av" onerror="this.style.display='none'">
                    @else
                        <div class="fi-av">{{ strtoupper(substr($partner->name ?? 'P', 0, 1)) }}</div>
                    @endif
                    <div>
                        <div class="fi-pname">{{ $partner->name }}</div>
                        <div class="fi-pst" :class="partnerPresence.is_online ? 'on' : ''">
                            <span class="fi-dot"></span>
                            <span x-text="partnerPresence.label"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Middle: START GAME BUTTON & Differences Counter --}}
            <div class="fi-grp">
                <template x-if="!gameStarted && !gameEnded">
                    <button type="button" @click="startGame()" class="fi-start">
                        <span>▶</span>
                        <span>START GAME</span>
                    </button>
                </template>

                <template x-if="gameStarted && !gameEnded">
                    <span class="fi-live">Live Playing</span>
                </template>

                <div class="fi-pill timer" x-show="timeLimit > 0 && (gameStarted || gameEnded)">
                    <small>Time</small>
                    <b :class="timeLeft <= 10 ? 'danger' : ''" x-text="`${timeLeft}s`"></b>
                </div>

                <div class="fi-pill found">
                    <small>Differences</small>
                    <b x-text="`${foundIndices.length}/${totalDifferences}`"></b>
                </div>
            </div>

            {{-- Right: INVITE LINK & RECEIVED INVITES --}}
            <div class="fi-grp">
                <button type="button" @click="sendOrCopyInvite()" class="fi-btn invite">
                    <span>💌</span><span>Invite Partner</span>
                </button>

                <button type="button" @click="showReceivedModal = true" class="fi-btn recv">
                    <span>📥</span><span>Received</span>
                    <span x-show="pendingInvitesCount > 0" class="fi-badge" x-text="pendingInvitesCount"></span>
                </button>
            </div>
        </div>

        {{-- ───────────── MAIN SPLIT GRID: GAME & LIVE CHAT ───────────── --}}
        <div class="fi-layout">

            {{-- ───────────── FIND IT GAME ───────────── --}}
            <div class="fi-game">

                <div class="fi-ghead">
                    <div class="fi-chal">
                        <span class="fi-chal-ico">🔍</span>
                        <div>
                            <small>Challenge</small>
                            <strong x-text="challenge.title"></strong>
                        </div>
                    </div>
                    <span class="fi-hint">Tap differences on Image B or Image A! ⏰ 🐦 🌸</span>
                </div>

                <div class="fi-imgs">

                    {{-- Image A (Original Image) --}}
                    <div class="fi-frame" @click="handleTap($event, 'A')">
                        <div class="fi-inner">

                            <div x-show="!gameStarted && !gameEnded" class="fi-lock">
                                <span class="t">🔒 Press START to reveal the images!</span>
                                <button type="button" class="fi-start" @click.stop="startGame()"><span>▶</span><span>START GAME</span></button>
                            </div>

                            <div class="fi-pic" :class="!gameStarted ? 'is-locked' : ''">
                                <span class="fi-tag">Image A (Original)</span>

                                {{-- Display Real Uploaded Image --}}
                                <template x-if="hasUploadedImages">
                                    <img :src="challenge.image_a || '/image/02_Koodal_Find_It.png'" id="imgFindItA" @load="onImageLoad('A')" alt="Image A" onerror="this.src='/image/02_Koodal_Find_It.png'" draggable="false">
                                </template>

                                {{-- Fallback Canvas --}}
                                <template x-if="!hasUploadedImages">
                                    <svg viewBox="0 0 400 300">
                                        <rect width="400" height="300" fill="#1e1b4b" />
                                        <path d="M 0 200 Q 100 120 200 200 T 400 200 L 400 300 L 0 300 Z" fill="#312e81" />
                                        <circle cx="330" cy="70" r="35" fill="#fef08a" opacity="0.9" />
                                        <polygon points="120,80 150,160 90,160" fill="#4338ca" />
                                        <polygon points="200,100 225,170 175,170" fill="#4338ca" />
                                        <circle cx="80" cy="220" r="18" fill="#fbbf24" />
                                        <g transform="translate(268, 75)">
                                            <path d="M 0 0 Q 5 -10 12 -5 Q 18 0 15 8 Q 10 12 0 8 Z" fill="#38bdf8" />
                                            <path d="M 3 -3 Q 8 -15 15 -10" stroke="#0284c7" stroke-width="2" fill="none" class="animate-bird-wing" />
                                        </g>
                                        <rect x="250" y="190" width="40" height="50" fill="#047857" rx="5" />
                                        <circle cx="270" cy="180" r="12" fill="#f43f5e" />
                                        <polygon points="320,65 325,75 315,75" fill="#e11d48" />
                                        <rect x="50" y="240" width="15" height="15" fill="#f59e0b" />
                                        <circle cx="140" cy="230" r="8" fill="#38bdf8" />
                                    </svg>
                                </template>

                                {{-- Found Markers on A --}}
                                <template x-for="(diff, idx) in challenge.differences" :key="`a-${idx}`">
                                    <div x-show="foundIndices.includes(idx)" class="fi-mk" :class="claimedByMe.includes(idx) ? '' : 'p'" :style="`left: ${diff.x}%; top: ${diff.y}%;`">
                                        <i>✓</i>
                                    </div>
                                </template>

                                {{-- Floating Animations Overlay on Image A --}}
                                <template x-for="anim in activeAnimations" :key="`anim-a-${anim.id}`">
                                    <div class="fi-anim" :style="`left: ${anim.x}%; top: ${anim.y}%;`">
                                        <div class="fi-pop animate-pop-findit">FIND IT! ✨</div>

                                        <template x-if="anim.type === 'clock'">
                                            <div class="animate-clock-zoom fi-clock">
                                                <div class="e">⏰</div>
                                                <span>Tower Clock!</span>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'bird'">
                                            <div class="animate-bird-fly">
                                                <svg width="48" height="48" viewBox="0 0 40 40">
                                                    <path d="M 10 20 Q 20 10 30 18 Q 35 25 25 28 Q 15 30 10 20 Z" fill="#0EA5E9" />
                                                    <path d="M 18 16 Q 12 2 28 8 Q 22 15 18 16 Z" fill="#38BDF8" class="animate-bird-wing" />
                                                    <polygon points="30,20 38,18 32,23" fill="#F59E0B" />
                                                    <polygon points="5,20 10,18 10,22" fill="#0284C7" />
                                                </svg>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'flower'">
                                            <div class="animate-flower-fall fi-flower">
                                                <div class="e">🌸</div>
                                                <div class="r"><span>🌸</span><span>🌺</span><span>🌸</span></div>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'general' || !anim.type">
                                            <div class="animate-sparkle-pulse fi-spark">✨</div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Image B (With Differences) --}}
                    <div class="fi-frame b" @click="handleTap($event, 'B')">
                        <div class="fi-inner">

                            <div x-show="!gameStarted && !gameEnded" class="fi-lock">
                                <span class="t">🔒 Press START to reveal the images!</span>
                                <button type="button" class="fi-start" @click.stop="startGame()"><span>▶</span><span>START GAME</span></button>
                            </div>

                            <div class="fi-pic" :class="!gameStarted ? 'is-locked' : ''">
                                <span class="fi-tag">Image B (Spot Changes)</span>

                                {{-- Display Real Uploaded Image --}}
                                <template x-if="hasUploadedImages">
                                    <img :src="challenge.image_b || '/image/02_Koodal_Find_It.png'" id="imgFindItB" @load="onImageLoad('B')" alt="Image B" onerror="this.src='/image/02_Koodal_Find_It.png'" draggable="false">
                                </template>

                                {{-- Fallback Canvas --}}
                                <template x-if="!hasUploadedImages">
                                    <svg viewBox="0 0 400 300">
                                        <rect width="400" height="300" fill="#1e1b4b" />
                                        <path d="M 0 200 Q 100 120 200 200 T 400 200 L 400 300 L 0 300 Z" fill="#312e81" />
                                        <circle cx="330" cy="70" r="35" fill="#fef08a" opacity="0.9" />
                                        <polygon points="120,80 150,160 90,160" fill="#4338ca" />
                                        <polygon points="200,100 225,170 175,170" fill="#4338ca" />

                                        <g transform="translate(148, 75)" x-show="!foundIndices.includes(1)">
                                            <path d="M 0 0 Q 5 -8 10 -4 Q 15 0 12 6 Q 8 10 0 6 Z" fill="#f43f5e" />
                                            <path d="M 2 -2 Q 6 -12 12 -8" stroke="#be123c" stroke-width="2" fill="none" class="animate-bird-wing" />
                                        </g>

                                        <rect x="250" y="190" width="40" height="50" fill="#7e22ce" rx="5" />
                                        <polygon points="320,55 335,65 320,75" fill="#10b981" />
                                        <circle cx="140" cy="230" r="8" fill="#facc15" />
                                        <rect x="90" y="240" width="15" height="15" fill="#f59e0b" />
                                        <circle cx="200" cy="130" r="7" fill="#ffffff" stroke="#0891b2" stroke-width="2" />
                                    </svg>
                                </template>

                                {{-- Found Markers on B --}}
                                <template x-for="(diff, idx) in challenge.differences" :key="`b-${idx}`">
                                    <div x-show="foundIndices.includes(idx)" class="fi-mk" :class="claimedByMe.includes(idx) ? '' : 'p'" :style="`left: ${diff.x}%; top: ${diff.y}%;`">
                                        <i>✓</i>
                                    </div>
                                </template>

                                {{-- Floating Animations Overlay on Image B --}}
                                <template x-for="anim in activeAnimations" :key="`anim-b-${anim.id}`">
                                    <div class="fi-anim" :style="`left: ${anim.x}%; top: ${anim.y}%;`">
                                        <div class="fi-pop animate-pop-findit">FIND IT! ✨</div>

                                        <template x-if="anim.type === 'clock'">
                                            <div class="animate-clock-zoom fi-clock">
                                                <div class="e">⏰</div>
                                                <span>Tower Clock!</span>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'bird'">
                                            <div class="animate-bird-fly">
                                                <svg width="48" height="48" viewBox="0 0 40 40">
                                                    <path d="M 10 20 Q 20 10 30 18 Q 35 25 25 28 Q 15 30 10 20 Z" fill="#F43F5E" />
                                                    <path d="M 18 16 Q 12 2 28 8 Q 22 15 18 16 Z" fill="#FB7185" class="animate-bird-wing" />
                                                    <polygon points="30,20 38,18 32,23" fill="#F59E0B" />
                                                    <polygon points="5,20 10,18 10,22" fill="#9F1239" />
                                                </svg>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'flower'">
                                            <div class="animate-flower-fall fi-flower">
                                                <div class="e">🌸</div>
                                                <div class="r"><span>🌸</span><span>🌺</span><span>🌸</span></div>
                                            </div>
                                        </template>

                                        <template x-if="anim.type === 'general' || !anim.type">
                                            <div class="animate-sparkle-pulse fi-spark">✨</div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        {{-- ───────────── RESULT MODAL (Shows only when all differences found) ───────────── --}}
        <div x-cloak x-show="gameEnded" class="fi-ov result">
            @for ($i = 0; $i < 26; $i++)
                <span class="fi-conf" style="left: {{ ($i * 41) % 100 }}%; --dl: {{ ($i % 7) * 0.16 }}s; background: {{ ['#0891B2','#EC4899','#F59E0B','#10B981','#8B5CF6'][$i % 5] }}"></span>
            @endfor
            <div class="fi-dlg">
                <div class="fi-big">🎉</div>
                <div>
                    <h2 x-text="allFound ? 'All Differences Found! 🏆' : 'Time\'s Up! ⏰'"></h2>
                    <p x-show="allFound">Successfully spotted all <b x-text="totalDifferences"></b> differences!</p>
                    <p x-show="!allFound">Together you spotted <b x-text="foundIndices.length"></b> of <b x-text="totalDifferences"></b> differences.</p>
                </div>

                {{-- Score Breakdown per player --}}
                <div class="fi-scores">
                    <div class="fi-sc me">
                        <small>You Spotted</small>
                        <strong x-text="claimedByMe.length"></strong>
                    </div>
                    <div class="fi-sc pt">
                        <small x-text="`${partnerName} Spotted`"></small>
                        <strong x-text="claimedByPartner.length"></strong>
                    </div>
                </div>

                <div class="fi-comb">
                    <small>Combined Game Score</small>
                    <strong x-text="score === null ? '…' : `${score}%`"></strong>
                </div>

                {{-- Action Buttons: Replay Game, Next Game, Game Hub, Vibe Meter --}}
                <div class="fi-dact" style="display: flex; flex-direction: column; gap: 10px; margin-top: 18px;">
                    <button type="button" @click="replayGame()" :disabled="sendingReplayInvite"
                            class="b"
                            style="width: 100%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; background: linear-gradient(135deg, #10B981, #059669); color: #ffffff; text-decoration: none; padding: 12px; border-radius: 14px; font-weight: 900; font-size: 13px; box-shadow: 0 4px 12px rgba(16,185,129,.35);">
                        <span x-text="sendingReplayInvite ? '⏳ Replay invite sent! Waiting for partner...' : '🔄 Replay Game (Fresh Match with Partner)'"></span>
                    </button>
                    <button type="button" @click="playNextGame('memory_snap')" :disabled="sendingNextInvite"
                            class="b"
                            style="width: 100%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; background: linear-gradient(135deg, #7C3AED, #6D28D9); color: #ffffff; text-decoration: none; padding: 12px; border-radius: 14px; font-weight: 900; font-size: 13px; box-shadow: 0 4px 12px rgba(124,58,237,.35);">
                        <span x-text="sendingNextInvite ? '⏳ Sending Invite & Opening Memory Snap...' : '🎮 Next Game (Memory Snap) →'"></span>
                    </button>
                    <div style="display: flex; gap: 10px;">
                        <a href="{{ route('games.hub', $partner->id) }}"
                           class="a"
                           style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; padding: 10px; border-radius: 14px; font-weight: 800; font-size: 12px; border: 1.5px solid #CBD5E1; color: #334155; background: #F8FAFC;">
                            <span>🏠 Game Hub</span>
                        </a>
                        <a href="{{ route('games.vibe', $partner->id) }}"
                           class="a"
                           style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; padding: 10px; border-radius: 14px; font-weight: 800; font-size: 12px; border: 1.5px solid #FBCFE8; color: #D6246E; background: #FFF0F5;">
                            <span>💙 Vibe Meter</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────────── RECEIVED INVITES MODAL ───────────── --}}
        <div x-cloak x-show="showReceivedModal" class="fi-ov recv">
            <div class="fi-dlg" style="text-align:left;padding:24px" @click.away="showReceivedModal = false">
                <div class="fi-rh">
                    <h3><span>📥</span> Received Game Invitations</h3>
                    <button type="button" @click="showReceivedModal = false" aria-label="Close">✕</button>
                </div>

                <div class="fi-rl">
                    <template x-if="pendingInvites.length === 0">
                        <div class="fi-none">No pending invitations at the moment.</div>
                    </template>

                    <template x-for="inv in pendingInvites" :key="inv.id">
                        <div class="fi-inv">
                            <div>
                                <b x-text="`${inv.sender_name} invited you!`"></b>
                                <small x-text="`Game: ${inv.game_type} • ${inv.created_at}`"></small>
                            </div>
                            <div class="g">
                                <button type="button" @click="acceptInvite(inv.id)" :disabled="isAccepting === inv.id" class="ok">
                                    <span x-show="isAccepting === inv.id" class="fi-spin">⏳</span>
                                    <span x-text="isAccepting === inv.id ? 'Joining...' : 'Accept'"></span>
                                </button>
                                <button type="button" @click="declineInvite(inv.id)" class="no">Decline</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    {{-- Floating Game Chat Icon (Default reactions + Paid custom chat) --}}
    <x-floating-chat />
</div>

{{-- ═════════════════════ ORIGINAL LOGIC (unchanged) ═════════════════════ --}}
<script>
function findItGame() {
    return {
        userId: {{ $user->id ?? 0 }},
        partnerId: {{ $partner->id ?? 0 }},
        partnerName: '{{ addslashes($partner->name ?? "Partner") }}',
        partnerPresence: @json($partnerPresence ?? ['is_online' => false, 'label' => 'Offline']),
        challenge: @json($challenge ?? []),
        roomCode: '{{ $room->room_code ?? "" }}',
        roomUserAId: {{ $room->user_a_id ?? 0 }},
        foundIndices: [],
        claimedByMe: [],
        claimedByPartner: [],
        activeAnimations: [],
        timeLimit: {{ (int) ($timeLimit ?? 60) }},
        timeLeft: {{ (int) ($timeLimit ?? 60) }},
        timer: null,
        timeUpNudge: null,
        pendingClaims: [],
        allFound: false,
        gameStarted: false,
        imageALoaded: false,
        imageBLoaded: false,
        gameEnded: false,
        score: null,
        toastMessage: '',
        toastTimer: null,
        showReceivedModal: false,
        isAccepting: null,
        pendingInvites: [],
        pendingInvitesCount: 0,
        chatMessages: [],
        newMessage: '',
        sendingNextInvite: false,
        sendingReplayInvite: false,

        get totalDifferences() {
            return (this.challenge.differences && this.challenge.differences.length) ? Math.min(7, this.challenge.differences.length) : 7;
        },

        get hasUploadedImages() {
            return this.challenge && this.challenge.image_a && this.challenge.image_a !== '/image/02_Koodal_Find_It.png';
        },

        get isImagesReady() {
            if (!this.hasUploadedImages) return true;
            return this.imageALoaded && this.imageBLoaded;
        },

        cleanIndices(arr) {
            if (!Array.isArray(arr)) return [];
            const valid = [];
            arr.forEach(item => {
                let val = null;
                if (typeof item === 'number') {
                    val = item;
                } else if (typeof item === 'string' && !isNaN(item)) {
                    val = parseInt(item, 10);
                } else if (item && typeof item === 'object' && item.difference_index !== undefined) {
                    val = parseInt(item.difference_index, 10);
                }
                if (val !== null && !isNaN(val) && val >= 0 && val < this.totalDifferences) {
                    if (!valid.includes(val)) valid.push(val);
                }
            });
            return valid;
        },

        init() {
            this.fetchPendingInvites();
            if (this.roomCode) {
                this.pollChat();
                setInterval(() => this.pollChat(), 2500);
            }
            this.checkImagesLoaded();

            // The round's clock is the room's (enforced on the server; none
            // when timeLimit is 0); this only counts it down between polls,
            // which re-sync it.
            this.timer = setInterval(() => {
                if (this.timeLimit <= 0 || !this.gameStarted || this.gameEnded || this.timeLeft <= 0) return;
                this.timeLeft--;
                if (this.timeLeft === 0) this.onTimeUp();
            }, 1000);

            window.addEventListener('koodal-quick-chat-send', (e) => {
                if (e.detail && e.detail.message && this.roomCode) {
                    this.newMessage = e.detail.message;
                    this.sendChatMessage(e.detail.type || 'quick_reply');
                }
            });
        },

        checkImagesLoaded() {
            if (!this.hasUploadedImages) {
                this.imageALoaded = true;
                this.imageBLoaded = true;
                return;
            }
            this.$nextTick(() => {
                const imgA = document.getElementById('imgFindItA');
                const imgB = document.getElementById('imgFindItB');
                if (imgA && (imgA.complete || imgA.naturalWidth > 0)) this.imageALoaded = true;
                if (imgB && (imgB.complete || imgB.naturalWidth > 0)) this.imageBLoaded = true;
            });
        },

        onImageLoad(side) {
            if (side === 'A') this.imageALoaded = true;
            if (side === 'B') this.imageBLoaded = true;
        },

        startGame() {
            if (this.gameStarted || this.gameEnded) return;
            this.gameStarted = true;
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playCountdown('go');
            }

            if (this.roomCode) {
                fetch(`/games/room/${this.roomCode}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d && typeof d.seconds_left === 'number') this.timeLeft = d.seconds_left;
                    if (d && d.status === 'completed') this.pollChat();
                })
                .catch(() => {});
            }
        },

        onTimeUp() {
            // The server keeps a few seconds' grace for claims still in flight,
            // then closes the round; ask for the result once that has passed.
            if (this.timeUpNudge) return;
            this.showToast("⏰ Time's up! Counting your differences…");
            this.timeUpNudge = setTimeout(() => {
                this.timeUpNudge = null;
                this.submitScoreSilently();
            }, 3500);
        },

        showToast(msg) {
            this.toastMessage = msg;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastMessage = '';
            }, 3500);
        },

        handleTap(event, side) {
            if (!this.gameStarted || this.gameEnded) return;

            const imgEl = side === 'A' ? document.getElementById('imgFindItA') : document.getElementById('imgFindItB');
            const targetEl = imgEl || event.currentTarget;
            const rect = targetEl.getBoundingClientRect();

            if (!rect.width || !rect.height) return;

            const clickX = ((event.clientX - rect.left) / rect.width) * 100;
            const clickY = ((event.clientY - rect.top) / rect.height) * 100;

            let matchedIdx = -1;
            let matchedDiff = null;

            (this.challenge.differences || []).forEach((diff, idx) => {
                const dist = Math.hypot(clickX - diff.x, clickY - diff.y);
                if (dist < 14) {
                    matchedIdx = idx;
                    matchedDiff = diff;
                }
            });

            if (matchedIdx !== -1 && matchedDiff) {
                // 1. Check if partner already claimed this difference!
                if (this.claimedByPartner.includes(matchedIdx)) {
                    this.showToast(`${this.partnerName} already found this! You find remaining differences.`);
                    return;
                }

                // 2. Check if user already claimed this difference
                if (this.claimedByMe.includes(matchedIdx)) {
                    return;
                }

                // 3. Claim difference for current user (shown at once; the
                //    room decides, and a refused claim is taken back below)
                this.claimedByMe.push(matchedIdx);
                this.pendingClaims.push(matchedIdx);
                this.foundIndices = this.cleanIndices([...this.foundIndices, matchedIdx]);
                if (window.KoodalGameAudio) {
                    window.KoodalGameAudio.playMatchSuccess();
                }

                const animId = Date.now() + Math.random();
                const animObj = {
                    id: animId,
                    x: matchedDiff.x,
                    y: matchedDiff.y,
                    type: matchedDiff.type || 'general'
                };
                this.activeAnimations.push(animObj);

                setTimeout(() => {
                    this.activeAnimations = this.activeAnimations.filter(a => a.id !== animId);
                }, 2400);

                // 4. Sync claim to backend room if playing live
                if (this.roomCode) {
                    fetch(`/games/room/${this.roomCode}/claim-find-it`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            difference_index: matchedIdx,
                            difference_name: matchedDiff.name || `Diff #${matchedIdx + 1}`
                        })
                    })
                    .then(r => r.json())
                    .then(d => {
                        this.pendingClaims = this.pendingClaims.filter(i => i !== matchedIdx);
                        if (!d.success) {
                            this.claimedByMe = this.claimedByMe.filter(i => i !== matchedIdx);
                            this.foundIndices = this.cleanIndices([...this.claimedByMe, ...this.claimedByPartner]);
                            if (d.message) this.showToast(d.message);
                        }
                        if (d.user_a_answers && d.user_b_answers) {
                            this.applyServerState(d);
                        }
                        // The room closes the round once every spot is claimed.
                        if (d.status === 'completed') {
                            this.endGame(d.score);
                        }
                        this.pollChat();
                    })
                    .catch(() => {
                        this.pendingClaims = this.pendingClaims.filter(i => i !== matchedIdx);
                    });
                }
            } else {
                if (window.KoodalGameAudio) {
                    window.KoodalGameAudio.playSelect();
                }
            }
        },

        sendOrCopyInvite() {
            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'find_it' })
            })
            .then(r => r.json())
            .then(d => {
                if (d.room_code) {
                    this.roomCode = d.room_code;
                }

                // Lock game state to lobby while waiting for receiver (the
                // countdown ticker keeps running; it only counts a live round).
                this.gameStarted = false;
                this.gameEnded = false;
                this.timeLeft = this.timeLimit;
                this.score = null;
                this.claimedByMe = [];
                this.claimedByPartner = [];
                this.foundIndices = [];

                const inviteUrl = this.roomCode 
                    ? `${window.location.origin}/games/room/${this.roomCode}` 
                    : window.location.href;

                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(inviteUrl).then(() => {
                            this.showToast(`📢 💌 Invitation sent to ${this.partnerName}! Link copied to clipboard 📋`);
                        }).catch(() => {
                            this.showToast(`📢 💌 Invitation sent to ${this.partnerName}! 🚀`);
                        });
                    } else {
                        this.showToast(`📢 💌 Invitation sent to ${this.partnerName}! 🚀`);
                    }
                } catch (e) {
                    this.showToast(`📢 💌 Invitation sent to ${this.partnerName}! 🚀`);
                }
            })
            .catch(() => {
                this.showToast(`📢 💌 Invitation sent to ${this.partnerName}! 🚀`);
            });
        },

        fetchPendingInvites() {
            fetch('/games/invites/pending')
                .then(r => r.json())
                .then(d => {
                    this.pendingInvites = d.invites || [];
                    this.pendingInvitesCount = d.count || 0;

                    if (d.accepted_redirect && !window.location.href.includes('/games/room/') && !window.location.href.includes('/flip/')) {
                        window.location.href = d.accepted_redirect;
                    }
                });
        },

        acceptInvite(inviteId) {
            this.isAccepting = inviteId;
            fetch(`/games/invites/${inviteId}/accept`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(d => {
                if (d.redirect) {
                    window.location.href = d.redirect;
                } else {
                    this.isAccepting = null;
                }
            })
            .catch(() => {
                this.isAccepting = null;
            });
        },

        declineInvite(inviteId) {
            fetch(`/games/invites/${inviteId}/decline`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(() => {
                this.fetchPendingInvites();
            });
        },

        pollChat() {
            this.fetchPendingInvites();
            if (!this.roomCode) return;

            fetch(`/games/room/${this.roomCode}/poll`)
                .then(r => r.json())
                .then(d => {
                    if (d.messages) {
                        this.chatMessages = d.messages;
                        if (window.syncKoodalChatMessages) {
                            window.syncKoodalChatMessages(this.chatMessages, this.userId, this.partnerName);
                        }
                    }

                    // 1. Sync game start state across both players!
                    if (d.status === 'playing') {
                        if (!this.gameStarted && !this.gameEnded) {
                            this.gameStarted = true;
                        }
                        if (typeof d.seconds_left === 'number') {
                            this.timeLeft = d.seconds_left;
                            if (d.seconds_left === 0) this.onTimeUp();
                        }
                    } else if (d.status === 'lobby') {
                        if (!this.gameEnded) {
                            this.gameStarted = false;
                            this.score = null;
                            this.timeLeft = this.timeLimit;
                        }
                    }

                    this.applyServerState(d);

                    // The room closes the round (all found, or the clock);
                    // the score shown is the one it recorded.
                    if (d.status === 'completed' && !this.gameEnded) {
                        this.endGame(d.score);
                    }
                });
        },

        applyServerState(d) {
            const userAFound = this.cleanIndices(d.user_a_answers);
            const userBFound = this.cleanIndices(d.user_b_answers);

            const isUserA = (this.userId === this.roomUserAId);
            const myAnswers = isUserA ? userAFound : userBFound;
            const partnerAnswers = isUserA ? userBFound : userAFound;

            // The room's list, plus taps whose claim is still on its way.
            this.claimedByMe = this.cleanIndices([...myAnswers, ...this.pendingClaims]);
            this.claimedByPartner = this.cleanIndices(partnerAnswers);

            this.foundIndices = this.cleanIndices([...this.claimedByMe, ...this.claimedByPartner]);
        },

        sendQuickReaction(emojiMsg) {
            if (window.triggerKoodalCelebration) {
                window.triggerKoodalCelebration(emojiMsg);
            }
            if (window.pushKoodalChatStream) {
                window.pushKoodalChatStream(emojiMsg, true);
            }
            this.newMessage = emojiMsg;
            this.sendChatMessage('quick_reply');
        },

        sendChatMessage(msgType = 'text') {
            if (!this.newMessage.trim() || !this.roomCode) return;
            const text = this.newMessage;
            this.newMessage = '';

            fetch(`/games/room/${this.roomCode}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message: text, type: msgType })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.pollChat();
                }
            })
            .catch(err => console.error('Find It send chat error:', err));
        },

        restartGame() {
            if (!this.roomCode) {
                window.location.reload();
                return;
            }
            fetch(`/games/room/${this.roomCode}/reset`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(d => {
                if (d.redirect) {
                    window.location.href = d.redirect;
                } else {
                    window.location.reload();
                }
            })
            .catch(() => {
                window.location.reload();
            });
        },

        endGame(serverScore) {
            if (this.gameEnded) return;

            this.score = (typeof serverScore === 'number') ? serverScore : null;
            this.allFound = this.foundIndices.length >= this.totalDifferences;
            this.gameStarted = false;
            this.gameEnded = true;
            this.timeLeft = Math.max(0, this.timeLeft);

            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playFinalClapAndFanfare();
            }
        },

        submitScoreSilently() {
            // Asks the room to close a round whose clock has run out; the
            // score is computed there from the stored claims.
            fetch('{{ route("games.postFindIt", $partner->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({})
            })
            .then(r => r.json())
            .then(d => {
                if (d && d.success) {
                    this.pollChat();
                    this.endGame(d.score);
                }
            })
            .catch(() => {});
        },

        playNextGame(nextGameType = 'memory_snap') {
            if (this.sendingNextInvite) return;
            this.sendingNextInvite = true;

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: nextGameType, force_fresh: true })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                if (d && d.redirect_url) {
                    window.location.href = d.redirect_url;
                } else {
                    window.location.href = '{{ route("games.memory_snap.start", $partner->id) }}';
                }
            })
            .catch(() => {
                window.location.href = '{{ route("games.memory_snap.start", $partner->id) }}';
            });
        },

        replayGame() {
            if (this.sendingReplayInvite) return;
            this.sendingReplayInvite = true;

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'find_it', force_fresh: true })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                this.showToast(`📢 💌 Replay invitation sent to ${this.partnerName}! Waiting for partner to accept...`);
            })
            .catch(() => {
                this.sendingReplayInvite = false;
                this.showToast('Could not send replay invitation');
            });
        }
    }
}
</script>
@endsection