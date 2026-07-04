<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GAgent - @yield('title', 'Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .app { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: #111827;
            color: white;
            padding: 20px;
        }
        .sidebar h2 { margin: 0 0 8px 0; }
        .sidebar p {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.4;
        }
        .sidebar a {
            display: block;
            color: #d1d5db;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 8px;
            margin: 6px 0;
            font-size: 14px;
        }
        .sidebar a:hover { background: #374151; color: white; }
        .main { flex: 1; }
        .topbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
        }
        .topbar h1 { margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 18px;
        }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .stat-value {
            font-size: 30px;
            font-weight: bold;
            margin-top: 8px;
        }
        .muted { color: #6b7280; font-size: 14px; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 11px;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }
        th { background: #f9fafb; }
        .btn {
            display: inline-block;
            padding: 9px 12px;
            border-radius: 8px;
            border: none;
            background: #2563eb;
            color: white;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            margin: 2px;
        }
        .btn-secondary { background: #4b5563; }
        .btn-danger { background: #dc2626; }
        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-low { background: #dcfce7; color: #166534; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #e5e7eb; color: #374151; }
        .badge-final { background: #dbeafe; color: #1d4ed8; }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }
        label {
            font-weight: bold;
            font-size: 14px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        pre {
            white-space: pre-wrap;
            background: #111827;
            color: #f9fafb;
            padding: 14px;
            border-radius: 8px;
            font-size: 13px;
            overflow-x: auto;
        }
        .screenshot-box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            background: #f9fafb;
        }
        .screenshot-box img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        @media (max-width: 900px) {
            .app { display: block; }
            .sidebar { width: 100%; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .content { padding: 14px; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <h2>GAgent</h2>
        <p>Autonomous AI-driven mystery shopper dashboard for UX friction detection.</p>

        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('projects.index') }}">Projects</a>
        <a href="{{ route('projects.create') }}">Create Project</a>
        <a href="{{ route('test-runs.index') }}">Test Runs</a>
        <a href="{{ route('reports.index') }}">Reports</a>
        <a href="{{ route('ai.test') }}">AI Service Test</a>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>@yield('title', 'Dashboard')</h1>
        </div>

        <div class="content">
            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
