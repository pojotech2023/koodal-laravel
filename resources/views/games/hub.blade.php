@extends('layouts.member')

@section('title', 'Koodal Matrimony — Games & Vibe Meter')

@section('body')

{{-- ═════════════════════ STYLES (self-contained, no Tailwind dependency) ═════════════════════ --}}
<style>
    [x-cloak]{display:none !important}

    .kg{
        --pink:#E0245E; --pink-d:#B81B5C; --ink:#1A0A12; --muted:#6B5560;
        --radius:26px;
        position:relative; overflow:hidden; min-height:100vh; color:var(--ink);
        background:linear-gradient(180deg,#FFF5F8 0%,#FFFFFF 38%,#FFF7FA 100%);
    }
    .kg *,.kg *::before,.kg *::after{box-sizing:border-box}
    .kg a{text-decoration:none}
    .kg button{font-family:inherit;cursor:pointer}

    /* ───────── Reveal on scroll ───────── */
    .kg-js .kg-reveal:not(.in){opacity:0}
    .kg-reveal.in{animation:kgUp .8s cubic-bezier(.2,.8,.2,1) var(--i,0s) backwards}
    @keyframes kgUp{from{opacity:0;transform:translateY(34px)}to{opacity:1;transform:none}}

    /* ───────── Hero ───────── */
    .kg-hero{position:relative;width:100%;overflow:hidden;line-height:0}
    .kg-hero img{width:100%;height:auto;display:block;animation:kgZoom 1.6s cubic-bezier(.2,.8,.2,1) both}
    .kg-hero::after{content:"";position:absolute;left:0;right:0;bottom:0;height:90px;
        background:linear-gradient(180deg,rgba(255,245,248,0),#fff)}
    @keyframes kgZoom{from{opacity:0;transform:scale(1.06)}to{opacity:1;transform:none}}

    /* ───────── Floating hearts (decor) ───────── */
    .kg-hearts{position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:0}
    .kg-hearts span{position:absolute;bottom:-40px;color:var(--pink);opacity:.0;font-size:20px;
        animation:kgFloat 14s linear infinite}
    .kg-hearts span:nth-child(1){left:6%;animation-delay:0s;font-size:18px}
    .kg-hearts span:nth-child(2){left:22%;animation-delay:3s;font-size:26px}
    .kg-hearts span:nth-child(3){left:41%;animation-delay:6s;font-size:16px}
    .kg-hearts span:nth-child(4){left:63%;animation-delay:2s;font-size:24px}
    .kg-hearts span:nth-child(5){left:80%;animation-delay:8s;font-size:18px}
    .kg-hearts span:nth-child(6){left:93%;animation-delay:5s;font-size:22px}
    @keyframes kgFloat{
        0%{transform:translateY(0) rotate(0);opacity:0}
        10%{opacity:.22}
        90%{opacity:.12}
        100%{transform:translateY(-110vh) rotate(25deg);opacity:0}
    }

    /* ───────── Journey ───────── */
    .kg-journey{position:relative;z-index:1;margin:8px 0 0;padding:56px 16px 44px;background:#fff;
        border-top:1px solid #FBCFE8;border-bottom:1px solid #FBCFE8;box-shadow:0 8px 30px rgba(224,36,94,.06)}
    .kg-wrap{max-width:1240px;margin:0 auto;padding:0 20px}
    .kg-title{margin:0;text-align:center;font-weight:900;letter-spacing:-.02em;font-size:clamp(26px,4vw,38px)}
    .kg-sub{margin:10px auto 0;text-align:center;color:var(--muted);font-weight:500;font-size:15px;max-width:620px}

    .kg-steps{margin-top:34px;overflow:hidden;padding:18px 0 22px;position:relative;mask-image:linear-gradient(to right,transparent,black 6%,black 94%,transparent);-webkit-mask-image:linear-gradient(to right,transparent,black 6%,black 94%,transparent)}
    .kg-steps-track{display:flex;align-items:flex-start;gap:14px;width:max-content;animation:kgAutoSlide 24s linear infinite}
    .kg-steps:hover .kg-steps-track{animation-play-state:paused}
    @keyframes kgAutoSlide{
        0%{transform:translateX(0)}
        100%{transform:translateX(-50%)}
    }
    .kg-steps-row{display:flex;align-items:flex-start;justify-content:center;gap:14px;min-width:max-content;padding:0 7px}
    .kg-step{display:flex;flex-direction:column;align-items:center;gap:8px;transition:transform .35s ease}
    .kg-pill{display:flex;align-items:center;gap:8px;padding:9px 16px 9px 10px;border-radius:999px;
        font-size:13px;font-weight:800;border:1.5px solid var(--bd);background:var(--bg);color:var(--c);
        transition:all .35s ease}
    .kg-pill:hover{transform:translateY(-3px);box-shadow:0 10px 20px -8px var(--c)}
    .kg-pill .n{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;
        background:var(--c);color:#fff;font-size:11px;font-weight:900;transition:all .35s ease}
    .kg-step small{max-width:110px;text-align:center;font-size:11px;line-height:1.3;font-weight:600;color:#7a6670;transition:color .35s ease}
    .kg-arrow{align-self:flex-start;margin-top:8px;color:#F9A8C9;font-weight:900;font-size:18px;animation:kgNudge 1.8s ease-in-out infinite}
    @keyframes kgNudge{0%,100%{transform:translateX(0)}50%{transform:translateX(5px)}}

    .kg-pill.active{background:linear-gradient(135deg,#E0245E,#B81B5C) !important;color:#fff !important;border-color:transparent !important;
        padding:12px 22px 12px 12px;font-size:14px;position:relative;
        box-shadow:0 0 0 5px #FBCFE8,0 12px 26px -8px rgba(224,36,94,.7);animation:kgGlow 2.4s ease-in-out infinite}
    .kg-pill.active .n{background:#fff !important;color:#D6246E !important}
    .kg-step.is-active{transform:translateY(-3px)}
    .kg-step.is-active small{color:#B81B5C;font-weight:800}
    @keyframes kgGlow{
        0%,100%{box-shadow:0 0 0 5px #FBCFE8,0 12px 26px -8px rgba(224,36,94,.7)}
        50%{box-shadow:0 0 0 11px rgba(251,207,232,.45),0 12px 30px -6px rgba(224,36,94,.85)}
    }

    /* ───────── Section heading ───────── */
    .kg-head{position:relative;z-index:1;text-align:center;padding:64px 16px 34px}
    .kg-chip{display:inline-flex;align-items:center;gap:8px;padding:7px 18px;border-radius:999px;
        background:#FFF0F5;border:1px solid #FBCFE8;color:#D6246E;font-size:12px;font-weight:800;letter-spacing:.06em}
    .kg-chip i{font-style:normal;animation:kgTwinkle 2s ease-in-out infinite}
    .kg-chip i:last-child{animation-delay:1s}
    @keyframes kgTwinkle{0%,100%{transform:scale(1) rotate(0)}50%{transform:scale(1.35) rotate(18deg)}}
    .kg-h2{margin:18px 0 0;font-weight:900;letter-spacing:-.03em;font-size:clamp(30px,5vw,50px)}
    .kg-grad{background:linear-gradient(110deg,#E0245E,#B81B5C,#9333EA,#E0245E);background-size:250% 100%;
        -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
        animation:kgShift 6s linear infinite}
    @keyframes kgShift{to{background-position:250% 0}}
    .kg-lead{margin:14px auto 0;max-width:620px;font-size:clamp(15px,2vw,18px);font-weight:700;color:#4a3a42}

    /* ───────── Grid ───────── */
    .kg-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:26px;
        align-items:stretch;padding-bottom:70px}
    @media (max-width:1020px){.kg-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width:660px){.kg-grid{grid-template-columns:1fr}}

    /* ───────── Card ───────── */
    .kg-card{--accent:#E0245E;--accent-2:#B81B5C;--tint:#FFF0F5;--line:#FBCFE8;
        position:relative;display:flex;flex-direction:column;padding:22px;border-radius:var(--radius);
        background:linear-gradient(180deg,var(--tint) 0%,#fff 70%);border:2px solid var(--line);
        box-shadow:0 10px 28px -16px var(--accent);transition:transform .35s cubic-bezier(.2,.8,.2,1),box-shadow .35s,border-color .35s}
    .kg-card:hover{transform:translateY(-8px);box-shadow:0 28px 46px -22px var(--accent);border-color:var(--accent)}
    .kg-card.t-sync{--accent:#E0245E;--accent-2:#B81B5C;--tint:#FFF0F5;--line:#FBCFE8}
    .kg-card.t-find{--accent:#0891B2;--accent-2:#0E7490;--tint:#ECFEFF;--line:#A5F3FC}
    .kg-card.t-mem{--accent:#7C3AED;--accent-2:#A855F7;--tint:#FAF5FF;--line:#DDD6FE}
    .kg-card.t-flip{--accent:#6D28D9;--accent-2:#5B21B6;--tint:#F3E8FF;--line:#DDD6FE}
    .kg-card.t-spot{--accent:#16A34A;--accent-2:#15803D;--tint:#F0FDF4;--line:#BBF7D0}
    .kg-card.t-vibe{--accent:#D6246E;--accent-2:#B81B5C;--tint:#FDF2F8;--line:#FBCFE8}

    .kg-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
    .kg-id{display:flex;align-items:flex-start;gap:12px;min-width:0}
    .kg-num{flex:none;display:grid;place-items:center;width:38px;height:38px;border-radius:50%;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-weight:900;font-size:15px;
        box-shadow:0 8px 16px -6px var(--accent);transition:transform .5s}
    .kg-card:hover .kg-num{transform:rotate(360deg) scale(1.08)}
    .kg-id h3{margin:0;font-size:14px;font-weight:900;letter-spacing:.04em;text-transform:uppercase;line-height:1.2}
    .kg-id span.s{display:block;margin-top:3px;font-size:12px;font-weight:700;color:var(--accent)}

    .kg-badge{flex:none;display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:999px;border:0;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-size:11px;font-weight:900;
        box-shadow:0 8px 14px -8px var(--accent);transition:transform .2s;white-space:nowrap}
    button.kg-badge:hover{transform:scale(1.08)}

    .kg-desc{margin:16px 0 0;font-size:13.5px;line-height:1.55;font-weight:500;color:#5b4a53;min-height:42px}

    /* status strips (sync card) */
    .kg-status{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding:8px 12px;
        border-radius:14px;background:#fff;border:1px solid #FBCFE8;font-size:11.5px;font-weight:700;color:#7a6670}
    .kg-status b{display:flex;align-items:center;gap:7px;font-weight:900;color:#4b5563}
    .kg-status b.is-online{color:#059669}
    .kg-dot{width:9px;height:9px;border-radius:50%;background:#9CA3AF}
    .is-online .kg-dot{background:#10B981;animation:kgPing 1.6s ease-out infinite}
    @keyframes kgPing{0%{box-shadow:0 0 0 0 rgba(16,185,129,.55)}100%{box-shadow:0 0 0 9px rgba(16,185,129,0)}}

    .kg-note{display:flex;align-items:center;gap:7px;margin-top:10px;padding:8px 12px;border-radius:14px;
        font-size:11.5px;font-weight:800}
    .kg-note.wait{background:linear-gradient(135deg,#E0245E,#B81B5C);color:#fff;box-shadow:0 10px 18px -10px #E0245E}
    .kg-note.wait span{animation:kgPulse 1.2s ease-in-out infinite}
    .kg-note.both{background:#DCFCE7;color:#15803D}
    .kg-note.me{background:#FFF0F5;color:#D6246E;border:1px solid #FBCFE8}
    @keyframes kgPulse{50%{opacity:.35;transform:scale(.85)}}

    .kg-alert{position:absolute;top:-8px;right:-8px;z-index:3;display:flex;width:24px;height:24px}
    .kg-alert i{position:absolute;inset:0;border-radius:50%;background:#F472B6;opacity:.75;animation:kgRing 1.4s ease-out infinite}
    .kg-alert b{position:relative;display:grid;place-items:center;width:24px;height:24px;border-radius:50%;
        background:#DB2777;color:#fff;font-size:11px;font-weight:900}
    @keyframes kgRing{to{transform:scale(2.3);opacity:0}}
    .kg-tip{position:absolute;left:50%;top:-52px;z-index:5;transform:translate(-50%,8px);opacity:0;pointer-events:none;
        white-space:nowrap;padding:8px 14px;border-radius:14px;background:linear-gradient(135deg,#E0245E,#B81B5C);
        color:#fff;font-size:12px;font-weight:800;box-shadow:0 14px 24px -10px #B81B5C;transition:.25s}
    .kg-tip::after{content:"";position:absolute;top:100%;left:50%;margin-left:-6px;border:6px solid transparent;border-top-color:#B81B5C}
    .kg-card:hover .kg-tip{opacity:1;transform:translate(-50%,0)}

    /* media (preview) */
    .kg-media{position:relative;margin-top:16px;height:270px;border-radius:20px;background:#fff;border:1px solid var(--line);
        padding:6px;overflow:hidden;display:grid;place-items:center;box-shadow:inset 0 0 0 1px rgba(255,255,255,.8)}
    .kg-media img{width:100%;height:100%;object-fit:contain;border-radius:14px;transition:transform .6s cubic-bezier(.2,.8,.2,1)}
    .kg-card:hover .kg-media img{transform:scale(1.05)}
    .kg-media.clickable{cursor:pointer}
    .kg-media::after{content:"";position:absolute;top:0;left:-70%;width:45%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.65),transparent);transform:skewX(-18deg);pointer-events:none}
    .kg-card:hover .kg-media::after{animation:kgSweep .9s ease}
    @keyframes kgSweep{to{left:130%}}

    /* actions */
    .kg-actions{margin-top:auto;padding-top:18px}
    .kg-actions-in{display:flex;flex-direction:column;gap:9px;padding-top:16px;border-top:1px dashed var(--line)}
    .kg-tag{display:block;text-align:center;padding:7px 10px;border-radius:999px;background:var(--line);color:var(--accent-2);
        font-size:10.5px;font-weight:900;letter-spacing:.07em;text-transform:uppercase}

    .kg-btn{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;gap:8px;width:100%;
        padding:12px 14px;border-radius:14px;border:0;font-size:13px;font-weight:900;color:#fff;text-align:center;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:0 12px 20px -10px var(--accent);
        transition:transform .2s,box-shadow .2s}
    .kg-btn:hover{transform:translateY(-2px);box-shadow:0 16px 26px -10px var(--accent)}
    .kg-btn:active{transform:scale(.97)}
    .kg-btn::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);transform:skewX(-20deg);
        animation:kgShine 3.4s ease-in-out infinite}
    @keyframes kgShine{0%,55%{left:-80%}100%{left:140%}}
    .kg-btn.soft{background:#fff;color:var(--accent-2);border:1.5px solid var(--line);box-shadow:none}
    .kg-btn.soft::after{display:none}
    .kg-btn.soft:hover{background:var(--tint);border-color:var(--accent)}
    .kg-btn.tint{background:var(--tint);color:var(--accent-2);border:1.5px solid var(--line);box-shadow:none}
    .kg-btn.tint::after{display:none}
    .kg-btn.tint:hover{border-color:var(--accent)}

    /* ───────── CSS mockup: Memory Snap ───────── */
    .kg-snap{position:relative;display:grid;place-items:center;width:100%;height:100%;border-radius:14px;
        background:radial-gradient(circle at 50% 40%,#F3E8FF,#fff 70%)}
    .kg-snap-box{position:relative;width:170px;height:170px;display:grid;place-items:center}
    .kg-snap svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg)}
    .kg-snap circle{fill:none;stroke-width:7;stroke-linecap:round}
    .kg-snap .bg{stroke:#EDE9FE}
    .kg-snap .fg{stroke:url(#kgSnapGrad);stroke-dasharray:327;stroke-dashoffset:0;animation:kgCount 6s linear infinite}
    @keyframes kgCount{0%{stroke-dashoffset:0}85%,100%{stroke-dashoffset:327}}
    .kg-snap-pic{font-size:56px;line-height:1;animation:kgBob 3s ease-in-out infinite}
    .kg-snap-eye{position:absolute;top:34px;right:30px;font-size:26px;animation:kgBlink 3s infinite}
    @keyframes kgBob{50%{transform:translateY(-6px) scale(1.05)}}
    @keyframes kgBlink{0%,42%,50%,100%{transform:scaleY(1)}46%{transform:scaleY(.1)}}
    .kg-snap-time{position:absolute;bottom:-4px;left:50%;transform:translateX(-50%);padding:4px 14px;border-radius:999px;
        background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;font-size:12px;font-weight:900;white-space:nowrap;
        box-shadow:0 8px 14px -6px #7C3AED}

    /* ───────── CSS mockup: Flip Tiles ───────── */
    .kg-tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;width:100%;max-width:250px;padding:6px}
    .kg-tile{aspect-ratio:1;perspective:500px}
    .kg-tile-in{position:relative;width:100%;height:100%;transform-style:preserve-3d;
        animation:kgFlip 7s ease-in-out var(--d) infinite}
    .kg-tile-in span{position:absolute;inset:0;display:grid;place-items:center;border-radius:12px;backface-visibility:hidden;
        -webkit-backface-visibility:hidden;font-size:24px;line-height:1}
    .kg-tile-back{background:linear-gradient(135deg,#7C3AED,#5B21B6);color:rgba(255,255,255,.85);font-size:18px !important;
        box-shadow:0 8px 14px -8px #5B21B6}
    .kg-tile-front{background:#fff;border:2px solid #DDD6FE;transform:rotateY(180deg)}
    @keyframes kgFlip{0%,8%{transform:rotateY(0)}16%,40%{transform:rotateY(180deg)}48%,100%{transform:rotateY(0)}}

    /* ───────── Modal ───────── */
    .kg-modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:16px;
        background:rgba(26,10,18,.62);backdrop-filter:blur(6px)}
    .kg-dialog{position:relative;width:100%;max-width:440px;padding:32px 26px 26px;border-radius:30px;background:#fff;
        border:2px solid #FBCFE8;text-align:center;box-shadow:0 40px 80px -20px rgba(0,0,0,.5);
        animation:kgPop .45s cubic-bezier(.2,1.2,.3,1)}
    @keyframes kgPop{from{opacity:0;transform:scale(.85) translateY(24px)}to{opacity:1;transform:none}}
    .kg-x{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:50%;border:0;background:#F3F4F6;
        color:#6b7280;font-weight:900;transition:.2s}
    .kg-x:hover{background:#FBCFE8;color:#B81B5C;transform:rotate(90deg)}
    .kg-ico{display:grid;place-items:center;width:68px;height:68px;margin:0 auto;border-radius:50%;background:#FFE4EF;
        border:1px solid #FBCFE8;font-size:32px;animation:kgBob 2.6s ease-in-out infinite}
    .kg-dialog h3{margin:16px 0 0;font-size:24px;font-weight:900}
    .kg-dialog .p{margin:5px 0 0;font-size:12.5px;font-weight:700;color:#7a6670}
    .kg-score{margin-top:20px;padding:20px;border-radius:22px;border:2px solid #FBCFE8;background:linear-gradient(135deg,#FFF0F5,#FFF5FB)}
    .kg-score .l{font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#7a6670}
    .kg-score .v{margin:4px 0;font-size:56px;font-weight:900;line-height:1;color:#D6246E}
    .kg-score .o{font-size:12px;font-weight:700;color:#7a6670}
    .kg-bar{height:12px;margin-top:14px;padding:2px;border-radius:999px;background:#FFE4EF;border:1px solid #FBCFE8;overflow:hidden}
    .kg-bar i{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#E0245E,#B81B5C);
        animation:kgGrow 1.3s cubic-bezier(.2,.8,.2,1) both;position:relative}
    @keyframes kgGrow{from{width:0 !important}}
    .kg-rows{margin-top:16px;padding:14px 16px;border-radius:20px;background:rgba(255,240,245,.8);border:1px solid #FBCFE8;
        text-align:left;font-size:12.5px;font-weight:700}
    .kg-rows div{display:flex;justify-content:space-between;gap:10px;padding:6px 0}
    .kg-rows em{font-style:normal;color:#6b5560}
    .kg-rows b{font-weight:900}
    .kg-rows .ok{color:#047857}.kg-rows .no{color:#6b7280}.kg-rows .wt{color:#D97706}.kg-rows .pk{color:#BE185D;font-size:15px}
    .kg-dialog-act{display:flex;gap:10px;margin-top:18px}
    .kg-dialog-act .kg-btn{flex:1}
    .kg-close{padding:12px 20px;border-radius:14px;border:1.5px solid #D1D5DB;background:#fff;color:#4b5563;font-weight:800;font-size:13px}
    .kg-close:hover{background:#F9FAFB}

    /* ───────── Bottom CTA ───────── */
    .kg-cta{position:relative;z-index:1;overflow:hidden;min-height:470px;padding:84px 16px;display:flex;align-items:center;
        justify-content:center;background:url('/image/bottom.jpg') center center / cover no-repeat}
    .kg-cta::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(26,10,18,.25),rgba(26,10,18,.55))}
    .kg-cta-in{position:relative;z-index:1;width:100%;max-width:880px;display:flex;flex-direction:column;align-items:center;gap:30px;text-align:center}
    .kg-quote{display:inline-block;padding:13px 30px;border-radius:999px;border:2px solid rgba(255,255,255,.85);
        background:rgba(0,0,0,.45);backdrop-filter:blur(6px);color:#fff;font-family:Georgia,serif;font-style:italic;
        font-weight:800;font-size:clamp(15px,2.4vw,23px);letter-spacing:.02em;text-shadow:0 2px 6px rgba(0,0,0,.5);
        animation:kgFloatY 5s ease-in-out infinite}
    @keyframes kgFloatY{50%{transform:translateY(-8px)}}
    .kg-big{display:inline-flex;align-items:center;gap:12px;padding:16px 42px;border-radius:999px;border:2px solid #fff;
        background:linear-gradient(135deg,#E0245E,#B81B5C);color:#fff;font-size:19px;font-weight:900;position:relative;overflow:hidden;
        box-shadow:0 20px 40px -12px rgba(224,36,94,.8);transition:transform .2s;animation:kgHeart 2.6s ease-in-out infinite}
    .kg-big:hover{transform:scale(1.07)}
    .kg-big:active{transform:scale(.96)}
    .kg-big::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.5),transparent);transform:skewX(-20deg);animation:kgShine 3s infinite}
    .kg-big .a{font-size:24px;transition:transform .25s}
    .kg-big:hover .a{transform:translateX(7px)}
    @keyframes kgHeart{0%,100%{box-shadow:0 20px 40px -12px rgba(224,36,94,.8)}50%{box-shadow:0 20px 56px -4px rgba(224,36,94,1)}}
    .kg-cta p.j{margin:14px 0 0;color:#fff;font-weight:800;font-size:15px;text-shadow:0 2px 6px rgba(0,0,0,.9)}
    .kg-trust{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;width:100%;max-width:760px}
    @media (max-width:640px){.kg-trust{grid-template-columns:repeat(2,1fr)}}
    .kg-trust div{display:flex;flex-direction:column;align-items:center;gap:8px;padding:18px 10px;border-radius:20px;
        background:rgba(255,255,255,.95);border:2px solid #fff;box-shadow:0 18px 30px -14px rgba(0,0,0,.5);
        font-size:13px;font-weight:900;color:#111827;transition:transform .3s}
    .kg-trust div:hover{transform:translateY(-8px)}
    .kg-trust div span{font-size:32px;line-height:1;display:block;transition:transform .4s}
    .kg-trust div:hover span{transform:scale(1.25) rotate(-8deg)}

    /* ───────── Toast ───────── */
    .kg-toast{position:fixed;right:22px;bottom:22px;z-index:70;width:calc(100% - 44px);max-width:370px;padding:18px;border-radius:24px;
        background:#fff;border:2px solid #EC4899;box-shadow:0 30px 60px -16px rgba(236,72,153,.6);
        animation:kgSlide .6s cubic-bezier(.2,1.1,.3,1) both}
    @keyframes kgSlide{from{opacity:0;transform:translateX(70px)}to{opacity:1;transform:none}}
    .kg-toast-row{display:flex;align-items:center;gap:12px}
    .kg-toast-row .e{font-size:34px;animation:kgPulse 1.1s ease-in-out infinite}
    .kg-toast h4{margin:0;font-size:13.5px;font-weight:900;color:#111827}
    .kg-toast p{margin:3px 0 0;font-size:11.5px;font-weight:600;color:#6b7280}
    .kg-toast-act{display:flex;gap:8px;margin-top:14px}
    .kg-toast-act .kg-btn{padding:10px 14px;background:linear-gradient(135deg,#E0245E,#B81B5C) !important;color:#ffffff !important;font-weight:900 !important;box-shadow:0 8px 16px -6px #E0245E !important}

    @media (prefers-reduced-motion:reduce){
        .kg *,.kg *::before,.kg *::after{animation:none !important;transition:none !important}
        .kg-js .kg-reveal:not(.in){opacity:1}
    }
</style>

<div class="kg" x-data="gamesHub()">

    {{-- floating hearts background --}}
    <div class="kg-hearts" aria-hidden="true">
        <span>♥</span><span>♥</span><span>♥</span><span>♥</span><span>♥</span><span>♥</span>
    </div>

    {{-- ───────────── 1. HERO SECTION — Full Width Banner ───────────── --}}
    <div class="kg-hero">
        <img src="/image/game2.jpg" alt="Koodal Matrimony - Discover Play Connect">
    </div>

    {{-- ───────────── 2. JOURNEY STEPPER ───────────── --}}
    <div class="kg-journey">
        <div class="kg-wrap">
            <h2 class="kg-title kg-reveal">Your Journey with Koodal</h2>
            <p class="kg-sub kg-reveal" style="--i:.1s">From a simple profile to a meaningful connection — every step helps you know each other better.</p>

            <div class="kg-steps kg-reveal" style="--i:.2s"
                 x-data="{ activeStep: 5 }"
                 x-init="setInterval(() => { activeStep = activeStep >= 7 ? 1 : activeStep + 1 }, 2200)">
                @php
                $steps = [
                    ['n'=>1,'icon'=>'👤','label'=>'Profile','sub'=>'Discover new matches','color'=>'#D6246E','bg'=>'#FFF0F5','border'=>'#FBCFE8'],
                    ['n'=>2,'icon'=>'🌺','label'=>'Porutham','sub'=>'Check compatibility','color'=>'#9333EA','bg'=>'#FAF5FF','border'=>'#DDD6FE'],
                    ['n'=>3,'icon'=>'❤️','label'=>'Send Interest','sub'=>'Show interest','color'=>'#DC2626','bg'=>'#FEF2F2','border'=>'#FCA5A5'],
                    ['n'=>4,'icon'=>'🤝','label'=>'Mutual Match','sub'=>"When they match",'color'=>'#0891B2','bg'=>'#ECFEFF','border'=>'#A5F3FC'],
                    ['n'=>5,'icon'=>'🎮','label'=>'4 Games','sub'=>'Play & discover','color'=>'#D6246E','bg'=>'#FFF0F5','border'=>'#FBCFE8'],
                    ['n'=>6,'icon'=>'📊','label'=>'Vibe Meter','sub'=>'See connection','color'=>'#0891B2','bg'=>'#ECFEFF','border'=>'#A5F3FC'],
                    ['n'=>7,'icon'=>'💬','label'=>'Connect','sub'=>'Start chatting','color'=>'#4F46E5','bg'=>'#EEF2FF','border'=>'#C7D2FE'],
                ];
                @endphp

                <div class="kg-steps-track">
                    @for($loopPass = 0; $loopPass < 2; $loopPass++)
                        <div class="kg-steps-row" @if($loopPass === 1) aria-hidden="true" @endif>
                            @foreach($steps as $i => $step)
                                <div class="kg-step" :class="activeStep === {{ $step['n'] }} ? 'is-active' : ''" @mouseenter="activeStep = {{ $step['n'] }}">
                                    <div class="kg-pill"
                                         :class="activeStep === {{ $step['n'] }} ? 'active' : ''"
                                         style="--c: {{ $step['color'] }}; --bg: {{ $step['bg'] }}; --bd: {{ $step['border'] }}">
                                        <span class="n">{{ $step['n'] }}</span>
                                        <span>{{ $step['icon'] }}</span>
                                        <span>{{ $step['label'] }}</span>
                                    </div>
                                    <small>{{ $step['sub'] }}</small>
                                </div>
                                <span class="kg-arrow">→</span>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- ───────────── 3. GAMES SECTION HEADING ───────────── --}}
    <div class="kg-head">
        <div class="kg-chip kg-reveal"><i>✨</i> INTERACTIVE MATCHING EXPERIENCE <i>✨</i></div>
        <h2 class="kg-h2 kg-reveal" style="--i:.1s">4 <span class="kg-grad">Games + 1 Vibe Meter</span></h2>
        <p class="kg-lead kg-reveal" style="--i:.2s">More than just games — it's a journey to know each other! 💕</p>
    </div>

    {{-- ───────────── 4. GAME CARDS ───────────── --}}
    <div class="kg-wrap">
        <div class="kg-grid">

            {{-- CARD 1: KOODAL SYNC --}}
            <div class="kg-card t-sync kg-reveal" style="--i:0s; {{ $syncStatus['partnerPlayed'] && !$syncStatus['userPlayed'] ? '--line:#E0245E;' : '' }}">

                @if($syncStatus['partnerPlayed'] && !$syncStatus['userPlayed'])
                    <div class="kg-alert"><i></i><b>!</b></div>
                    <div class="kg-tip">⏳ {{ $partner->name }} is waiting for your answer!</div>
                @endif

                <div class="kg-top">
                    <div class="kg-id">
                        <span class="kg-num">1</span>
                        <div>
                            <h3>KOODAL SYNC</h3>
                            <span class="s">Compatibility Test</span>
                        </div>
                    </div>
                    <button type="button" @click="showPointsModal = true" class="kg-badge">
                        <span>⭐</span><span>{{ $vibe->games_summary['sync']['score'] ?? 0 }} pts</span>
                    </button>
                </div>

                <div class="kg-status">
                    <span>Partner Status:</span>
                    <b :class="partnerPresence.is_online ? 'is-online' : ''">
                        <span class="kg-dot"></span>
                        <span x-text="partnerPresence.label"></span>
                    </b>
                </div>

                @if($syncStatus['partnerPlayed'] && !$syncStatus['userPlayed'])
                    <div class="kg-note wait"><span>⏳</span> {{ $partner->name }} is waiting for your answer!</div>
                @elseif($syncStatus['userPlayed'] && $syncStatus['partnerPlayed'])
                    <div class="kg-note both">✅ Both played! View your match score →</div>
                @elseif($syncStatus['userPlayed'])
                    <div class="kg-note me">✓ You played — waiting for {{ $partner->name }}</div>
                @endif

                <p class="kg-desc">Answer 10 compatibility questions! Play together in real-time or offline at your own pace.</p>

                <div class="kg-media clickable" @click="showPointsModal = true">
                    <img src="/image/01_Koodal_Sync.png" alt="Koodal Sync Preview">
                </div>

                <div class="kg-actions">
                    <div class="kg-actions-in" style="display:flex; flex-direction:column; gap:8px;">
                        <button type="button" @click="sendInvite('sync')" class="kg-btn" style="background:linear-gradient(135deg,#E0245E,#B81B5C); color:#fff; font-weight:800;">
                            <span>🔴 🎮</span><span>Play Live (Real-Time 1v1)</span>
                        </button>
                        <a href="{{ route('games.sync', $partner->id) }}" class="kg-btn" style="background:linear-gradient(135deg,#4F46E5,#6366F1); color:#fff; font-weight:800; text-align:center;">
                            <span>🔵 📱</span><span>Play Offline (Turn-Based)</span>
                        </a>
                        <button type="button" @click="sendWhatsAppLink('sync')" class="kg-btn" style="background:linear-gradient(135deg,#25D366,#128C7E); color:#fff;">
                            <span>💬</span><span>Share on WhatsApp</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- CARD 2: KOODAL FIND IT --}}
            <div class="kg-card t-find kg-reveal" style="--i:.1s">
                <div class="kg-top">
                    <div class="kg-id">
                        <span class="kg-num">2</span>
                        <div>
                            <h3>KOODAL FIND IT</h3>
                            <span class="s">Find the Difference</span>
                        </div>
                    </div>
                    <span class="kg-badge">🔍 {{ isset($vibe->games_summary['find_it']) && $vibe->games_summary['find_it']['played'] ? $vibe->games_summary['find_it']['score'] . '%' : 'Play Now' }}</span>
                </div>

                <p class="kg-desc">Spot the differences in two similar images. Think fast!</p>

                <div class="kg-media">
                    <img src="/image/02_Koodal_Find_It.png" alt="Koodal Find It Preview">
                </div>

                <div class="kg-actions">
                    <div class="kg-actions-in">
                        <button type="button" @click="sendWhatsAppLink('find_it')" class="kg-btn" style="background:linear-gradient(135deg,#25D366,#128C7E)">
                            <span>💬</span><span>Send Link via WhatsApp</span>
                        </button>
                        <button type="button" @click="sendInvite('find_it')" class="kg-btn">
                            <span>💌</span><span>Invite {{ $partner->name }} to Play</span>
                        </button>
                        <a href="{{ route('games.find_it', $partner->id) }}" class="kg-btn soft">Play Find It →</a>
                    </div>
                </div>
            </div>

            {{-- CARD 3: MEMORY SNAP --}}
            <div class="kg-card t-mem kg-reveal" style="--i:.2s">
                <div class="kg-top">
                    <div class="kg-id">
                        <span class="kg-num">3</span>
                        <div>
                            <h3>MEMORY SNAP</h3>
                            <span class="s">Look. Remember. Answer.</span>
                        </div>
                    </div>
                    <span class="kg-badge">🧠 {{ isset($vibe->games_summary['memory_snap']) && $vibe->games_summary['memory_snap']['played'] ? $vibe->games_summary['memory_snap']['score'] . '%' : 'Play Now' }}</span>
                </div>

                <p class="kg-desc">Look at the photo for 10 seconds, then answer the questions!</p>

                <div class="kg-media clickable" @click="sendInvite('memory_snap')" title="Play Memory Snap" style="cursor:pointer;">
                    <div class="kg-snap">
                        <div class="kg-snap-box">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <defs>
                                    <linearGradient id="kgSnapGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0" stop-color="#7C3AED"/>
                                        <stop offset="1" stop-color="#EC4899"/>
                                    </linearGradient>
                                </defs>
                                <circle class="bg" cx="60" cy="60" r="52"/>
                                <circle class="fg" cx="60" cy="60" r="52"/>
                            </svg>
                            <span class="kg-snap-pic">🖼️</span>
                            <span class="kg-snap-eye">👁️</span>
                            <span class="kg-snap-time">⏱️ 10s</span>
                        </div>
                    </div>
                </div>

                <div class="kg-actions">
                    <div class="kg-actions-in">
                        <button type="button" @click="sendWhatsAppLink('memory_snap')" class="kg-btn" style="background:linear-gradient(135deg,#25D366,#128C7E)">
                            <span>💬</span><span>Send Link via WhatsApp</span>
                        </button>
                        <button type="button" @click="sendInvite('memory_snap')" class="kg-btn">
                            <span>🧠</span><span>Start Game →</span>
                        </button>
                        <a href="{{ route('games.memory_snap.start', $partner->id) }}" class="kg-btn soft">Play Memory Snap →</a>
                    </div>
                </div>
            </div>

            {{-- CARD 4: FLIP THE TILES --}}
            <div class="kg-card t-flip kg-reveal" style="--i:0s">
                <div class="kg-top">
                    <div class="kg-id">
                        <span class="kg-num">4</span>
                        <div>
                            <h3>FLIP THE TILES</h3>
                            <span class="s">1v1 Memory Match</span>
                        </div>
                    </div>
                    <span class="kg-badge">🧩 {{ isset($vibe->games_summary['flip']) && $vibe->games_summary['flip']['played'] ? $vibe->games_summary['flip']['score'] . '%' : '1v1 Live' }}</span>
                </div>

                <p class="kg-desc">Flip and match pairs of cards with your partner in a live 1v1 tile match!</p>

                <div class="kg-media" style="background:radial-gradient(circle at 50% 40%,#F3E8FF,#fff 75%)">
                    @php
                        $faces = ['🌹','🌺','💍','🪔','🌸','💕'];
                        $tiles = [0,3,1,4,2,5,3,0,5,1,4,2];
                    @endphp
                    <div class="kg-tiles" aria-hidden="true">
                        @foreach($tiles as $t)
                            <div class="kg-tile" style="--d: {{ $t * 0.9 }}s">
                                <div class="kg-tile-in">
                                    <span class="kg-tile-back">♥</span>
                                    <span class="kg-tile-front">{{ $faces[$t] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="kg-actions">
                    <div class="kg-actions-in">
                        <button type="button" @click="sendWhatsAppLink('flip')" class="kg-btn" style="background:linear-gradient(135deg,#25D366,#128C7E)">
                            <span>💬</span><span>Send Link via WhatsApp</span>
                        </button>
                        <button type="button" @click="sendInvite('flip')" class="kg-btn">
                            <span>💌</span><span>Invite {{ $partner->name }} to Play Live</span>
                        </button>
                        <a href="{{ route('flip.index') }}" class="kg-btn soft">Create Game Room →</a>
                    </div>
                </div>
            </div>

            {{-- CARD 5: KOODAL VIBE METER --}}
            @php
                $anyHubGamePlayed = false;
                foreach (($vibe->games_summary ?? []) as $gs) {
                    if (!empty($gs['played'])) {
                        $anyHubGamePlayed = true;
                        break;
                    }
                }
            @endphp
            <div class="kg-card t-vibe kg-reveal" style="--i:.2s">
                <div class="kg-top">
                    <div class="kg-id">
                        <span class="kg-num">♥</span>
                        <div>
                            <h3>KOODAL VIBE METER</h3>
                            <span class="s">Connection Summary</span>
                        </div>
                    </div>
                    <span class="kg-badge">💙 {{ $anyHubGamePlayed ? ($vibe->vibe_percentage . '%') : 'Play First' }}</span>
                </div>

                <p class="kg-desc">
                    @if($anyHubGamePlayed)
                        Smart summary of common choices and game results!
                    @else
                        Please play games first — discover your chemistry story based on your game scores!
                    @endif
                </p>

                <div class="kg-media">
                    <img src="/image/05_Koodal_Vibe_Meter.png" alt="Koodal Vibe Meter Preview">
                </div>

                <div class="kg-actions">
                    <div class="kg-actions-in">
                        <span class="kg-tag">Commonality + Vibe Score</span>
                        <a href="{{ route('games.vibe', $partner->id) }}" class="kg-btn">View Vibe Meter →</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ───────────── 5. POINTS / COMPATIBILITY SCORE MODAL ───────────── --}}
    <div x-cloak x-show="showPointsModal" x-transition.opacity class="kg-modal">
        <div class="kg-dialog" @click.away="showPointsModal = false">

            <button type="button" @click="showPointsModal = false" class="kg-x">✕</button>

            <div class="kg-ico">🎯</div>

            <h3>Compatibility Test Score</h3>
            <p class="p">Detailed Points Summary with {{ $partner->name }}</p>

            @if($syncStatus['userPlayed'] && $syncStatus['partnerPlayed'])
                <div class="kg-score">
                    <div class="l">Your Sync Points</div>
                    <div class="v">{{ $vibe->games_summary['sync']['score'] ?? 0 }}</div>
                    <div class="o">out of {{ $vibe->games_summary['sync']['max'] ?? 100 }} points</div>
                    <div class="kg-bar">
                        <i style="width: {{ $vibe->games_summary['sync']['percentage'] ?? 0 }}%"></i>
                    </div>
                </div>
            @else
                <div class="kg-score">
                    <div class="l">Status</div>
                    <div style="margin:10px 0; font-size:18px; font-weight:900; color:#D6246E;">
                        ⏳ Waiting for Opposite Answer
                    </div>
                    <div class="o">
                        @if($syncStatus['userPlayed'] && !$syncStatus['partnerPlayed'])
                            You have completed the test! Waiting for {{ $partner->name }} to answer.
                        @elseif(!$syncStatus['userPlayed'] && $syncStatus['partnerPlayed'])
                            {{ $partner->name }} has already played! Waiting for your answer — please play.
                        @else
                            Both players need to complete the test to view the final compatibility score.
                        @endif
                    </div>
                </div>
            @endif

            <div class="kg-rows">
                <div>
                    <em>Match Accuracy:</em>
                    <b class="pk">{{ ($syncStatus['userPlayed'] && $syncStatus['partnerPlayed']) ? (($vibe->games_summary['sync']['percentage'] ?? 0) . '%') : '⏳ Waiting' }}</b>
                </div>
                <div>
                    <em>Your Answer Status:</em>
                    <b class="{{ $syncStatus['userPlayed'] ? 'ok' : 'no' }}">
                        {{ $syncStatus['userPlayed'] ? '✓ Completed' : 'Not Played Yet' }}
                    </b>
                </div>
                <div>
                    <em>{{ $partner->name }}'s Answer Status:</em>
                    <b class="{{ $syncStatus['partnerPlayed'] ? 'ok' : 'wt' }}">
                        {{ $syncStatus['partnerPlayed'] ? '✓ Completed' : '⏳ Waiting for Answer' }}
                    </b>
                </div>
            </div>

            <div class="kg-dialog-act">
                <a href="{{ route('games.sync', $partner->id) }}" class="kg-btn">
                    {{ $syncStatus['userPlayed'] ? 'Play Again →' : 'Play Test Now →' }}
                </a>
                <button type="button" @click="showPointsModal = false" class="kg-close">Close</button>
            </div>
        </div>
    </div>

    {{-- ───────────── 6. BOTTOM SUNSET CTA BANNER ───────────── --}}
    <div class="kg-cta">
        <div class="kg-cta-in">

            <div class="kg-quote kg-reveal">
                "Same interests... Different stories... One beautiful journey... ♡"
            </div>

            <div class="kg-reveal" style="--i:.1s">
                <a href="{{ route('games.sync', $partner->id) }}" class="kg-big">
                    <span>Find Your Match Today</span>
                    <span class="a">→</span>
                </a>
                <p class="j">Join Koodal Matrimony and experience a new way to connect.</p>
            </div>

            <div class="kg-trust kg-reveal" style="--i:.2s">
                <div><span>🛡️</span>Safe & Secure</div>
                <div><span>✅</span>Verified Profiles</div>
                <div><span>🎮</span>Fun & Meaningful Games</div>
                <div><span>❤️</span>Real Connections</div>
            </div>
        </div>
    </div>

    {{-- REAL-TIME INCOMING INVITE TOAST NOTIFICATION POPUP --}}
    <div x-cloak x-show="incomingInvite" class="kg-toast">
        <div class="kg-toast-row">
            <span class="e">💕</span>
            <div>
                <h4 x-text="incomingInvite?.is_offline_waiting ? `${incomingInvite?.sender_name} already played — waiting for your answer!` : `${incomingInvite?.sender_name} invited you to play!`"></h4>
                <p x-text="incomingInvite?.custom_message || 'Accept the invitation to join the game room now.'"></p>
            </div>
        </div>
        <div class="kg-toast-act">
            <button type="button" @click="acceptInvite(incomingInvite.id, $event)" class="kg-btn" style="background:linear-gradient(135deg,#E0245E,#B81B5C) !important;color:#ffffff !important;font-weight:900 !important;box-shadow:0 8px 16px -6px #E0245E !important;" x-text="incomingInvite?.is_offline_waiting ? 'Accept & Play →' : 'Accept & Join →'"></button>
            <button type="button" @click="declineInvite(incomingInvite.id)" class="kg-close">Decline</button>
        </div>
    </div>

</div>

{{-- ═════════════════════ ORIGINAL LOGIC (unchanged) ═════════════════════ --}}
<script>
function gamesHub() {
    return {
        showPointsModal: false,
        partnerId: {{ $partner->id }},
        partnerName: '{{ addslashes($partner->name) }}',
        partnerMobile: @json($partner->mobile ?? null),
        partnerPresence: @json($partnerPresence),
        incomingInvite: null,

        init() {
            this.checkPendingInvites();
            setInterval(() => this.checkPendingInvites(), 4000);
        },

        sendWhatsAppLink(gameType) {
            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: gameType })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                const relativePath = ((d && d.redirect_url) || (gameType === 'flip' ? '/flip' : `/games/${this.partnerId}`)).replace(/^https?:\/\/[^\/]+/, '');
                const targetUrl = window.location.origin + relativePath;
                let gameName = 'Koodal Matrimony Games';
                if (gameType === 'sync') gameName = 'Koodal Sync (Compatibility Test)';
                else if (gameType === 'find_it') gameName = 'Koodal Find It';
                else if (gameType === 'memory_snap') gameName = 'Memory Snap';
                else if (gameType === 'flip') gameName = 'Flip the Tiles (1v1 Live Match)';

                const text = encodeURIComponent(`Hi ${this.partnerName}! Join me for a game of ${gameName} on Koodal Matrimony!\nPlay here: ${targetUrl}`);

                let waUrl = `https://wa.me/?text=${text}`;
                if (this.partnerMobile) {
                    let cleanNum = String(this.partnerMobile).replace(/[^0-9]/g, '');
                    if (cleanNum.length === 10) cleanNum = '91' + cleanNum;
                    if (cleanNum) waUrl = `https://wa.me/${cleanNum}?text=${text}`;
                }

                // 1. Open WhatsApp window for user to share message
                window.open(waUrl, '_blank');

                // 2. Simultaneously redirect sender's browser to the game starting room/page
                if (d && d.redirect_url) {
                    const target = d.redirect_url.replace(/^https?:\/\/[^\/]+/, '');
                    window.location.href = target || d.redirect_url;
                } else if (gameType === 'flip') {
                    window.location.href = '/flip';
                } else if (gameType === 'memory_snap') {
                    window.location.href = `/memory-snap/start/${this.partnerId}`;
                }
            })
            .catch(err => {
                console.warn("sendWhatsAppLink fallback:", err);
                const fallbackUrl = gameType === 'flip' ? window.location.origin + '/flip' : (gameType === 'memory_snap' ? window.location.origin + `/memory-snap/start/${this.partnerId}` : window.location.href);
                let gameName = gameType === 'flip' ? 'Flip the Tiles (1v1 Live Match)' : (gameType === 'memory_snap' ? 'Memory Snap' : 'Koodal Matrimony Games');
                const text = encodeURIComponent(`Hi ${this.partnerName}! Join me for a game of ${gameName} on Koodal Matrimony!\nPlay here: ${fallbackUrl}`);
                let waUrl = `https://wa.me/?text=${text}`;
                if (this.partnerMobile) {
                    let cleanNum = String(this.partnerMobile).replace(/[^0-9]/g, '');
                    if (cleanNum.length === 10) cleanNum = '91' + cleanNum;
                    if (cleanNum) waUrl = `https://wa.me/${cleanNum}?text=${text}`;
                }
                window.open(waUrl, '_blank');
                if (gameType === 'flip') {
                    window.location.href = '/flip';
                } else if (gameType === 'memory_snap') {
                    window.location.href = `/memory-snap/start/${this.partnerId}`;
                }
            });
        },

        sendInvite(gameType) {
            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: gameType })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                if (d && d.redirect_url) {
                    const target = d.redirect_url.replace(/^https?:\/\/[^\/]+/, '');
                    window.location.href = target || d.redirect_url;
                } else if (d && d.room_code) {
                    if (gameType === 'flip') {
                        window.location.href = `/flip/${d.room_code}`;
                    } else if (gameType === 'memory_snap') {
                        window.location.href = `/memory-snap/room/${d.room_code}`;
                    } else {
                        window.location.href = `/games/room/${d.room_code}`;
                    }
                } else if (gameType === 'flip') {
                    window.location.href = '/flip';
                } else if (gameType === 'memory_snap') {
                    window.location.href = `/memory-snap/start/${this.partnerId}`;
                } else if (gameType === 'find_it') {
                    window.location.href = `/games/${this.partnerId}/find-it`;
                } else {
                    window.location.href = `/games/${this.partnerId}/sync`;
                }
            })
            .catch(err => {
                console.warn("sendInvite fallback:", err);
                if (gameType === 'flip') {
                    window.location.href = '/flip';
                } else if (gameType === 'memory_snap') {
                    window.location.href = `/memory-snap/start/${this.partnerId}`;
                } else if (gameType === 'find_it') {
                    window.location.href = `/games/${this.partnerId}/find-it`;
                } else {
                    window.location.href = `/games/${this.partnerId}/sync`;
                }
            });
        },

        checkPendingInvites() {
            fetch('/games/invites/pending')
                .then(r => r.json())
                .then(d => {
                    if (d.invites && d.invites.length > 0) {
                        this.incomingInvite = d.invites[0];
                    }
                });
        },

        acceptInvite(inviteId, event) {
            const btn = event ? event.target.closest('button') : null;
            if (btn) {
                btn.innerHTML = '⏳ Joining...';
                btn.disabled = true;
            }
            fetch(`/games/invites/${inviteId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json().catch(() => ({ redirect: `/games/invites/${inviteId}/accept` })))
            .then(d => {
                if (d && d.redirect) {
                    const target = d.redirect.replace(/^https?:\/\/[^\/]+/, '');
                    window.location.href = target || d.redirect;
                } else {
                    window.location.href = `/games/invites/${inviteId}/accept`;
                }
            })
            .catch(err => {
                console.error('Accept invite error:', err);
                window.location.href = `/games/invites/${inviteId}/accept`;
            });
        },

        declineInvite(inviteId) {
            fetch(`/games/invites/${inviteId}/decline`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(() => {
                this.incomingInvite = null;
            });
        }
    }
}
</script>

{{-- Scroll-reveal animation (visual only, independent of page logic) --}}
<script>
(function () {
    var items = document.querySelectorAll('.kg-reveal');
    if (!('IntersectionObserver' in window)) { return; }
    document.documentElement.classList.add('kg-js');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>
@endsection