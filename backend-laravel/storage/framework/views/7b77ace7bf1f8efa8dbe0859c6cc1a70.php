<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GAgent - <?php echo $__env->yieldContent('title', 'Dashboard'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

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
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => '▦'],
        ['label' => 'Website Testing', 'route' => 'unified-tests.create', 'match' => 'unified-tests.*', 'icon' => '▶'],
        ['label' => 'Android Testing', 'route' => 'android-tests.create', 'match' => 'android-tests.*', 'icon' => '◫'],
        ['label' => 'Projects', 'route' => 'projects.index', 'match' => 'projects.*', 'icon' => '▣'],
        ['label' => 'Test Runs', 'route' => 'test-runs.index', 'match' => 'test-runs.*', 'icon' => '◉'],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => '▤'],
        ['label' => 'AI Analysis', 'route' => 'ai.test', 'match' => 'ai.*', 'icon' => '✦'],
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
                        <span class="g-nav-icon"><?php echo e($item['icon']); ?></span>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>

        <div class="g-sidebar-footer">
            <div><span class="g-status-dot"></span>AI Status: Operational</div>
            <div style="margin-top: 8px;">© <?php echo e(date('Y')); ?> GAgent AI</div>
        </div>
    </aside>

    <main class="g-main">
        <header class="g-topbar">
            <div>
                <div class="g-page-kicker"><?php echo $__env->yieldContent('kicker', 'GAgent System'); ?></div>
                <h1 class="g-page-title"><?php echo $__env->yieldContent('title', 'Dashboard'); ?></h1>
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