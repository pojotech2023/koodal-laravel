<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Too Many Requests — {{ config('app.name', 'Koodal Matrimony') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #fff5f8 0%, #fef2f2 50%, #fff 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(184, 27, 92, 0.12), 0 0 1px 1px rgba(0,0,0,0.05);
            max-width: 480px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #ffe4e6;
            color: #b81b5c;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-circle svg {
            width: 40px;
            height: 40px;
        }
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .badge {
            display: inline-block;
            background: #fff1f2;
            color: #b81b5c;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 9999px;
            margin-bottom: 24px;
            border: 1px solid #fecdd3;
        }
        .countdown {
            font-weight: 700;
            color: #b81b5c;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #b81b5c 0%, #901246 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            padding: 13px 24px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(184, 27, 92, 0.25);
            cursor: pointer;
            border: none;
            width: 100%;
        }
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .btn-secondary {
            display: inline-block;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover {
            background: #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="badge">Security Protection Active</div>
        <h1>Too Many Requests</h1>
        <p>You have made too many attempts in a short period. For your security, please wait a moment before trying again.</p>
        <p id="timer-text" style="font-size: 14px; margin-bottom: 20px;">
            You can try again in <span id="countdown" class="countdown">30</span> seconds.
        </p>
        <div class="actions">
            <button id="retry-btn" class="btn-primary" onclick="window.location.reload();">Try Again Now</button>
            <a href="{{ url('/login') }}" class="btn-secondary">Back to Login</a>
        </div>
    </div>
    <script>
        let timeLeft = 30;
        const countdownEl = document.getElementById('countdown');
        const timerTextEl = document.getElementById('timer-text');
        const timer = setInterval(() => {
            timeLeft--;
            if (countdownEl) countdownEl.innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                if (timerTextEl) timerTextEl.innerHTML = '<span style="color: #16a34a; font-weight: 600;">You can now try again!</span>';
            }
        }, 1000);
    </script>
</body>
</html>
