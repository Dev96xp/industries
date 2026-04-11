<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Access Restricted</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #09090b;
            color: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 1rem;
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 56px;
            height: 56px;
            background: #450a0a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon svg { width: 28px; height: 28px; color: #ef4444; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { font-size: 0.875rem; color: #a1a1aa; line-height: 1.6; margin-bottom: 1rem; }
        .ip-badge {
            display: inline-block;
            background: #27272a;
            border: 1px solid #3f3f46;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            font-family: monospace;
            font-size: 0.875rem;
            color: #d4d4d8;
            margin-bottom: 1.5rem;
        }
        a {
            display: inline-block;
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 0.15s;
        }
        a:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h1>Access Restricted</h1>
        <p>Dashboard access is only allowed from authorized locations. Your current IP address is not on the allowed list.</p>
        <div class="ip-badge">{{ $clientIp }}</div>
        <br>
        <p>Please contact your administrator to request access from this location.</p>
        <br>
        <a href="{{ route('login') }}">Back to Login</a>
    </div>
</body>
</html>
