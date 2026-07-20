<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GAgent - <?php echo $__env->yieldContent('title', 'Dashboard'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php else: ?>
        <style>
            <?php echo file_get_contents(resource_path('css/app.css')); ?>

        </style>
    <?php endif; ?>
</head>
<body>
<?php
    // Icon key -> inline SVG path data. Kept separate from $navItems so the
    // existing route/label/match config below is untouched.
    $navIcons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',
        'website' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 4 6 4 9s-1.5 6.4-4 9c-2.5-2.6-4-6-4-9s1.5-6.4 4-9z"/>',
        'android' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
        'projects' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/>',
        'test-runs' => '<circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/>',
        'reports' => '<path d="M6 2h9l3 3v17H6z"/><path d="M15 2v3h3"/><path d="M9 12h6M9 16h6M9 8h2"/>',
    ];

    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'dashboard'],
        ['label' => 'Website Testing', 'route' => 'unified-tests.create', 'match' => 'unified-tests.*', 'icon' => 'website'],
        ['label' => 'Android Testing', 'route' => 'android-tests.create', 'match' => 'android-tests.*', 'icon' => 'android'],
        ['label' => 'Projects', 'route' => 'projects.index', 'match' => 'projects.*', 'icon' => 'projects'],
        ['label' => 'Test Runs', 'route' => 'test-runs.index', 'match' => 'test-runs.*', 'icon' => 'test-runs'],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'reports'],
    ];
?>

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
            <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(\Illuminate\Support\Facades\Route::has($item['route'])): ?>
                    <a class="g-nav-link <?php echo e(request()->routeIs($item['match']) ? 'is-active' : ''); ?>" href="<?php echo e(route($item['route'])); ?>">
                        <span class="g-nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php echo $navIcons[$item['icon']] ?? ''; ?>

                            </svg>
                        </span>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
    </aside>

    <main class="g-main">
        <header class="g-topbar">
            <div>
                <div class="g-page-kicker"><?php echo $__env->yieldContent('kicker', 'GAgent System'); ?></div>
                <h1 class="g-page-title"><?php echo $__env->yieldContent('title', 'Dashboard'); ?></h1>
            </div>

            <div class="g-topbar-actions" style="margin-left: auto;">
                <div class="g-icon-btn" title="System status">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2L4 14h7l-1 8 9-12h-7z"/>
                    </svg>
                </div>
                <div class="g-icon-btn" title="Notifications">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                    </svg>
                </div>
                <div class="g-avatar" title="Demo user">AI</div>
            </div>
        </header>

        <section class="g-content">
            <?php if(session('success')): ?>
                <div class="g-alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="g-alert-error"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </section>

        <footer class="g-footer">
            <span>Documentation · Support · Privacy</span>
            <span><span class="g-status-dot"></span>AI Status: Operational</span>
        </footer>
    </main>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/layouts/app.blade.php ENDPATH**/ ?>