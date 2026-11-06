@extends('admin.layouts.admin')

@section('title', 'Memory Snap Manager — Admin')

@section('admin-body')
<style>
    .mm{display:flex;flex-direction:column;gap:22px}
    .mm *,.mm *::before,.mm *::after{box-sizing:border-box}
    .mm a{text-decoration:none}
    .mm button{font-family:inherit;cursor:pointer}

    /* ───────── Header ───────── */
    .mm-head{display:flex;flex-direction:column;gap:16px;animation:mmUp .5s both}
    @media (min-width:900px){.mm-head{flex-direction:row;align-items:center;justify-content:space-between}}
    .mm-title{display:flex;flex-wrap:wrap;align-items:center;gap:12px}
    .mm-title h1{margin:0;font-size:26px;font-weight:800;letter-spacing:-.02em;color:#0F172A}
    .mm-count{display:inline-flex;align-items:center;padding:5px 14px;border-radius:999px;background:#F3E8FF;border:1px solid #D8B4FE;
        color:#581C87;font-size:12px;font-weight:800}
    .mm-sub{margin:6px 0 0;max-width:520px;font-size:14px;line-height:1.5;color:#475569}

    .mm-nav{display:flex;flex-wrap:wrap;align-items:center;gap:10px}

    /* ───────── Buttons ───────── */
    .mm-btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;gap:7px;
        padding:10px 18px;border-radius:14px;border:1.5px solid transparent;font-size:13.5px;font-weight:800;line-height:1.2;
        white-space:nowrap;transition:transform .2s,box-shadow .2s,background .2s,border-color .2s}
    .mm-btn:hover{transform:translateY(-2px)}
    .mm-btn:active{transform:scale(.97)}
    .mm-btn:focus-visible{outline:3px solid #C4B5FD;outline-offset:2px}

    .mm-btn.light{background:#fff;border-color:#CBD5E1;color:#1E293B;box-shadow:0 4px 10px -6px rgba(15,23,42,.35)}
    .mm-btn.light:hover{background:#F5F3FF;border-color:#A78BFA;color:#5B21B6}

    .mm-btn.primary{background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;border-color:transparent;
        box-shadow:0 12px 22px -10px #7C3AED}
    .mm-btn.primary:hover{box-shadow:0 16px 28px -10px #7C3AED}
    .mm-btn.primary::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);transform:skewX(-20deg);animation:mmShine 3.4s ease-in-out infinite}

    .mm-btn.add{margin-left:6px;background:linear-gradient(135deg,#E0245E,#B81B5C);color:#fff;box-shadow:0 12px 22px -10px #E0245E}
    .mm-btn.add:hover{box-shadow:0 16px 28px -10px #E0245E}
    .mm-btn.add .plus{display:grid;place-items:center;width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.25);font-size:16px;line-height:1;transition:transform .3s}
    .mm-btn.add:hover .plus{transform:rotate(90deg)}
    .mm-btn.add::after{content:"";position:absolute;top:0;left:-80%;width:50%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);transform:skewX(-20deg);animation:mmShine 3.4s ease-in-out infinite}
    @keyframes mmShine{0%,55%{left:-80%}100%{left:140%}}

    .mm-btn.dark{background:#0F172A;color:#fff;padding:10px 22px}
    .mm-btn.dark:hover{background:#000}
    .mm-btn.ghost{background:#fff;border-color:#CBD5E1;color:#334155}
    .mm-btn.ghost:hover{background:#F8FAFC}

    .mm-act{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border-radius:12px;border:0;font-size:12.5px;font-weight:800;transition:.2s}
    .mm-act:hover{transform:translateY(-2px)}
    .mm-act.edit{background:#FEF3C7;color:#92400E}
    .mm-act.edit:hover{background:#FDE68A}
    .mm-act.del{background:#FFE4E6;color:#BE123C}
    .mm-act.del:hover{background:#FECDD3}

    /* ───────── Filter ───────── */
    .mm-filter{padding:16px;border-radius:20px;background:#fff;border:1px solid #E2E8F0;box-shadow:0 6px 16px -12px rgba(15,23,42,.4);animation:mmUp .5s .08s both}
    .mm-filter form{display:flex;flex-direction:column;gap:12px}
    @media (min-width:640px){.mm-filter form{flex-direction:row;align-items:center}}
    .mm-search{position:relative;flex:1}
    .mm-search span{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none}
    .mm-search input{width:100%;padding:11px 16px 11px 42px;border-radius:14px;border:1.5px solid #CBD5E1;background:#fff;
        font-size:14px;font-weight:500;font-family:inherit;color:#0F172A;transition:.2s}
    .mm-search input:focus{outline:none;border-color:#7C3AED;box-shadow:0 0 0 4px #EDE9FE}
    .mm-fbtns{display:flex;align-items:center;gap:10px}

    /* ───────── Table card ───────── */
    .mm-card{overflow:hidden;border-radius:22px;background:#fff;border:1px solid #E2E8F0;box-shadow:0 14px 30px -22px rgba(15,23,42,.5);animation:mmUp .5s .16s both}
    .mm-table-wrap{display:none;overflow-x:auto}
    .mm-mobile{display:block;padding:14px;display:flex;flex-direction:column;gap:14px}
    @media (min-width:768px){.mm-table-wrap{display:block}.mm-mobile{display:none}}

    .mm-table{width:100%;border-collapse:collapse;text-align:left;font-size:14px;color:#334155}
    .mm-table thead{background:#F8FAFC;border-bottom:1px solid #E2E8F0}
    .mm-table th{padding:16px 24px;font-size:11.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#475569}
    .mm-table th.r,.mm-table td.r{text-align:right}
    .mm-table td{padding:14px 24px;border-bottom:1px solid #F1F5F9;vertical-align:middle}
    .mm-table tbody tr{transition:background .2s;animation:mmUp .45s both}
    .mm-table tbody tr:nth-child(2){animation-delay:.06s}
    .mm-table tbody tr:nth-child(3){animation-delay:.12s}
    .mm-table tbody tr:nth-child(4){animation-delay:.18s}
    .mm-table tbody tr:hover{background:#FAF5FF}
    .mm-table tbody tr:last-child td{border-bottom:0}

    .mm-thumb{display:block;width:64px;height:64px;object-fit:cover;border-radius:16px;border:2px solid #E9D5FF;box-shadow:0 8px 14px -8px #7C3AED;transition:transform .3s}
    .mm-table tr:hover .mm-thumb{transform:scale(1.12) rotate(-3deg)}
    .mm-name{font-size:16px;font-weight:800;color:#0F172A}
    .mm-meta{display:flex;align-items:center;gap:8px;margin-top:5px;font-size:12px;color:#64748B}
    .mm-meta code{font-family:ui-monospace,monospace;font-size:11px;color:#94A3B8}
    .mm-dot{width:9px;height:9px;border-radius:50%;background:#CBD5E1}
    .mm-dot.on{background:#10B981;animation:mmPing 1.8s ease-out infinite}
    @keyframes mmPing{0%{box-shadow:0 0 0 0 rgba(16,185,129,.55)}100%{box-shadow:0 0 0 8px rgba(16,185,129,0)}}
    .mm-q{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;background:#F3E8FF;border:1px solid #D8B4FE;
        color:#581C87;font-size:12px;font-weight:800}
    .mm-date{font-size:12.5px;font-weight:600;color:#475569}
    .mm-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px}
    .mm-actions form{margin:0}

    /* mobile card */
    .mm-mc{padding:16px;border-radius:20px;border:1px solid #E2E8F0;background:#fff;box-shadow:0 6px 14px -10px rgba(15,23,42,.4)}
    .mm-mc-top{display:flex;align-items:flex-start;gap:12px}
    .mm-mc-top .mm-thumb{flex:none}
    .mm-mc h3{margin:0;font-size:14px;font-weight:800;line-height:1.35;color:#0F172A}
    .mm-mc .mm-q{margin-top:8px;padding:3px 11px;font-size:11px}
    .mm-mc-bot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid #F1F5F9;font-size:12px;color:#64748B}
    .mm-mc-bot form{margin:0}

    .mm-pager{padding:16px;border-top:1px solid #E2E8F0}

    /* ───────── Empty ───────── */
    .mm-empty{padding:56px 20px;text-align:center}
    .mm-empty-ico{display:grid;place-items:center;width:84px;height:84px;margin:0 auto;border-radius:50%;background:#F3E8FF;border:1px solid #D8B4FE;
        font-size:40px;animation:mmBob 3s ease-in-out infinite}
    .mm-empty h3{margin:18px 0 0;font-size:18px;font-weight:800;color:#0F172A}
    .mm-empty p{margin:8px auto 22px;max-width:420px;font-size:14px;line-height:1.5;color:#64748B}
    @keyframes mmBob{50%{transform:translateY(-8px)}}

    @keyframes mmUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
    @media (prefers-reduced-motion:reduce){.mm *,.mm *::before,.mm *::after{animation:none !important;transition:none !important}}
</style>

<div class="mm">

    {{-- Header & Navigation Bar --}}
    <div class="mm-head">
        <div>
            <div class="mm-title">
                <h1>🧠 Memory Snap Manager</h1>
                <span class="mm-count">{{ $totalCount }} {{ $totalCount == 1 ? 'Memory' : 'Memories' }}</span>
            </div>
            <p class="mm-sub">Manage image recall challenge packs (photos + detail recall questions) for Memory Snap 1v1.</p>
        </div>

        {{-- Nav Buttons + Add Memory --}}
        <div class="mm-nav">
            <a href="{{ route('admin.games.questions.index') }}" class="mm-btn light">🎯 Sync Questions</a>
            <a href="{{ route('admin.games.find_it.index') }}" class="mm-btn light">🔍 Find It</a>
            <a href="{{ route('admin.games.memory_snap.index') }}" class="mm-btn primary">🧠 Memory Snap</a>
            <a href="{{ route('admin.flip-tiles.index') }}" class="mm-btn light">🧩 Flip Tiles</a>
            <a href="{{ route('admin.games.vibe_stories.index') }}" class="mm-btn light">❤️ Vibe Stories</a>
            <a href="{{ route('admin.games.memory_snap.create') }}" class="mm-btn add">
                <span class="plus">+</span> Add Memory
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="mm-filter">
        <form method="GET" action="{{ route('admin.games.memory_snap.index') }}">
            <div class="mm-search">
                <span>🔍</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search challenges by title...">
            </div>
            <div class="mm-fbtns">
                <button type="submit" class="mm-btn dark">Filter</button>
                @if(request('search'))
                    <a href="{{ route('admin.games.memory_snap.index') }}" class="mm-btn ghost">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table & Card List --}}
    <div class="mm-card">
        @if($challenges->count() > 0)

        {{-- Desktop Table --}}
        <div class="mm-table-wrap">
            <table class="mm-table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Challenge Title</th>
                        <th>Questions Count</th>
                        <th>Created Date</th>
                        <th class="r">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($challenges as $c)
                    <tr>
                        <td>
                            <img src="{{ $c->image_url }}" alt="Memory Thumbnail" class="mm-thumb">
                        </td>
                        <td>
                            <div class="mm-name">{{ $c->title }}</div>
                            <div class="mm-meta">
                                <code>#{{ $c->id }}</code>
                                <span class="mm-dot {{ $c->is_active ? 'on' : '' }}"></span>
                                <span>{{ $c->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="mm-q">❓ {{ $c->questions_count }} {{ $c->questions_count == 1 ? 'Question' : 'Questions' }}</span>
                        </td>
                        <td class="mm-date">
                            {{ $c->created_at ? $c->created_at->format('M d, Y') : 'N/A' }}
                        </td>
                        <td class="r">
                            <div class="mm-actions">
                                <a href="{{ route('admin.games.memory_snap.edit', $c->id) }}" class="mm-act edit" title="Edit">✏️ Edit</a>
                                <form method="POST" action="{{ route('admin.games.memory_snap.destroy', $c->id) }}" onsubmit="return confirm('Delete this Memory Challenge and all its questions?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mm-act del" title="Delete">🗑️ Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Stacked Cards --}}
        <div class="mm-mobile">
            @foreach($challenges as $c)
            <div class="mm-mc">
                <div class="mm-mc-top">
                    <img src="{{ $c->image_url }}" alt="Memory Thumbnail" class="mm-thumb">
                    <div>
                        <h3>{{ $c->title }}</h3>
                        <span class="mm-q">❓ {{ $c->questions_count }} {{ $c->questions_count == 1 ? 'Question' : 'Questions' }}</span>
                    </div>
                </div>

                <div class="mm-mc-bot">
                    <span>Created: {{ $c->created_at ? $c->created_at->format('M d, Y') : 'N/A' }}</span>
                    <div class="mm-actions">
                        <a href="{{ route('admin.games.memory_snap.edit', $c->id) }}" class="mm-act edit">Edit</a>
                        <form method="POST" action="{{ route('admin.games.memory_snap.destroy', $c->id) }}" onsubmit="return confirm('Delete challenge?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mm-act del">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination Links --}}
        <div class="mm-pager">
            {{ $challenges->links() }}
        </div>

        @else
        {{-- Empty State --}}
        <div class="mm-empty">
            <div class="mm-empty-ico">🧠</div>
            <h3>No memories added yet</h3>
            <p>Create your first Memory Snap challenge (1 photo + multiple recall questions) to start building the game challenge bank.</p>
            <a href="{{ route('admin.games.memory_snap.create') }}" class="mm-btn add" style="margin-left:0">
                <span class="plus">+</span> Add your first memory
            </a>
        </div>
        @endif
    </div>

</div>
@endsection