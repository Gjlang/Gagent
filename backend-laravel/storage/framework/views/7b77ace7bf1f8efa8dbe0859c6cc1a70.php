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

            .g-user-menu-wrapper {
    position: relative;
}

.g-user-menu-trigger {
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.g-user-menu-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    z-index: 1000;
    width: 240px;
    overflow: hidden;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
}

.g-user-menu-header {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 16px;
}

.g-user-menu-header strong {
    color: #111827;
    font-size: 14px;
}

.g-user-menu-header span {
    overflow: hidden;
    color: #6b7280;
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.g-user-menu-divider {
    height: 1px;
    background: #e5e7eb;
}

.g-user-menu-item {
    display: block;
    width: 100%;
    padding: 12px 16px;
    border: 0;
    background: transparent;
    color: #334155;
    font: inherit;
    font-size: 14px;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.g-user-menu-item:hover {
    background: #f8fafc;
}

.g-user-menu-logout {
    color: #b91c1c;
}

.g-user-menu-logout:hover {
    background: #fef2f2;
}
        </style>
    <?php endif; ?>
</head>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById(
        'user-menu-trigger'
    );

    const dropdown = document.getElementById(
        'user-menu-dropdown'
    );

    if (!trigger || !dropdown) {
        return;
    }

    trigger.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = !dropdown.hasAttribute('hidden');

        if (isOpen) {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        } else {
            dropdown.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
        }
    });

    dropdown.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        dropdown.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
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

    'comparisons' => '<path d="M7 7h11"/><path d="M15 4l3 3-3 3"/><path d="M17 17H6"/><path d="M9 14l-3 3 3 3"/>',
];

   $navItems = [
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'match' => 'dashboard',
        'icon' => 'dashboard',
    ],

    [
        'label' => 'Website Testing',
        'route' => 'unified-tests.create',
        'match' => 'unified-tests.*',
        'icon' => 'website',
    ],

    [
        'label' => 'Android Testing',
        'route' => 'android-tests.create',
        'match' => 'android-tests.*',
        'icon' => 'android',
    ],

    [
        'label' => 'Projects',
        'route' => 'projects.index',
        'match' => 'projects.*',
        'icon' => 'projects',
    ],

    [
        'label' => 'Test Runs',
        'route' => 'test-runs.index',
        'match' => 'test-runs.*',
        'icon' => 'test-runs',
    ],

    [
        'label' => 'Reports',
        'route' => 'reports.index',
        'match' => 'reports.*',
        'icon' => 'reports',
    ],

    [
        'label' => 'Comparisons',
        'route' => 'comparisons.index',
        'match' => 'comparisons.*',
        'icon' => 'comparisons',
    ],
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

            <div class="g-topbar-actions">
    <div
        class="g-icon-btn"
        title="System status"
    >
        ⚡
    </div>

    <div
        class="g-icon-btn"
        title="Notifications"
    >
        🔔
    </div>

    <div class="g-user-menu-wrapper">
        <button
            type="button"
            class="g-user-menu-trigger"
            id="user-menu-trigger"
            aria-expanded="false"
            aria-controls="user-menu-dropdown"
        >
            <div class="g-avatar">
                <?php echo e(strtoupper(
                        substr(
                            auth()->user()->name,
                            0,
                            2
                        )
                    )); ?>

            </div>
        </button>

        <div
            class="g-user-menu-dropdown"
            id="user-menu-dropdown"
            hidden
        >
            <div class="g-user-menu-header">
                <strong>
                    <?php echo e(auth()->user()->name); ?>

                </strong>

                <span>
                    <?php echo e(auth()->user()->email); ?>

                </span>
            </div>

            <div class="g-user-menu-divider"></div>

            <a
                href="<?php echo e(route('profile.show')); ?>"
                class="g-user-menu-item"
            >
                My Profile
            </a>

            <form
                method="POST"
                action="<?php echo e(route('logout')); ?>"
            >
                <?php echo csrf_field(); ?>

                <button
                    type="submit"
                    class="g-user-menu-item g-user-menu-logout"
                >
                    Logout
                </button>
            </form>
        </div>
    </div>
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