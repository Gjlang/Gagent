<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        GAgent - @yield('title', 'Authentication')
    </title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    @if (
        file_exists(public_path('build/manifest.json'))
        || file_exists(public_path('hot'))
    )
        @vite([
            'resources/css/app.css',
            'resources/js/app.js'
        ])
    @else
        <style>
            {!! file_get_contents(
                resource_path('css/app.css')
            ) !!}
        </style>
    @endif

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(37, 99, 235, 0.18),
                    transparent 35%
                ),
                #07101f;
        }

        .g-auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .g-auth-container {
            width: 100%;
            max-width: 460px;
        }

        .g-auth-brand {
            margin-bottom: 24px;
            text-align: center;
        }

        .g-auth-logo {
            width: 62px;
            height: 62px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #2563eb;
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
        }

        .g-auth-brand h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
        }

        .g-auth-brand p {
            margin: 7px 0 0;
            color: #94a3b8;
        }

        .g-auth-card {
            padding: 28px;
            border: 1px solid rgba(
                148,
                163,
                184,
                0.25
            );
            border-radius: 20px;
            background: rgba(
                15,
                23,
                42,
                0.96
            );
            box-shadow:
                0 24px 70px rgba(
                    0,
                    0,
                    0,
                    0.35
                );
        }

        .g-auth-card h2 {
            margin: 0 0 8px;
            color: #ffffff;
        }

        .g-auth-description {
            margin: 0 0 22px;
            color: #94a3b8;
        }

        .g-auth-field {
            margin-bottom: 17px;
        }

        .g-auth-field label {
            display: block;
            margin-bottom: 7px;
            color: #dbeafe;
            font-size: 14px;
            font-weight: 600;
        }

        .g-auth-field input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #334155;
            border-radius: 10px;
            background: #0f172a;
            color: #ffffff;
            font: inherit;
            box-sizing: border-box;
        }

        .g-auth-field input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow:
                0 0 0 3px rgba(
                    59,
                    130,
                    246,
                    0.18
                );
        }

        .g-auth-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0 18px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .g-auth-button {
            width: 100%;
            padding: 12px 16px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .g-auth-button:hover {
            background: #1d4ed8;
        }

        .g-auth-footer {
            margin-top: 20px;
            color: #94a3b8;
            text-align: center;
        }

        .g-auth-footer a {
            color: #60a5fa;
            text-decoration: none;
        }

        .g-auth-error {
            margin-top: 6px;
            color: #fca5a5;
            font-size: 13px;
        }

        .g-auth-alert {
            margin-bottom: 18px;
            padding: 11px 13px;
            border: 1px solid #166534;
            border-radius: 9px;
            background: rgba(
                22,
                101,
                52,
                0.18
            );
            color: #bbf7d0;
        }
    </style>
</head>

<body>
<div class="g-auth-page">
    <div class="g-auth-container">
        <div class="g-auth-brand">
            <div class="g-auth-logo">G</div>

            <h1>GAgent</h1>

            <p>
                Autonomous AI-Driven UX Mystery Shopper
            </p>
        </div>

        <main class="g-auth-card">
            @if (session('success'))
                <div class="g-auth-alert">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
