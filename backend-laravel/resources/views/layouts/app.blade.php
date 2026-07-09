<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GAgent - @yield('title', 'Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            {!! file_get_contents(resource_path('css/app.css')) !!}
        </style>
    @endif
</head>
<body>
@php
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => '▦'],
        ['label' => 'Projects', 'route' => 'projects.index', 'match' => 'projects.*', 'icon' => '▣'],
        ['label' => 'Test Runs', 'route' => 'test-runs.index', 'match' => 'test-runs.*', 'icon' => '◉'],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => '▤'],
        ['label' => 'AI Analysis', 'route' => 'ai.test', 'match' => 'ai.*', 'icon' => '✦'],
        ['label' => 'Live Website Testing', 'route' => 'live-tests.create', 'match' => 'live-tests.*', 'icon' => '◎'],
        ['label' => 'Android Testing', 'route' => 'android-tests.create', 'match' => 'android-tests.*', 'icon' => '▥'],
    ];
@endphp

<div class="g-shell">
    <aside class="g-sidebar">
        <div class="g-brand">
            <div class="g-brand-mark">G</div>
            <div>
                <div class="g-brand-title">GAgent</div>
                <div class="g-brand-subtitle">AI UX Testing</div>
            </div>
        </div>

        <nav class="g-nav" aria-label="Main navigation">
            @foreach ($navItems as $item)
                @if (\Illuminate\Support\Facades\Route::has($item['route']))
                    <a class="g-nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                        <span class="g-nav-icon">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="g-sidebar-footer">
            <div><span class="g-status-dot"></span>AI Status: Operational</div>
            <div style="margin-top: 8px;">© {{ date('Y') }} GAgent AI</div>
        </div>
    </aside>

    <main class="g-main">
        <header class="g-topbar">
            <div>
                <div class="g-page-kicker">@yield('kicker', 'GAgent System')</div>
                <h1 class="g-page-title">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="g-topbar-search" aria-label="Visual search bar">
                <span>⌕</span>
                <input type="search" placeholder="Search tests, reports, metrics, or agents..." readonly>
            </div>

            <div class="g-topbar-actions">
                <div class="g-icon-btn" title="System status">⌘</div>
                <div class="g-icon-btn" title="Notifications">●</div>
                <div class="g-avatar" title="Demo user">AI</div>
            </div>
        </header>

        <section class="g-content">
            @if (session('success'))
                <div class="g-alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="g-alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </section>

        <footer class="g-footer">
            <span>Documentation · Support · Privacy</span>
            <span><span class="g-status-dot"></span>AI Status: Operational</span>
        </footer>
    </main>
</div>

@stack('scripts')
</body>
</html>
