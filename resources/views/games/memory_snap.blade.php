@extends('layouts.app')

@section('title', '🧠 Memory Snap — Koodal Matrimony')

@section('content')
<style>
    [x-cloak]{display:none !important}

    .ms{
        --p1:#7C3AED; --p2:#A855F7; --pk:#EC4899; --ink:#1E1B2E; --muted:#6B6483; --line:#E9D5FF;
        position:relative; min-height:100vh; overflow-x:clip; color:var(--ink);
        background:linear-gradient(180deg,#FDF2F8 0%,#F5F0FF 50%,#FDF2F8 100%);
    }
    .ms *,.ms *::before,.ms *::after{box-sizing:border-box}
    .ms a{text-decoration:none}
    .ms button{font-family:inherit;cursor:pointer}
    .ms button:focus-visible,.ms a:focus-visible,.ms input:focus-visible{outline:3px solid #C4B5FD;outline-offset:2px}

    /* ───────── Background decor ───────── */
    .ms-bg{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0}
    .ms-blob{position:absolute;border-radius:50%;filter:blur(70px);opacity:.45;animation:msDrift 18s ease-in-out infinite}
    .ms-blob.a{width:420px;height:420px;left:-120px;top:-100px;background:#C4B5FD}
    .ms-blob.b{width:380px;height:380px;right:-100px;top:30%;background:#FBCFE8;animation-delay:-6s}
    .ms-blob.c{width:320px;height:320px;left:30%;bottom:-140px;background:#DDD6FE;animation-delay:-11s}
    @keyframes msDrift{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(40px,-30px) scale(1.12)}}
    .ms-spark{position:absolute;bottom:-40px;opacity:0;color:var(--p2);animation:msRise 16s linear infinite}
    .ms-spark:nth-of-type(4){left:8%;font-size:20px;animation-delay:0s}
    .ms-spark:nth-of-type(5){left:30%;font-size:26px;animation-delay:4s}
    .ms-spark:nth-of-type(6){left:55%;font-size:18px;animation-delay:8s}
    .ms-spark:nth-of-type(7){left:78%;font-size:24px;animation-delay:2s}
    .ms-spark:nth-of-type(8){left:92%;font-size:16px;animation-delay:10s}
    @keyframes msRise{0%{transform:translateY(0) rotate(0);opacity:0}10%{opacity:.28}90%{opacity:.12}100%{transform:translateY(-115vh) rotate(30deg);opacity:0}}

    /* ───────── Header ───────── */
    .ms-header{position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:12px;
        padding:12px 18px;background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);
        box-shadow:0 8px 24px -16px rgba(124,58,237,.5)}
    .ms-hl,.ms-hr{display:flex;align-items:center;gap:12px}
    .ms-back{display:grid;place-items:center;width:44px;height:44px;border-radius:14px;color:#6D28D9;font-size:22px;font-weight:900;transition:.2s}
    .ms-back:hover{background:#EDE9FE;transform:translateX(-3px)}
    .ms-brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:16px;letter-spacing:.02em}
    .ms-logo{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;
        background:linear-gradient(135deg,var(--p1),var(--p2));font-size:17px;box-shadow:0 8px 16px -6px var(--p1);animation:msBob 3.2s ease-in-out infinite}
    .ms-icon-btn{display:grid;place-items:center;width:44px;height:44px;border-radius:14px;border:1px solid var(--line);background:#F5F3FF;font-size:18px;transition:.2s}
    .ms-icon-btn:hover{background:#EDE9FE;transform:scale(1.08)}
    .ms-room{padding:7px 14px;border-radius:999px;background:#EDE9FE;border:1px solid #C4B5FD;color:#4C1D95;font-size:12px;font-weight:700}
    .ms-room strong{letter-spacing:.12em}
    @keyframes msBob{50%{transform:translateY(-4px) rotate(-6deg)}}

    /* ───────── Layout ───────── */
    .ms-main{position:relative;z-index:1;max-width:1180px;margin:0 auto;padding:26px 16px 90px;
        display:flex;flex-direction:column;gap:24px;align-items:stretch}
    @media (min-width:1024px){.ms-main{flex-direction:row;align-items:flex-start;justify-content:center;padding-bottom:40px}}
    .ms-stage{width:100%;flex:1;max-width:680px;margin:0 auto;min-width:0}

    .ms-card{padding:32px 28px;border-radius:30px;background:#fff;border:1px solid var(--line);
        box-shadow:0 30px 60px -28px rgba(124,58,237,.45);text-align:center;animation:msPop .5s cubic-bezier(.2,1.2,.3,1) both}
    @media (max-width:520px){.ms-card{padding:24px 16px}}
    @keyframes msPop{from{opacity:0;transform:scale(.88) translateY(20px)}to{opacity:1;transform:none}}
    @keyframes msUp{from{opacity:0;transform:translateY(34px)}to{opacity:1;transform:none}}
    .ms-countdown-overlay{position:fixed;inset:0;z-index:9999;background:rgba(23,15,38,.88);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;text-align:center;padding:20px}
    .ms-countdown-box{display:flex;flex-direction:column;align-items:center;justify-content:center}
    .ms-countdown-num{font-size:clamp(80px,20vw,150px);font-weight:900;line-height:1;background:linear-gradient(135deg,#A855F7,#EC4899,#F59E0B);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;animation:msBeat .8s ease-in-out infinite}
    .ms-countdown-sub{font-size:clamp(14px,3vw,18px);font-weight:800;color:#F3E8FF;margin-top:14px;letter-spacing:.02em}

    .ms-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#F3E8FF;
        border:1px solid #D8B4FE;color:#581C87;font-size:12px;font-weight:900;letter-spacing:.05em;text-transform:uppercase}
    .ms-h1{margin:14px 0 0;font-size:clamp(24px,4vw,34px);font-weight:900;letter-spacing:-.02em;line-height:1.15;
        background:linear-gradient(110deg,#4C1D95,#7C3AED,#EC4899,#7C3AED);background-size:240% 100%;
        -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;animation:msShift 7s linear infinite}
    @keyframes msShift{to{background-position:240% 0}}
    .ms-sub{margin:10px 0 0;font-size:14px;font-weight:600;color:var(--muted)}

    /* ───────── Lobby players ───────── */
    .ms-players{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;max-width:470px;margin:28px auto 0}
    .ms-vs{display:grid;place-items:center;width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--p1),var(--pk));
        color:#fff;font-size:13px;font-weight:900;box-shadow:0 10px 20px -8px var(--p1);animation:msBeat 1.8s ease-in-out infinite}
    @keyframes msBeat{0%,100%{transform:scale(1)}50%{transform:scale(1.18)}}
    .ms-player{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 10px 16px;border-radius:24px;
        background:#FAF5FF;border:2px solid var(--line);transition:.35s}
    .ms-player.joined{background:#ECFDF5;border-color:#10B981;box-shadow:0 18px 30px -18px #10B981;transform:translateY(-4px)}
    .ms-avatar{position:relative;width:96px;height:96px}
    .ms-avatar img{position:relative;z-index:1;width:100%;height:100%;border-radius:50%;object-fit:cover;border:4px solid #C4B5FD;
        filter:grayscale(1);opacity:.7;transition:.5s;background:#fff}
    .ms-avatar::before{content:"";position:absolute;inset:-9px;border-radius:50%;border:2px dashed #A78BFA;animation:msSpin 9s linear infinite}
    .ms-player.joined .ms-avatar img{filter:none;opacity:1;border-color:#10B981;box-shadow:0 0 0 6px #A7F3D0}
    .ms-player.joined .ms-avatar::before{border-color:#10B981;border-style:solid;opacity:.35;animation:msRing 2s ease-out infinite}
    @keyframes msSpin{to{transform:rotate(360deg)}}
    @keyframes msRing{0%{transform:scale(.95);opacity:.6}100%{transform:scale(1.25);opacity:0}}
    .ms-joined-tag{position:absolute;z-index:2;left:50%;bottom:-8px;transform:translateX(-50%);white-space:nowrap;padding:3px 11px;border-radius:999px;
        background:#059669;border:2px solid #fff;color:#fff;font-size:10px;font-weight:900;animation:msPop .4s cubic-bezier(.2,1.4,.3,1) both}
    .ms-pname{margin:6px 0 0;font-size:14px;font-weight:800;line-height:1.2}
    .ms-prole{font-size:10.5px;font-weight:700;color:#7C6F9B}
    .ms-join{width:100%;min-height:44px;padding:10px 12px;border:0;border-radius:14px;background:linear-gradient(135deg,#059669,#10B981);
        color:#fff;font-size:12.5px;font-weight:900;box-shadow:0 12px 20px -10px #059669;animation:msGlow 1.6s ease-in-out infinite;transition:transform .2s}
    .ms-join:hover{transform:scale(1.06)}
    @keyframes msGlow{0%,100%{box-shadow:0 12px 20px -10px #059669,0 0 0 0 rgba(16,185,129,.5)}50%{box-shadow:0 12px 20px -10px #059669,0 0 0 9px rgba(16,185,129,0)}}

    /* invite */
    .ms-invite{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:12px;margin-top:26px;padding:16px;border-radius:22px;
        background:rgba(245,243,255,.9);border:1px solid var(--line)}
    .ms-wa,.ms-pick{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:10px 20px;border-radius:14px;
        font-size:12.5px;font-weight:800;transition:.2s}
    .ms-wa{background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;box-shadow:0 12px 20px -10px #128C7E}
    .ms-wa:hover{transform:translateY(-3px) scale(1.04)}
    .ms-pick{background:#fff;border:1.5px solid #C4B5FD;color:#4C1D95}
    .ms-pick:hover{background:#F5F3FF;transform:translateY(-3px)}
    @media (max-width:520px){.ms-wa,.ms-pick{width:100%}}

    /* start */
    .ms-start{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:54px;margin-top:22px;
        padding:15px;border:0;border-radius:18px;background:#CBD5E1;color:#fff;font-size:15px;font-weight:900;transition:.3s}
    .ms-start:disabled{opacity:.6;cursor:not-allowed}
    .ms-start.ready{opacity:1;background:linear-gradient(135deg,#6D28D9,#4F46E5);box-shadow:0 20px 34px -12px #6D28D9;animation:msHop 1.6s ease-in-out infinite;cursor:pointer}
    .ms-start.ready:hover{transform:scale(1.04)}
    .ms-start.ready::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.5),transparent);transform:skewX(-20deg);animation:msShine 2.6s ease-in-out infinite}
    @keyframes msHop{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
    @keyframes msShine{0%,50%{left:-80%}100%{left:140%}}

    /* how to */
    .ms-how{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:24px;padding-top:20px;border-top:1px dashed var(--line)}
    .ms-how div{padding:12px 6px;border-radius:16px;background:#FAF5FF;font-size:12px;font-weight:800;color:#3B2A5C;transition:.3s}
    .ms-how div:hover{background:#F3E8FF;transform:translateY(-4px)}
    .ms-how span{display:block;margin-bottom:4px;font-size:24px;animation:msBob 3s ease-in-out infinite}
    .ms-how div:nth-child(2) span{animation-delay:.4s}
    .ms-how div:nth-child(3) span{animation-delay:.8s}

    /* disconnect */
    .ms-warn{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:14px 18px;border-radius:18px;background:#F59E0B;
        color:#fff;font-size:13.5px;font-weight:800;box-shadow:0 16px 26px -12px #D97706;animation:msHop 1.2s ease-in-out infinite}

    /* ───────── Playing ───────── */
    .ms-stack{display:flex;flex-direction:column;gap:18px}
    .ms-box{padding:16px 18px;border-radius:22px;background:#fff;border:1px solid var(--line);box-shadow:0 14px 28px -20px rgba(124,58,237,.6);animation:msUp .5s both}
    .ms-top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;font-size:12px;font-weight:800}
    .ms-qlabel{color:#4C1D95;text-transform:uppercase;letter-spacing:.06em}
    .ms-scores{display:flex;align-items:center;gap:10px}
    .ms-scores span{padding:4px 12px;border-radius:999px;background:#F5F3FF;color:#3B2A5C}
    .ms-scores i{font-style:normal;color:#A09BB8}
    .ms-segs{display:flex;gap:6px;height:10px;margin-top:12px}
    .ms-seg{flex:1;border-radius:999px;background:#E2E8F0;transition:.4s}
    .ms-seg.done{background:#10B981}
    .ms-seg.now{background:linear-gradient(90deg,#7C3AED,#A855F7);box-shadow:0 0 0 3px #DDD6FE;animation:msBlink 1.4s ease-in-out infinite}
    @keyframes msBlink{50%{opacity:.6}}

    .ms-panel{position:relative;padding:22px;border-radius:28px;background:#fff;border:1px solid var(--line);
        box-shadow:0 30px 56px -28px rgba(124,58,237,.55);animation:msUp .5s cubic-bezier(.2,.8,.2,1) both}
    @media (min-width:600px){.ms-panel{padding:26px}}

    .ms-phead{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
    .ms-look{display:inline-flex;align-items:center;gap:7px;padding:7px 16px;border-radius:999px;background:#F3E8FF;border:1px solid #D8B4FE;
        color:#581C87;font-size:12.5px;font-weight:900}
    .ms-look i{font-style:normal;animation:msBlinkEye 3s infinite}
    @keyframes msBlinkEye{0%,42%,50%,100%{transform:scaleY(1)}46%{transform:scaleY(.1)}}

    .ms-clock{position:relative;display:grid;place-items:center;width:56px;height:56px}
    .ms-clock svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg)}
    .ms-clock circle{transition:stroke-dashoffset 1s linear,stroke .4s}
    .ms-clock b{position:relative;font-size:17px;font-weight:900;color:#1E1B2E}
    .ms-clock b.danger{color:#E11D48;animation:msBeat .6s ease-in-out infinite}

    .ms-photo{position:relative;display:flex;align-items:center;justify-content:center;max-height:60vh;padding:10px;border-radius:22px;
        background:radial-gradient(circle at 50% 40%,#2a1850,#0f0a1f);border:2px solid #DDD6FE;box-shadow:inset 0 0 40px rgba(0,0,0,.5);overflow:hidden}
    .ms-photo img{display:block;max-height:55vh;width:100%;object-fit:contain;border-radius:14px;animation:msDevelop 1.2s ease-out both}
    @keyframes msDevelop{from{filter:blur(18px) brightness(1.6);transform:scale(1.06)}to{filter:none;transform:none}}
    .ms-photo .c{position:absolute;width:26px;height:26px;border:3px solid rgba(255,255,255,.85);z-index:2;animation:msBlink 2s ease-in-out infinite}
    .ms-photo .c1{top:14px;left:14px;border-right:0;border-bottom:0;border-radius:8px 0 0 0}
    .ms-photo .c2{top:14px;right:14px;border-left:0;border-bottom:0;border-radius:0 8px 0 0}
    .ms-photo .c3{bottom:14px;left:14px;border-right:0;border-top:0;border-radius:0 0 0 8px}
    .ms-photo .c4{bottom:14px;right:14px;border-left:0;border-top:0;border-radius:0 0 8px 0}
    .ms-flash{position:absolute;inset:0;z-index:3;background:#fff;pointer-events:none;animation:msFlash .8s ease-out forwards}
    @keyframes msFlash{from{opacity:.95}to{opacity:0}}

    /* question */
    .ms-qbar{height:7px;border-radius:999px;background:#F1F5F9;overflow:hidden}
    .ms-qbar div{height:100%;border-radius:999px;background:linear-gradient(90deg,#7C3AED,#EC4899);transition:width 1s linear}
    .ms-qtitle{margin:22px 0 0;font-size:clamp(19px,3vw,25px);font-weight:900;line-height:1.3;text-align:center}
    .ms-qhint{margin:6px 0 0;text-align:center;font-size:12px;font-weight:700;color:var(--muted)}
    .ms-opts{display:grid;grid-template-columns:1fr;gap:12px;margin-top:22px}
    @media (min-width:600px){.ms-opts{grid-template-columns:1fr 1fr}}

    .ms-opt{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:56px;padding:12px 14px;border-radius:18px;
        border:2px solid #E2E8F0;background:#fff;color:#1E293B;text-align:left;font-size:14px;font-weight:700;
        box-shadow:0 8px 16px -12px rgba(0,0,0,.4);transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .ms-opt:not(:disabled):hover{transform:translateY(-3px);border-color:#C4B5FD;background:#FAF5FF;box-shadow:0 14px 22px -14px #7C3AED}
    .ms-opt:disabled{cursor:default}
    .ms-opt-l{display:flex;align-items:center;gap:12px;min-width:0}
    .ms-key{flex:none;display:grid;place-items:center;width:34px;height:34px;border-radius:12px;background:#EDE9FE;color:#4C1D95;font-size:14px;font-weight:900;transition:.2s}
    .ms-key.sel{background:#fff;color:#6D28D9}
    /* states returned by getOptionClass() */
    .ms-opt.border-purple-700{border-color:#6D28D9;background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;box-shadow:0 18px 28px -14px #7C3AED;animation:msSelect .35s ease both}
    .ms-opt.border-emerald-500{border-color:#10B981;background:#ECFDF5;color:#022C22;animation:msCorrect .55s ease both}
    .ms-opt.border-rose-500{border-color:#F43F5E;background:#FFF1F2;color:#4C0519;animation:msShake .45s ease both}
    @keyframes msSelect{50%{transform:scale(1.03)}}
    @keyframes msCorrect{0%{transform:scale(.96)}50%{transform:scale(1.04)}100%{transform:scale(1)}}
    @keyframes msShake{0%,100%{transform:translateX(0)}20%{transform:translateX(-7px)}40%{transform:translateX(7px)}60%{transform:translateX(-5px)}80%{transform:translateX(5px)}}
    .ms-tick{font-size:20px;font-weight:900;animation:msPop .4s cubic-bezier(.2,1.4,.3,1) both}
    .ms-tick.ok{color:#059669}.ms-tick.no{color:#E11D48}

    .ms-wait{margin-top:18px;padding:12px;border-radius:16px;background:#F5F3FF;border:1px solid var(--line);text-align:center;
        font-size:12.5px;font-weight:800;color:#5B21B6;animation:msBlink 1.4s ease-in-out infinite}

    /* ───────── Result ───────── */
    .ms-res{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:26px}
    .ms-rp{position:relative;padding:22px 10px 16px;border-radius:22px;background:#FAF5FF;border:1px solid var(--line);transition:.3s}
    .ms-rp:hover{transform:translateY(-5px)}
    .ms-rp img{display:block;width:68px;height:68px;margin:0 auto;border-radius:50%;object-fit:cover;border:3px solid #A78BFA;box-shadow:0 10px 18px -8px #7C3AED}
    .ms-rp h3{margin:10px 0 0;font-size:14px;font-weight:800}
    .ms-rp strong{display:block;margin-top:4px;font-size:26px;font-weight:900;color:#5B21B6}
    .ms-crown{position:absolute;top:-16px;left:50%;transform:translateX(-50%);font-size:28px;animation:msCrown 1.8s ease-in-out infinite}
    @keyframes msCrown{0%,100%{transform:translateX(-50%) translateY(0) rotate(-8deg)}50%{transform:translateX(-50%) translateY(-8px) rotate(8deg)}}

    .ms-chem{margin-top:22px;padding:26px 16px;border-radius:26px;background:linear-gradient(180deg,#FAF5FF,#FDF2F8);border:1px solid var(--line)}
    .ms-gauge{position:relative;display:grid;place-items:center;width:150px;height:150px;margin:0 auto}
    .ms-gauge svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg)}
    .ms-gauge circle{fill:none;stroke-width:9;stroke-linecap:round}
    .ms-gauge .gb{stroke:#EDE9FE}
    .ms-gauge .gf{stroke:url(#msGauge);stroke-dasharray:327;transition:stroke-dashoffset 1.6s cubic-bezier(.2,.8,.2,1)}
    .ms-gauge .h{position:absolute;font-size:70px;opacity:.16;animation:msBeat 1.6s ease-in-out infinite}
    .ms-gauge .p{position:relative;font-size:32px;font-weight:900;color:#4C1D95}
    .ms-tier{display:inline-block;margin-top:14px;padding:7px 18px;border-radius:999px;color:#fff;font-size:12.5px;font-weight:900;box-shadow:0 10px 18px -8px rgba(0,0,0,.4)}
    .ms-tier.hi{background:linear-gradient(135deg,#DB2777,#EC4899)}
    .ms-tier.mid{background:linear-gradient(135deg,#6D28D9,#8B5CF6)}
    .ms-tier.lo{background:linear-gradient(135deg,#334155,#475569)}
    .ms-cmsg{margin:10px auto 0;max-width:420px;font-size:13px;font-weight:700;color:var(--muted);line-height:1.5}

    .ms-acts{display:flex;flex-direction:column;gap:12px;margin-top:24px}
    @media (min-width:560px){.ms-acts{flex-direction:row}}
    .ms-btn,.ms-btn-g{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;flex:1;min-height:48px;padding:13px 16px;
        border-radius:16px;font-size:14px;font-weight:900;text-align:center;transition:.2s}
    .ms-btn{border:0;background:linear-gradient(135deg,#6D28D9,#4F46E5);color:#fff;box-shadow:0 16px 26px -12px #6D28D9}
    .ms-btn:hover{transform:translateY(-3px) scale(1.02)}
    .ms-btn::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);transform:skewX(-20deg);animation:msShine 3s ease-in-out infinite}
    .ms-btn-g{border:1.5px solid #C4B5FD;background:#fff;color:#4C1D95}
    .ms-btn-g:hover{background:#F5F3FF;transform:translateY(-3px)}

    /* ───────── Chat ───────── */
    .ms-chat{display:flex;flex-direction:column;width:100%;height:500px;padding:16px;border-radius:28px;background:#fff;border:1px solid var(--line);
        box-shadow:0 30px 56px -30px rgba(124,58,237,.55);animation:msUp .6s .15s both}
    @media (min-width:1024px){.ms-chat{width:320px;flex:none;height:600px;position:sticky;top:84px}}
    .ms-chat-h{display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F3E8FF}
    .ms-chat-h div{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:900}
    .ms-online{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#ECFDF5;border:1px solid #A7F3D0;color:#059669;font-size:10.5px;font-weight:800}
    .ms-online::before{content:"";width:7px;height:7px;border-radius:50%;background:#10B981;animation:msPing 1.6s ease-out infinite}
    @keyframes msPing{0%{box-shadow:0 0 0 0 rgba(16,185,129,.6)}100%{box-shadow:0 0 0 8px rgba(16,185,129,0)}}
    .ms-msgs{flex:1;overflow-y:auto;padding:12px 4px 12px 0;display:flex;flex-direction:column;gap:12px;font-size:12px;scrollbar-width:thin;scrollbar-color:#D8B4FE transparent}
    .ms-msg{animation:msUp .35s both}
    .ms-msg.me{text-align:right}
    .ms-msg small{display:block;margin-bottom:3px;font-size:10px;font-weight:800;color:#A09BB8}
    .ms-bub{display:inline-block;max-width:86%;padding:8px 14px;text-align:left;font-weight:700;line-height:1.4;box-shadow:0 6px 12px -8px rgba(0,0,0,.4);
        background:#F1F5F9;color:#1E293B;border-radius:18px 18px 18px 4px}
    .ms-msg.me .ms-bub{background:linear-gradient(135deg,#6D28D9,#8B5CF6);color:#fff;border-radius:18px 18px 4px 18px}
    .ms-chips{display:flex;gap:6px;padding:10px 0;border-top:1px solid #F3E8FF;overflow-x:auto;white-space:nowrap;scrollbar-width:none}
    .ms-chips::-webkit-scrollbar{display:none}
    .ms-chip{flex:none;padding:6px 12px;border-radius:999px;border:1px solid var(--line);background:#FAF5FF;color:#4C1D95;font-size:11px;font-weight:800;transition:.15s}
    .ms-chip:hover{background:#EDE9FE;transform:translateY(-2px)}
    .ms-chip:active{transform:scale(.93)}
    .ms-form{display:flex;align-items:center;gap:8px;padding-top:6px}
    .ms-form input{flex:1;min-width:0;padding:10px 16px;border-radius:999px;border:1.5px solid #CBD5E1;background:#fff;font-size:12.5px;font-family:inherit;transition:.2s}
    .ms-form input:focus{outline:none;border-color:#7C3AED;box-shadow:0 0 0 4px #EDE9FE}
    .ms-send{flex:none;display:grid;place-items:center;width:44px;height:44px;border:0;border-radius:50%;background:linear-gradient(135deg,#6D28D9,#8B5CF6);
        color:#fff;font-weight:900;box-shadow:0 10px 16px -8px #6D28D9;transition:.2s}
    .ms-send:hover:not(:disabled){transform:scale(1.1) rotate(-12deg)}
    .ms-send:disabled{opacity:.45;cursor:not-allowed}

    /* ───────── Overlays ───────── */
    .ms-ov{position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;text-align:center}
    .ms-count{background:radial-gradient(circle at 50% 50%,rgba(76,29,149,.9),rgba(15,10,31,.94));backdrop-filter:blur(8px)}
    .ms-count-ring{display:grid;place-items:center;width:190px;height:190px;border-radius:50%;border:4px solid rgba(168,85,247,.5);
        box-shadow:0 0 60px rgba(168,85,247,.5),inset 0 0 40px rgba(168,85,247,.3);animation:msRingPulse 1.4s ease-in-out infinite}
    @keyframes msRingPulse{0%,100%{transform:scale(.95)}50%{transform:scale(1.06)}}
    .ms-count-num{font-size:84px;font-weight:900;color:#E9D5FF;text-shadow:0 0 30px #A855F7;animation:msNum .7s ease-out infinite}
    @keyframes msNum{0%{transform:scale(.4);opacity:0}45%{transform:scale(1.25);opacity:1}100%{transform:scale(1);opacity:.9}}
    .ms-count p{margin:20px 0 0;color:#DDD6FE;font-size:14px;font-weight:800;letter-spacing:.2em;text-transform:uppercase}

    .ms-cele{pointer-events:none;overflow:hidden;z-index:55}
    .ms-cele h1{position:relative;z-index:2;margin:0;font-size:clamp(40px,9vw,84px);font-weight:900;line-height:1.05;
        background:linear-gradient(90deg,#EC4899,#8B5CF6,#6366F1,#EC4899);background-size:250% 100%;-webkit-background-clip:text;background-clip:text;
        -webkit-text-fill-color:transparent;filter:drop-shadow(0 12px 24px rgba(124,58,237,.5));animation:msPop .5s cubic-bezier(.2,1.3,.3,1) both,msShift 2s linear infinite}
    .ms-conf{position:absolute;top:-20px;width:10px;height:16px;border-radius:2px;opacity:0;animation:msConf 2.4s ease-in var(--dl) infinite}
    @keyframes msConf{0%{transform:translateY(0) rotate(0);opacity:1}100%{transform:translateY(110vh) rotate(720deg);opacity:.9}}

    .ms-modal{background:rgba(30,27,46,.6);backdrop-filter:blur(6px)}
    .ms-dialog{width:100%;max-width:440px;padding:24px;border-radius:28px;background:#fff;box-shadow:0 40px 80px -20px rgba(0,0,0,.5);text-align:left;animation:msPop .4s cubic-bezier(.2,1.2,.3,1) both}
    .ms-dialog-h{display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F1F5F9}
    .ms-dialog-h h3{margin:0;font-size:17px;font-weight:900}
    .ms-dialog-h button{width:36px;height:36px;border:0;border-radius:50%;background:#F3F4F6;color:#6B7280;font-size:20px;font-weight:900;transition:.2s}
    .ms-dialog-h button:hover{background:#EDE9FE;color:#6D28D9;transform:rotate(90deg)}
    .ms-search{display:block;width:100%;margin-top:14px;padding:11px 16px;border-radius:14px;border:1.5px solid #CBD5E1;font-size:13px;font-family:inherit}
    .ms-search:focus{outline:none;border-color:#7C3AED;box-shadow:0 0 0 4px #EDE9FE}
    .ms-list{display:flex;flex-direction:column;gap:10px;max-height:260px;margin-top:14px;overflow-y:auto}
    .ms-mem{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:18px;border:1.5px solid #E2E8F0;cursor:pointer;transition:.2s}
    .ms-mem:hover{background:#FAF5FF;border-color:#C4B5FD;transform:translateX(4px)}
    .ms-mem div{display:flex;align-items:center;gap:12px}
    .ms-mem img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #DDD6FE}
    .ms-mem h4{margin:0;font-size:13px;font-weight:800}
    .ms-mem small{font-size:10.5px;color:#94A3B8;font-family:monospace}
    .ms-mem b{font-size:12px;color:#6D28D9}

    @media (prefers-reduced-motion:reduce){
        .ms *,.ms *::before,.ms *::after{animation:none !important;transition:none !important}
    }
</style>

<div class="ms"
     x-data="memorySnapGame()"
     @keydown.window="handleKeyPress($event)">

    {{-- background decor --}}
    <div class="ms-bg" aria-hidden="true">
        <div class="ms-blob a"></div><div class="ms-blob b"></div><div class="ms-blob c"></div>
        <span class="ms-spark">🖼️</span><span class="ms-spark">💜</span><span class="ms-spark">🧠</span><span class="ms-spark">✨</span><span class="ms-spark">💖</span>
    </div>

    {{-- CLEAN TOP GAME HEADER --}}
    <header class="ms-header">
        <div class="ms-hl">
            <a href="{{ $partnerId ? route('games.hub', $partnerId) : route('dashboard') }}" class="ms-back" aria-label="Exit Game">←</a>
            <div class="ms-brand">
                <span class="ms-logo">🧠</span>
                <span>MEMORY SNAP</span>
            </div>
        </div>

        <div class="ms-hr">
            <button @click="toggleMute()" class="ms-icon-btn" :aria-label="muted ? 'Unmute Audio' : 'Mute Audio'">
                <span x-text="muted ? '🔇' : '🔊'"></span>
            </button>
            <span class="ms-room">Room: <strong x-text="game.code"></strong></span>
        </div>
    </header>

    {{-- MAIN STAGE --}}
    <main class="ms-main">

        {{-- LEFT / CENTER STAGE CONTENT --}}
        <div class="ms-stage">

            {{-- DISCONNECT BANNER --}}
            <div x-show="disconnectTimer > 0" x-cloak class="ms-warn">
                <span>⚠️</span>
                <span x-text="`${game.user_two_name} disconnected. Waiting ${disconnectTimer}s to reconnect...`"></span>
            </div>

            {{-- SYNCHRONIZED 3-2-1 COUNTDOWN OVERLAY --}}
            <div x-show="showCountdown" x-cloak class="ms-countdown-overlay">
                <div class="ms-countdown-box">
                    <div class="ms-countdown-num" x-text="countdownVal"></div>
                    <div class="ms-countdown-sub">Get Ready! Photo Recall Starting... 📸</div>
                </div>
            </div>

            {{-- 1. LOBBY SCREEN --}}
            <template x-if="game.status === 'waiting'">
                <div class="ms-card">
                    <div>
                        <span class="ms-pill">🧠 1v1 Memory Challenge</span>
                        <h1 class="ms-h1" x-text="game.challenge_data ? game.challenge_data.title : 'Memory Snap Lobby'"></h1>
                        <p class="ms-sub">Both players must get ready below to start the synchronized countdown!</p>
                    </div>

                    {{-- Two Player Avatar Cards --}}
                    <div class="ms-players">

                        {{-- Player 1 --}}
                        <div class="ms-player" :class="isUserOneReady ? 'joined' : (isUserOneJoined ? 'joined' : '')">
                            <div class="ms-avatar">
                                <img :src="game.user_one_avatar || '/image/01_Koodal_Sync.png'" alt="Player 1">
                                <span x-show="isUserOneReady" class="ms-joined-tag" style="background: #10B981;">Ready ⚡</span>
                                <span x-show="!isUserOneReady && isUserOneJoined" class="ms-joined-tag" style="background: #6366F1;">In Lobby ✓</span>
                            </div>

                            <div>
                                <h3 class="ms-pname" x-text="game.user_one_name"></h3>
                                <span class="ms-prole">Player 1</span>
                            </div>

                            <button x-show="playerRole === 1 && !isUserOneReady" @click="toggleReady()" class="ms-join" style="background: linear-gradient(135deg,#7C3AED,#6D28D9);">
                                Click Ready ⚡
                            </button>
                        </div>

                        <div class="ms-vs">VS</div>

                        {{-- Player 2 --}}
                        <div class="ms-player" :class="isUserTwoReady ? 'joined' : (isUserTwoJoined ? 'joined' : '')">
                            <div class="ms-avatar">
                                <img :src="game.user_two_avatar || '/image/01_Koodal_Sync.png'" alt="Player 2">
                                <span x-show="isUserTwoReady" class="ms-joined-tag" style="background: #10B981;">Ready ⚡</span>
                                <span x-show="!isUserTwoReady && isUserTwoJoined" class="ms-joined-tag" style="background: #6366F1;">In Lobby ✓</span>
                            </div>

                            <div>
                                <h3 class="ms-pname" x-text="game.user_two_name"></h3>
                                <span class="ms-prole">Player 2</span>
                            </div>

                            <button x-show="playerRole === 2 && !isUserTwoReady" @click="toggleReady()" class="ms-join" style="background: linear-gradient(135deg,#EC4899,#DB2777);">
                                Click Ready ⚡
                            </button>
                        </div>
                    </div>

                    {{-- Invite Options --}}
                    <div class="ms-invite">
                        <a :href="whatsappShareUrl" target="_blank" class="ms-wa">
                            <span>💬</span>
                            <span>Invite via WhatsApp</span>
                        </a>

                        <button @click="showMemberPicker = true" class="ms-pick">
                            🔍 Select Member
                        </button>
                    </div>

                    {{-- Start / Ready Game Button --}}
                    <button @click="toggleReady()"
                            :disabled="isMyReady || isStarting"
                            :class="!isMyReady ? 'ready' : ''"
                            class="ms-start">
                        <span>🎮</span>
                        <span x-show="!isMyReady">I'm Ready to Play! (Click Here) →</span>
                        <span x-show="isMyReady && !isPartnerReady">Waiting for Partner to Click Ready... ⏳</span>
                        <span x-show="isMyReady && isPartnerReady">Both Ready! Countdown Starting... 🚀</span>
                    </button>

                    {{-- How to Play Strip --}}
                    <div class="ms-how">
                        <div><span>👁️</span>1) Watch photo 10s</div>
                        <div><span>❓</span>2) Answer questions</div>
                        <div><span>💖</span>3) See chemistry</div>
                    </div>
                </div>
            </template>

            {{-- 2. GAME PLAYING SCREEN --}}
            <template x-if="game.status === 'playing'">
                <div class="ms-stack">

                    {{-- Top Game Progress Bar --}}
                    <div class="ms-box">
                        <div class="ms-top">
                            <span class="ms-qlabel" x-text="`Set ${game.current_question_index + 1} of ${questions.length}`"></span>
                            <div class="ms-scores">
                                <span x-text="`${game.user_one_name}: ${game.user_one_score}`"></span>
                                <i>vs</i>
                                <span x-text="`${game.user_two_name}: ${game.user_two_score}`"></span>
                            </div>
                        </div>

                        {{-- Segmented Progress Bar --}}
                        <div class="ms-segs">
                            <template x-for="(q, idx) in questions" :key="idx">
                                <div class="ms-seg"
                                     :class="idx === game.current_question_index ? 'now' : (idx < game.current_question_index ? 'done' : '')"></div>
                            </template>
                        </div>
                    </div>

                    {{-- PHASE A: IMAGE RECALL PHASE (10 Seconds for Set Photo) --}}
                    <div x-show="phase === 'image'" x-cloak class="ms-panel">
                        <div class="ms-phead">
                            <span class="ms-look"><i>👁️</i> Memorize Photo #<span x-text="game.current_question_index + 1"></span>! (10s)</span>

                            {{-- Circular 10s Countdown Ring --}}
                            <div class="ms-clock">
                                <svg viewBox="0 0 48 48">
                                    <circle cx="24" cy="24" r="20" stroke="#E2E8F0" stroke-width="4" fill="transparent"></circle>
                                    <circle cx="24" cy="24" r="20" :stroke="imageTimer <= 3 ? '#EF4444' : (imageTimer <= 6 ? '#F59E0B' : '#10B981')" stroke-width="4" fill="transparent"
                                            stroke-linecap="round" stroke-dasharray="125.6" :stroke-dashoffset="125.6 - (125.6 * imageTimer / 10)"></circle>
                                </svg>
                                <b :class="imageTimer <= 3 ? 'danger' : ''" x-text="imageTimer"></b>
                            </div>
                        </div>

                        {{-- Photo Display --}}
                        <div class="ms-photo">
                            <span class="c c1"></span><span class="c c2"></span><span class="c c3"></span><span class="c c4"></span>
                            <span class="ms-flash"></span>
                            <img :src="currentQuestionImage" alt="Memorize this photo">
                        </div>
                    </div>

                    {{-- PHASE B & C: QUESTIONS & REVEAL PHASE (NO TIMER) --}}
                    <div x-show="phase === 'question' || phase === 'reveal'" x-cloak class="ms-panel">

                        {{-- Question Set Tag without Countdown Bar --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 16px;border-radius:999px;background:#F5F3FF;border:1px solid #DDD6FE;font-size:12px;font-weight:800;color:#5B21B6;margin-bottom:14px">
                            <span>❓ Recall Question for Photo #<span x-text="game.current_question_index + 1"></span></span>
                            <span style="opacity:0.8">Answer together • Untimed</span>
                        </div>

                        {{-- Question Title --}}
                        <h2 class="ms-qtitle" x-text="currentQuestionData ? currentQuestionData.question_text : ''"></h2>
                        <p class="ms-qhint">Select the correct choice based on what you saw in the photo!</p>

                        {{-- 4 Option Buttons --}}
                        <div class="ms-opts">
                            <template x-for="key in ['A', 'B', 'C', 'D']" :key="key">
                                <button type="button"
                                        @click="selectOption(key)"
                                        :disabled="hasAnswered || phase === 'reveal'"
                                        :class="getOptionClass(key)"
                                        class="ms-opt">
                                    <div class="ms-opt-l">
                                        <span class="ms-key" :class="myAnswer === key ? 'sel' : ''" x-text="key"></span>
                                        <span x-text="currentQuestionData ? currentQuestionData[`option_${key.toLowerCase()}`] : ''"></span>
                                    </div>

                                    <template x-if="phase === 'reveal'">
                                        <div>
                                            <span x-show="currentQuestionData.correct_option === key" class="ms-tick ok">✓</span>
                                            <span x-show="myAnswer === key && currentQuestionData.correct_option !== key" class="ms-tick no">✗</span>
                                        </div>
                                    </template>
                                </button>
                            </template>
                        </div>

                        {{-- Waiting for partner footer indicator --}}
                        <div x-show="hasAnswered && phase === 'question'" class="ms-wait">
                            ⏳ Choice locked! Waiting for partner to finish answering...
                        </div>
                    </div>
                </div>
            </template>

            {{-- 3. RESULT SCREEN --}}
            <template x-if="game.status === 'completed'">
                <div class="ms-card">
                    <div>
                        <span class="ms-pill">✨ Challenge Completed</span>
                        <h1 class="ms-h1">Your Memory Chemistry</h1>
                    </div>

                    {{-- Two Player Cards Side-by-Side --}}
                    <div class="ms-res">
                        <div class="ms-rp">
                            <template x-if="game.user_one_score > game.user_two_score">
                                <span class="ms-crown">👑</span>
                            </template>
                            <img :src="game.user_one_avatar || '/image/01_Koodal_Sync.png'" alt="Player 1">
                            <h3 x-text="game.user_one_name"></h3>
                            <strong x-text="`${game.user_one_score} / ${questions.length}`"></strong>
                        </div>

                        <div class="ms-rp">
                            <template x-if="game.user_two_score > game.user_one_score">
                                <span class="ms-crown">👑</span>
                            </template>
                            <img :src="game.user_two_avatar || '/image/01_Koodal_Sync.png'" alt="Player 2">
                            <h3 x-text="game.user_two_name"></h3>
                            <strong x-text="`${game.user_two_score} / ${questions.length}`"></strong>
                        </div>
                    </div>

                    {{-- Chemistry Percentage Display --}}
                    <div class="ms-chem">
                        <div class="ms-gauge">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <defs>
                                    <linearGradient id="msGauge" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0" stop-color="#7C3AED"/>
                                        <stop offset="1" stop-color="#EC4899"/>
                                    </linearGradient>
                                </defs>
                                <circle class="gb" cx="60" cy="60" r="52"/>
                                <circle class="gf" cx="60" cy="60" r="52" :style="`stroke-dashoffset:${327 - (327 * chemistryPct / 100)}`"/>
                            </svg>
                            <span class="h">💖</span>
                            <span class="p" x-text="`${chemistryPct}%`"></span>
                        </div>

                        <span class="ms-tier"
                              :class="chemistryPct >= 80 ? 'hi' : (chemistryPct >= 50 ? 'mid' : 'lo')"
                              x-text="chemistryTierBadge"></span>
                        <p class="ms-cmsg" x-text="chemistryMessage"></p>
                    </div>

                    {{-- Action Buttons (Replay Game, Play Next Game, Vibe Meter, Games Hub) --}}
                    <div class="ms-acts">
                        <button type="button" @click="replayGame()" :disabled="sendingReplayInvite" class="ms-btn" style="background: linear-gradient(135deg, #10B981, #059669); color: #ffffff;">
                            <span x-show="!sendingReplayInvite">🔄 Replay Game (Fresh Match with Partner)</span>
                            <span x-show="sendingReplayInvite">⏳ Replay invite sent! Waiting for partner...</span>
                        </button>
                        <button type="button" @click="playNextGame()" :disabled="launchingNextGame" class="ms-btn" style="background: linear-gradient(135deg, #7C3AED, #EC4899); color: #ffffff;">
                            <span x-show="!launchingNextGame">🎮 Play Next Game (Flip the Tiles) →</span>
                            <span x-show="launchingNextGame">Inviting & Launching Flip... 🚀</span>
                        </button>
                        <a href="{{ $partnerId ? route('games.vibe', $partnerId) : route('dashboard') }}" class="ms-btn-g" style="color: #7C3AED; font-weight: 800;">
                            📊 Vibe Meter
                        </a>
                        <a href="{{ $partnerId ? route('games.hub', $partnerId) : route('dashboard') }}" class="ms-btn-g" style="color: #475569; font-weight: 800;">
                            🏠 Games Hub
                        </a>
                    </div>

                    {{-- Partner next game invitation prompt (non-intrusive, no auto-redirect) --}}
                    <div x-show="partnerNextGameUrl" x-cloak style="margin-top:16px;padding:14px 18px;border-radius:18px;background:#FDF2F8;border:2px solid #F472B6;color:#831843;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:800;">🎉 Partner invites you to play Flip the Tiles!</span>
                        <a :href="partnerNextGameUrl" class="ms-btn" style="min-height:38px;padding:8px 16px;font-size:13px;flex:none;background:linear-gradient(135deg, #EC4899, #8B5CF6);color:#fff;">Join Flip Game Now →</a>
                    </div>
                </div>
            </template>
        </div>
    </main>

    {{-- Floating Game Chat Icon (Default reactions + Paid custom chat) --}}
    <x-floating-chat />

    {{-- CELEBRATION OVERLAY --}}
    <div x-show="celebrationActive" x-cloak class="ms-ov ms-cele">
        @for ($i = 0; $i < 30; $i++)
            <span class="ms-conf" style="left: {{ ($i * 37) % 100 }}%; --dl: {{ ($i % 8) * 0.14 }}s; background: {{ ['#EC4899','#7C3AED','#F59E0B','#10B981','#3B82F6'][$i % 5] }}"></span>
        @endfor
        <h1 x-text="celebrationText"></h1>
    </div>

    {{-- MEMBER PICKER MODAL --}}
    <div x-show="showMemberPicker" x-cloak class="ms-ov ms-modal">
        <div class="ms-dialog" @click.away="showMemberPicker = false">
            <div class="ms-dialog-h">
                <h3>Select Match Partner</h3>
                <button @click="showMemberPicker = false" aria-label="Close">&times;</button>
            </div>

            <input type="text" x-model="memberSearch" placeholder="Search by Member ID or Name..." class="ms-search">

            <div class="ms-list">
                <template x-for="m in mockMembers" :key="m.id">
                    <div @click="selectPartner(m)" class="ms-mem">
                        <div>
                            <img :src="m.photo" alt="">
                            <div style="display:block">
                                <h4 x-text="m.name"></h4>
                                <small x-text="`ID: ${m.member_id}`"></small>
                            </div>
                        </div>
                        <b>Select →</b>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

{{-- ═════════════════════ JS GAME ENGINE ═════════════════════ --}}
<script>
function memorySnapGame() {
    return {
        game: @json($game),
        playerRole: {{ $playerRole }},
        questions: @json($game['questions_data'] ?? []),
        partnerId: @json($partnerId),
        phase: 'lobby',
        imageTimer: 10,
        imageInterval: null,
        pollInterval: null,
        myAnswer: null,
        hasAnswered: false,
        showCountdown: false,
        countdownVal: '3',
        muted: false,
        showMemberPicker: false,
        memberSearch: '',
        chatInput: '',
        disconnectTimer: 0,
        celebrationActive: false,
        celebrationText: 'SUPER!',
        lastCelebrationId: null,
        launchingNextGame: false,
        sendingReplayInvite: false,
        nextGameNotice: false,
        partnerNextGameUrl: @json($game['next_game_url'] ?? null),
        redirectingToNextGame: false,
        clockOffset: (Date.now() / 1000) - {{ $serverTime ?? microtime(true) }},
        isStarting: false,
        celebratedCompletion: false,

        quickChips: [
            'Super 👍', 'Good 😊', 'Wow 👏', 'Excellent ⭐'
        ],

        mockMembers: [
            { id: 2, name: 'Karthik Venkat', member_id: 'MAT202688', photo: '/image/01_Koodal_Sync.png' },
            { id: 3, name: 'Anand Kumar', member_id: 'MAT202699', photo: '/image/02_Koodal_Find_It.png' }
        ],

        get isUserOneJoined() {
            return !!this.game.user_one_joined;
        },

        get isUserTwoJoined() {
            return !!this.game.user_two_joined;
        },

        get bothJoined() {
            return this.isUserOneJoined && this.isUserTwoJoined;
        },

        get isUserOneReady() {
            return !!this.game.user_one_ready;
        },

        get isUserTwoReady() {
            return !!this.game.user_two_ready;
        },

        get isMyReady() {
            return this.playerRole === 1 ? this.isUserOneReady : this.isUserTwoReady;
        },

        get isPartnerReady() {
            return this.playerRole === 1 ? this.isUserTwoReady : this.isUserOneReady;
        },

        get bothReady() {
            return this.isUserOneReady && this.isUserTwoReady;
        },

        get challengeTitle() {
            if (!this.game || !this.game.challenge_data) return 'Memory Snap Challenge';
            let cd = this.game.challenge_data;
            if (typeof cd === 'string') {
                try { cd = JSON.parse(cd); } catch(e) {}
            }
            return (cd && cd.title) ? cd.title : 'Memory Snap Challenge';
        },

        get photoImageUrl() {
            if (!this.game || !this.game.challenge_data) return '/image/01_Koodal_Sync.png';
            let cd = this.game.challenge_data;
            if (typeof cd === 'string') {
                try { cd = JSON.parse(cd); } catch(e) {}
            }
            return (cd && cd.image_url) ? cd.image_url : '/image/01_Koodal_Sync.png';
        },

        get currentQuestionData() {
            return this.questions[this.game.current_question_index] || null;
        },

        get currentQuestionImage() {
            if (this.currentQuestionData && this.currentQuestionData.image_url) {
                return this.currentQuestionData.image_url;
            }
            return this.photoImageUrl;
        },

        get whatsappShareUrl() {
            const partnerName = this.playerRole === 1 ? (this.game.user_two_name || 'Karthik') : (this.game.user_one_name || 'Priya');
            const roomUrl = window.location.href;
            const text = encodeURIComponent(`Hi ${partnerName}! Join my Memory Snap game on Koodal Matrimony!\nPlay here: ${roomUrl}`);

            // The partner's phone number is contact info and stays private:
            // WhatsApp lets the sender pick who to share with.
            return `https://wa.me/?text=${text}`;
        },

        get chemistryPct() {
            const total = this.questions.length || 5;
            const avgScore = (this.game.user_one_score + this.game.user_two_score) / 2;
            return Math.round((avgScore / total) * 100);
        },

        get chemistryTierBadge() {
            if (this.chemistryPct >= 80) return '💖 Perfect Chemistry';
            if (this.chemistryPct >= 50) return '💕 Good Match';
            return '✨ Opposites Attract';
        },

        get chemistryMessage() {
            if (this.chemistryPct >= 80) return 'Incredible recall! You both share an extraordinary eye for detail.';
            if (this.chemistryPct >= 50) return 'Great teamwork! You have a solid connection and mutual memory focus.';
            return 'Fun attempt! Opposite perspectives make your connection exciting and complementary.';
        },

        getServerNow() {
            return (Date.now() / 1000) - this.clockOffset;
        },

        init() {
            if (this.game && typeof this.game.challenge_data === 'string') {
                try { this.game.challenge_data = JSON.parse(this.game.challenge_data); } catch(e) {}
            }
            if (this.game && typeof this.game.questions_data === 'string') {
                try { this.game.questions_data = JSON.parse(this.game.questions_data); } catch(e) {}
            }
            if (this.game && this.game.questions_data && this.game.questions_data.length) {
                this.questions = this.game.questions_data;
            }

            // Continuous sync game loop every 200ms
            setInterval(() => {
                this.syncGameLoop();
            }, 200);

            this.startStatePolling();
            window.addEventListener('koodal-quick-chat-send', (e) => {
                if (e.detail && e.detail.message) {
                    this.chatInput = e.detail.message;
                    this.sendChat();
                }
            });
        },

        syncGameLoop() {
            if (!this.game) return;

            if (this.game.status === 'completed') {
                this.phase = 'completed';
                this.showCountdown = false;
                return;
            }

            if (this.game.status !== 'playing' || !this.game.image_started_at) {
                return;
            }

            const now = this.getServerNow();
            const diff = now - this.game.image_started_at;
            const currQ = this.game.current_question_index;
            const u1Ans = this.game.user_one_answers || {};
            const u2Ans = this.game.user_two_answers || {};
            const bothAnswered = !!(u1Ans[String(currQ)] && u2Ans[String(currQ)]);

            // If both players have answered the current question, stay in answer reveal phase
            // until the server advances current_question_index to the next round or completes the game!
            if (bothAnswered) {
                this.showCountdown = false;
                this.phase = 'reveal';
                return;
            }

            // diff < 0 means we are in the initial synchronized 3-2-1 countdown before Round 1 (currQ === 0)
            if (diff < 0) {
                if (currQ === 0) {
                    this.showCountdown = true;
                    const sec = Math.max(1, Math.ceil(-diff));
                    const nextVal = sec <= 3 ? String(sec) : '3';
                    if (this.countdownVal !== nextVal) {
                        this.countdownVal = nextVal;
                        if (window.KoodalGameAudio) {
                            window.KoodalGameAudio.playCountdown(Number(nextVal));
                        }
                    }
                    this.phase = 'lobby';
                }
                return;
            }

            if (this.showCountdown) {
                this.showCountdown = false;
                if (window.KoodalGameAudio) {
                    window.KoodalGameAudio.playCountdown('go');
                }
            }

            // diff >= 0 and diff < 10.0: Image Recall Phase for exactly 10 seconds
            if (diff < 10.0) {
                this.phase = 'image';
                const remaining = Math.max(0, Math.ceil(10.0 - diff));
                if (this.imageTimer !== remaining) {
                    this.imageTimer = remaining;
                    if (remaining <= 3 && remaining > 0 && window.KoodalGameAudio) {
                        window.KoodalGameAudio.playCountdown(remaining);
                    }
                }
                this.hasAnswered = false;
                this.myAnswer = null;
                return;
            }

            // diff >= 10.0: Question Phase (Untimed, waits for both players to answer)
            this.imageTimer = 0;
            this.phase = 'question';

            // Ensure player's own submitted answer is reflected
            const answers = this.playerRole === 1 ? this.game.user_one_answers : this.game.user_two_answers;
            if (answers && answers[String(currQ)]) {
                this.myAnswer = answers[String(currQ)];
                this.hasAnswered = true;
            }
        },

        startStatePolling() {
            this.pollInterval = setInterval(() => {
                fetch(`/memory-snap/room/${this.game.code}/state`)
                .then(res => res.json())
                .then(data => {
                    this.handleStateUpdate(data);
                })
                .catch(() => {});
            }, 1000);
        },

        handleStateUpdate(data) {
            // An error body ({success:false, message}) is not a game row.
            if (!data || !data.code) return;

            if (data.server_time) {
                this.clockOffset = (Date.now() / 1000) - data.server_time;
            }

            if (typeof data.challenge_data === 'string') {
                try { data.challenge_data = JSON.parse(data.challenge_data); } catch(e) {}
            }
            if (typeof data.questions_data === 'string') {
                try { data.questions_data = JSON.parse(data.questions_data); } catch(e) {}
            }
            if (data.questions_data && data.questions_data.length) {
                this.questions = data.questions_data;
            }

            // Check if partner clicked "Play Next Game" (Flip the Tiles)
            if (data.next_game_url) {
                this.partnerNextGameUrl = data.next_game_url.replace(/^https?:\/\/[^\/]+/, '');
            }

            // Always sync chat messages and celebrations across all game phases (lobby, playing, completed)
            if (Array.isArray(data.chat_messages) && window.syncKoodalChatMessages) {
                const mappedChat = data.chat_messages.map(m => ({
                    id: m.id,
                    message: m.text || m.message,
                    sender_name: m.sender || '',
                    mine: Number(m.role) === Number(this.playerRole),
                    time: m.time || ''
                }));
                const pName = this.playerRole === 1 ? (data.user_two_name || 'Partner') : (data.user_one_name || 'Partner');
                window.syncKoodalChatMessages(mappedChat, 0, pName);
            }

            if (data.celebration_event && data.celebration_event.id !== this.lastCelebrationId) {
                this.lastCelebrationId = data.celebration_event.id;
                this.triggerCelebration(data.celebration_event.word, data.celebration_event.type);
            }

            // Detect if game was reset to waiting
            if (data.status === 'waiting' && this.game.status === 'completed') {
                this.game = data;
                this.phase = 'lobby';
                this.showCountdown = false;
                this.hasAnswered = false;
                this.myAnswer = null;
                this.celebratedCompletion = false;
                return;
            }

            if (data.status === 'completed') {
                const wasCompleted = (this.phase === 'completed');
                this.game = data;
                this.phase = 'completed';
                this.showCountdown = false;
                if (!wasCompleted && window.KoodalGameAudio) {
                    window.KoodalGameAudio.playFinalClapAndFanfare();
                }
                if (this.chemistryPct >= 80 && !this.celebratedCompletion) {
                    this.celebratedCompletion = true;
                    this.triggerCelebration('PERFECT CHEMISTRY!', 'confetti');
                }
                return;
            }

            this.game = data;
        },

        toggleReady() {
            if (this.isStarting || this.game.status === 'playing') return;
            this.isStarting = true;

            fetch(`/memory-snap/room/${this.game.code}/ready`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ role: this.playerRole })
            })
            .then(res => res.json())
            .then(data => {
                this.isStarting = false;
                if (data && (data.game || data.server_time)) {
                    this.handleStateUpdate(data.game || data);
                }
                if (window.KoodalGameAudio) {
                    window.KoodalGameAudio.playSelect();
                } else {
                    this.playBeepSound(520);
                }
            })
            .catch(() => {
                this.isStarting = false;
            });
        },

        clickPlayerJoin() {
            this.toggleReady();
        },

        startGame() {
            this.toggleReady();
        },

        selectOption(key) {
            if (this.hasAnswered || this.phase !== 'question') return;
            this.myAnswer = key;
            this.hasAnswered = true;
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playSelect();
            } else {
                this.playBeepSound(600);
            }

            fetch(`/memory-snap/room/${this.game.code}/answer`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    role: this.playerRole,
                    question_index: this.game.current_question_index,
                    answer: key
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data && (data.game || data.server_time)) {
                    this.handleStateUpdate(data.game || data);
                }
            });
        },

        getOptionClass(key) {
            if (this.phase === 'reveal') {
                if (this.currentQuestionData && this.currentQuestionData.correct_option === key) {
                    return 'border-emerald-500 bg-emerald-50 text-emerald-950 font-extrabold';
                }
                if (this.myAnswer === key && this.currentQuestionData && this.currentQuestionData.correct_option !== key) {
                    return 'border-rose-500 bg-rose-50 text-rose-950 font-extrabold';
                }
            }
            if (this.myAnswer === key) {
                return 'border-purple-700 bg-purple-700 text-white font-extrabold scale-102 shadow-md';
            }
            return 'border-slate-200 bg-white hover:border-purple-300 hover:bg-purple-50 text-slate-800';
        },

        handleKeyPress(e) {
            if (this.phase !== 'question' || this.hasAnswered) return;
            const keyMap = { '1': 'A', 'a': 'A', 'A': 'A', '2': 'B', 'b': 'B', 'B': 'B', '3': 'C', 'c': 'C', 'C': 'C', '4': 'D', 'd': 'D', 'D': 'D' };
            if (keyMap[e.key]) {
                this.selectOption(keyMap[e.key]);
            }
        },

        sendChat() {
            if (!this.chatInput.trim() || !this.game || !this.game.code) return;
            const text = this.chatInput;
            fetch(`/memory-snap/room/${this.game.code}/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ role: this.playerRole, message: text })
            }).then(res => res.json()).then(data => {
                if (data && !data.game && data.message) {
                    alert(data.message);
                }
                if (data && data.game) {
                    this.game = data.game;
                    if (Array.isArray(this.game.chat_messages) && window.syncKoodalChatMessages) {
                        const mappedChat = this.game.chat_messages.map(m => ({
                            id: m.id,
                            message: m.text || m.message,
                            sender_name: m.sender || '',
                            mine: Number(m.role) === Number(this.playerRole),
                        }));
                        const pName = this.playerRole === 1 ? (this.game.user_two_name || 'Partner') : (this.game.user_one_name || 'Partner');
                        window.syncKoodalChatMessages(mappedChat, 0, pName);
                    }
                }
                this.chatInput = '';
                this.scrollChat();
            }).catch(err => console.error('Memory Snap send chat error:', err));
        },

        sendChip(chip) {
            if (window.triggerKoodalCelebration) {
                window.triggerKoodalCelebration(chip);
            }
            if (window.pushKoodalChatStream) {
                window.pushKoodalChatStream(chip, true);
            }
            this.chatInput = chip;
            this.sendChat();
        },

        scrollChat() {
            this.$nextTick(() => {
                const el = document.getElementById('chatMessages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        toggleMute() {
            this.muted = !this.muted;
        },

        playBeepSound(freq) {
            if (this.muted) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } catch(e) {}
        },

        triggerCelebration(text, type) {
            this.celebrationText = text;
            this.celebrationActive = true;
            setTimeout(() => { this.celebrationActive = false; }, 2500);
        },

        selectPartner(m) {
            fetch(`/memory-snap/room/${this.game.code}/join`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: m.name })
            }).then(() => {
                this.showMemberPicker = false;
                window.location.reload();
            });
        },

        playNextGame() {
            if (this.launchingNextGame) return;
            if (!this.partnerId) {
                window.location.href = `/flip`;
                return;
            }
            this.launchingNextGame = true;

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'flip', force_fresh: true })
            })
            .then(res => res.json())
            .then(data => {
                var targetUrl = (data.redirect_url || `/flip`).replace(/^https?:\/\/[^\/]+/, '');

                // Broadcast next_game_url so partner's room also redirects
                fetch(`/memory-snap/room/${this.game.code}/next-game`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ url: targetUrl })
                }).catch(() => {});

                window.location.href = targetUrl;
            })
            .catch(err => {
                this.launchingNextGame = false;
                window.location.href = `/flip`;
            });
        },

        replayGame() {
            if (this.sendingReplayInvite) return;
            this.sendingReplayInvite = true;

            fetch(`/games/{{ $partnerId }}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'memory_snap', force_fresh: true })
            })
            .then(res => res.json().catch(() => null))
            .then(data => {
                // Replay invite sent to partner
            })
            .catch(() => {
                this.sendingReplayInvite = false;
            });
        }
    }
}
</script>
@endsection