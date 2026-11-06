<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pricing Plans — {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', -apple-system, sans-serif;
            padding: 30px 20px 60px;
            background: linear-gradient(180deg, #FFF1F5 0, #FBF7F9 320px);
            color: #1e293b;
        }

        /* Pink & Red Website Theme Gradient (#e11d48 & #c2185b) */
        .btn-theme {
            background: linear-gradient(135deg, #e11d48 0%, #c2185b 100%);
            border: none;
            color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
        }
        .btn-theme:hover, .btn-theme:focus {
            background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(190, 18, 60, 0.35);
            transform: translateY(-1px);
        }

        .btn-waiting {
            background-color: #fef3c7 !important;
            border: 1.5px solid #f59e0b !important;
            color: #b45309 !important;
            font-weight: 600;
            cursor: not-allowed;
            box-shadow: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-waiting:hover, .btn-waiting:focus {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }

        .back-btn {
            border: 1px solid #fecdd3;
            color: #be123c;
            background: #fff1f4;
            border-radius: 30px;
            padding: 8px 22px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: #ffe4e6;
            color: #9f1239;
            border-color: #fda4af;
        }

        .pricing-header {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 35px;
        }

        .pricing-header h2 {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 700;
            font-size: 2rem;
            color: #0f172a;
        }

        .pricing-header p {
            color: #64748b;
            font-size: 1rem;
        }

        .avatar-group {
            display: inline-flex;
            align-items: center;
        }
        .avatar-group img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            object-fit: cover;
        }
        .avatar-group img:not(:first-child) {
            margin-left: -12px;
        }

        .plan-card {
            border: 1px solid #EADFE5;
            border-radius: 22px;
            padding: 35px 26px;
            height: 100%;
            background: #ffffff;
            transition: all 0.25s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .plan-card:hover {
            border-color: #f43f5e;
            box-shadow: 0 10px 28px rgba(225, 29, 72, 0.1);
            transform: translateY(-3px);
        }

        /* Same height on every card whether or not it carries a badge, so the
           title, price, button and feature list start at the same y. */
        .badge-slot {
            min-height: 30px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .plan-card h4 {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.45rem;
        }

        .plan-card.current-plan {
            border: 2px solid #e11d48;
            box-shadow: 0 10px 28px rgba(225, 29, 72, 0.18);
            background: linear-gradient(180deg, #fff1f4 0%, #ffffff 120px);
        }

        .plan-card.requested-plan {
            border: 2px solid #f59e0b;
            box-shadow: 0 10px 28px rgba(245, 158, 11, 0.18);
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 120px);
        }

        .current-badge {
            background: linear-gradient(135deg, #e11d48 0%, #c2185b 100%);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25);
        }

        .requested-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
        }

        .price {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin: 10px 0 4px;
        }

        .tick-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            margin: 1px 10px 0 0;
            flex-shrink: 0;
            border-radius: 50%;
            background:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center / 62% no-repeat,
                linear-gradient(135deg, #e11d48 0%, #c2185b 100%);
        }

        .tick-icon.sm {
            width: 15px;
            height: 15px;
            margin: 2px 8px 0 0;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 0.92rem;
            color: #334155;
            line-height: 1.45;
            text-align: left;
        }

        .features-list {
            flex-grow: 1;
            margin-top: 15px;
            text-align: left;
        }

        .plan-desc {
            color: #64748b;
            font-size: 0.9rem;
            min-height: 42px;
            margin-bottom: 8px;
        }

        /* Admin Panel Highlight Box */
        .admin-panel-box {
            background: linear-gradient(135deg, #fff1f4 0%, #ffe4e6 100%);
            border: 1.5px solid #fecdd3;
            border-left: 5px solid #e11d48;
            border-radius: 14px;
            padding: 16px 16px 8px;
            margin: 18px 0 8px;
            text-align: left;
        }

        .admin-panel-box .feature-item {
            margin-bottom: 9px;
            font-size: 0.88rem;
        }

        .admin-panel-badge {
            background: linear-gradient(135deg, #e11d48 0%, #c2185b 100%);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 1160px;">
    {{-- Top Back to Dashboard Button --}}
    <div class="mb-3">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">
            &larr; Back to Dashboard
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('status'))
        <div class="alert alert-info alert-dismissible fade show rounded-3" role="alert">
            <strong>Status:</strong> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <strong>Success:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <strong>Notice:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Pending Upgrade Notice Banner --}}
    @if(!empty($limit->requested_plan))
        @php
            $reqPlan = $plans[$limit->requested_plan] ?? null;
            $reqPlanName = $reqPlan['name'] ?? ucfirst($limit->requested_plan);
            $reqWhatsAppUrl = $whatsappUrls[$limit->requested_plan] ?? '#';
        @endphp
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 rounded-4 shadow-sm" role="alert" style="background-color: #fffbeb; border: 1.5px solid #fcd34d;">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size: 1.3rem;">⏳</span>
                <div>
                    <strong style="color: #92400e;">Waiting for admin update:</strong> You have requested to upgrade to the <strong>{{ $reqPlanName }} Plan</strong>.
                </div>
            </div>
            <div>
                <a href="{{ $reqWhatsAppUrl }}" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #16a34a; border: none;">
                    WhatsApp (918072515050) &rarr;
                </a>
            </div>
        </div>
    @endif

    {{-- Center Header matching the user screenshot with Pink/Red theme --}}
    <div class="pricing-header">
        <h2>Matrimony Pricing Plans</h2>
        <p>Choose the plan that matches your category count and management needs.</p>

        {{-- Avatars + Tagline --}}
        <div class="d-flex justify-content-center align-items-center mb-3 flex-wrap gap-2">
            <div class="avatar-group">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User">
                <img src="https://randomuser.me/api/portraits/women/2.jpg" alt="User">
                <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="User">
                <img src="https://randomuser.me/api/portraits/women/4.jpg" alt="User">
                <img src="https://randomuser.me/api/portraits/men/5.jpg" alt="User">
                <img src="https://randomuser.me/api/portraits/women/6.jpg" alt="User">
            </div>
            <span class="text-muted small ms-2">Join amazing creators &amp; entrepreneurs</span>
        </div>

        {{-- Yearly Badge matching screenshot in Pink/Red theme --}}
        <div class="d-flex justify-content-center align-items-center gap-2">
            <button type="button" class="btn btn-theme rounded-pill px-4 py-1.5 fw-semibold" style="pointer-events: none; font-size: 0.95rem;">
                Yearly
            </button>
            <span class="badge bg-success rounded-pill px-2.5 py-1.5" style="font-size: 0.75rem;">
                20% off
            </span>
        </div>
    </div>

    {{-- Plan Cards Grid matching screenshot --}}
    @php
        $currentPlanKey = strtolower($limit->plan_type ?? 'basic');
        $requestedPlanKey = strtolower($limit->requested_plan ?? '');
    @endphp

    <div class="row g-4 justify-content-center text-center">
        @foreach($plans as $key => $plan)
            @php
                $isCurrent = ($currentPlanKey === strtolower($key));
                $isRequested = (!$isCurrent && $requestedPlanKey === strtolower($key));
            @endphp
            <div class="col-lg-4 col-md-6 d-flex">
                <div class="plan-card w-100 {{ $isCurrent ? 'current-plan' : ($isRequested ? 'requested-plan' : '') }}">
                    <div class="badge-slot">
                        @if($isCurrent)
                            <span class="current-badge">Current Plan</span>
                        @elseif($isRequested)
                            <span class="requested-badge">Waiting for Admin Update</span>
                        @endif
                    </div>

                    <h4 class="fw-bold mb-1 text-dark">{{ $plan['name'] }}</h4>
                    <p class="plan-desc">{{ $plan['description'] }}</p>

                    <div class="price">
                        {{ $plan['price'] }}
                    </div>
                    <div class="text-muted small mb-4">
                        {{ $plan['period'] ?? 'Yearly plan' }}
                    </div>

                    {{-- Action Button --}}
                    <div class="mb-4">
                        @if($isCurrent)
                            <button type="button" class="btn btn-secondary w-100 rounded-pill py-2 fw-semibold" disabled>
                                Current Plan
                            </button>
                        @elseif($isRequested)
                            <button type="button" class="btn btn-waiting w-100 rounded-pill py-2 fw-semibold" disabled>
                                Waiting for admin update
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.upgrade.store') }}" class="upgrade-form" data-whatsapp="{{ $whatsappUrls[$key] ?? '' }}" data-plan-name="{{ $plan['name'] }}">
                                @csrf
                                <input type="hidden" name="plan_type" value="{{ $key }}">
                                <button type="submit" class="btn btn-theme w-100 rounded-pill py-2 fw-semibold">
                                    Get Started
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Features List --}}
                    <div class="features-list">
                        @if($key === 'basic')
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>100 Users</strong></span>
                            </div>
                            @foreach($plan['features'] as $feature)
                                @if($feature !== '100 Users')
                                    <div class="feature-item">
                                        <span class="tick-icon"></span>
                                        <span>{{ $feature }}</span>
                                    </div>
                                @endif
                            @endforeach

                            {{-- Highlighted Admin Panel Box --}}
                            @if(!empty($plan['admin_features']))
                                <div class="admin-panel-box">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="admin-panel-badge">Admin Panel</span>
                                        <span class="fw-bold" style="color: #9f1239; font-size: 0.85rem;">Included Modules:</span>
                                    </div>
                                    @foreach($plan['admin_features'] as $adminFeature)
                                        <div class="feature-item">
                                            <span class="tick-icon sm"></span>
                                            <span style="color: #334155;">{{ $adminFeature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        @elseif($key === 'premium')
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>250 Users</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>All In Basic Plan</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span>Customer Android App</span>
                            </div>

                        @elseif($key === 'advanced')
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>500 Users</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>All In Basic & Premium Plan</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span>Customer Android & iOS App</span>
                            </div>

                        @elseif($key === 'unlimited')
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>Unlimited Users</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>Lifetime Access</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span><strong>All In Basic, Premium & Advanced Plan</strong></span>
                            </div>
                            <div class="feature-item">
                                <span class="tick-icon"></span>
                                <span>Customer Android & iOS Apps</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle clicking Get Started
        document.querySelectorAll('.upgrade-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const whatsappUrl = form.getAttribute('data-whatsapp');
                const submitBtn = form.querySelector('button[type="submit"]');

                // Open WhatsApp immediately in new tab
                if (whatsappUrl) {
                    window.open(whatsappUrl, '_blank');
                }

                // Immediately update button UI to "Waiting for admin update"
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.className = 'btn btn-waiting w-100 rounded-pill py-2 fw-semibold';
                    submitBtn.innerHTML = 'Waiting for admin update';
                }

                // Send request to server to record requested_plan in database
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (res) {
                    return res.json();
                })
                .then(function () {
                    // Reload page to display updated database state and banner
                    window.location.reload();
                })
                .catch(function () {
                    // Fallback to normal form submit if fetch fails
                    form.submit();
                });
            });
        });

        // If redirected with WhatsApp URL from server fallback
        @if(session('whatsapp_redirect'))
            window.open("{{ session('whatsapp_redirect') }}", '_blank');
        @endif
    });
</script>
</body>
</html>
