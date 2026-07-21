<?php $__env->startSection('title', 'Projects'); ?>
<?php $__env->startSection('kicker', 'Project Suites'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $projectCollection = $projects ?? collect();
?>

<div class="g-page-header">
    <div>
        <h2>Active Projects</h2>
        <p>Manage and configure automated UX test suites for dummy websites, live web applications, and Android applications.</p>
    </div>
    <div class="g-actions">
        
    </div>
</div>

<div class="g-layout-2-1">
    <div class="g-stack">
        <div class="g-card">
            <div class="g-split-row" style="margin-bottom: 12px;">
                <h3>Project Registry</h3>
                <span class="g-badge badge-final"><?php echo e(method_exists($projectCollection, 'total') ? $projectCollection->total() : $projectCollection->count()); ?> total</span>
            </div>

            <?php if($projectCollection->isEmpty()): ?>
                <div class="g-empty"><strong>No projects found.</strong>Create a project before running live or Android tests.</div>
            <?php else: ?>
                <div class="g-table-wrap">
                    <table class="g-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>URL / Target</th>
                                <th>Status</th>
                                <th>Test Runs</th>
                                <th>Target Type</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $projectCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $status = strtolower($project->status ?? 'unknown');
                                    $statusClass = 'g-status-' . preg_replace('/[^a-z0-9]+/', '-', $status);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($project->name ?? 'Untitled Project'); ?></strong>
                                        <div class="g-table-meta"><?php echo e($project->description ? \Illuminate\Support\Str::limit($project->description, 70) : 'No description provided'); ?></div>
                                    </td>
                                    <td><?php echo e($project->target_url ?? 'N/A'); ?></td>
                                    <td><span class="g-status-badge <?php echo e($statusClass); ?>"><?php echo e($project->status ?? 'N/A'); ?></span></td>
                                    <td><?php echo e($project->test_runs_count ?? $project->testRuns?->count() ?? 0); ?></td>
                                    <td><?php echo e($project->target_type ?? 'N/A'); ?></td>
                                    <td><a class="g-btn g-btn-primary" href="<?php echo e(route('projects.show', $project)); ?>">Open</a></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="g-pager"><?php echo e($projectCollection->links()); ?></div>
            <?php endif; ?>
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-insight-card">
                <div class="g-soft-label">Predictive Analysis</div>
                <h3 style="margin-top: 7px;">Project coverage view</h3>
                <p class="g-muted">Use this page to enter project detail pages, review linked runs, and launch the correct testing flow from the project context.</p>
            </div>
            <div class="g-insight-card" style="border-left: 4px solid var(--g-red);">
                <div class="g-soft-label">Critical Friction Watch</div>
                <h3 style="margin-top: 7px;">Review high-risk projects</h3>
                <p class="g-muted">Projects with repeated failed tests or High predictions should be prioritised in the report page.</p>
            </div>
        </div>
    </div>

    <aside class="g-panel">
        <div class="g-soft-label">Test Configuration</div>
        <h3 style="margin-top: 7px;">Queue AI Simulation</h3>
        <div class="g-kv" style="margin-top: 14px;">
            <div class="g-kv-row"><span>Target Environment</span><span>Staging / Production</span></div>
            <div class="g-kv-row"><span>Web Runner</span><span>Playwright</span></div>
            <div class="g-kv-row"><span>Android Runner</span><span>Appium</span></div>
            <div class="g-kv-row"><span>Model Output</span><span>Low / Medium / High</span></div>
        </div>
        <div style="margin-top: 16px; display: grid; gap: 10px;">
            
            <a class="g-btn g-btn-block" href="<?php echo e(route('android-tests.create')); ?>">Run Android Test</a>
        </div>
        <p class="g-muted g-small" style="margin-top: 14px;">These buttons use existing Laravel routes. No backend logic is changed.</p>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/projects/index.blade.php ENDPATH**/ ?>