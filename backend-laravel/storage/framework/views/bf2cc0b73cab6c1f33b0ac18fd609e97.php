<?php $__env->startSection('title', 'Test Runs'); ?>
<?php $__env->startSection('kicker', 'Automation History'); ?>

<?php $__env->startSection('content'); ?>
<?php $runCollection = $testRuns ?? collect(); ?>

<div class="g-page-header">
    <div>
        <h2>Test Run Registry</h2>
        <p>Review all saved UX test executions, prediction status, platform, confidence, and generated report actions.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn g-btn-primary" href="<?php echo e(route('live-tests.create')); ?>">Run Live Test</a>
        <a class="g-btn" href="<?php echo e(route('android-tests.create')); ?>">Run Android Test</a>
    </div>
</div>

<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 12px;">
        <h3>All Test Runs</h3>
        <span class="g-badge badge-final"><?php echo e(method_exists($runCollection, 'total') ? $runCollection->total() : $runCollection->count()); ?> records</span>
    </div>

    <?php if($runCollection->isEmpty()): ?>
        <div class="g-empty"><strong>No test runs found.</strong>Run a live website test or save Android metrics first.</div>
    <?php else: ?>
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Run Code</th>
                        <th>Project</th>
                        <th>Flow / Platform</th>
                        <th>Status</th>
                        <th>Friction Level</th>
                        <th>Confidence</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $runCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $result = $run->finalFrictionResult;
                            $level = $result?->friction_level ?? 'Not predicted';
                            $badgeClass = match ($level) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };
                            $status = strtolower($run->status ?? 'unknown');
                            $statusClass = 'g-status-' . preg_replace('/[^a-z0-9]+/', '-', $status);
                        ?>
                        <tr>
                            <td><strong><?php echo e($run->run_code ?? 'N/A'); ?></strong><div class="g-table-meta"><?php echo e($run->run_mode ?? $run->scenario_type ?? 'standard'); ?></div></td>
                            <td><?php echo e($run->project?->name ?? 'N/A'); ?></td>
                            <td><?php echo e($run->flow_type ?? 'N/A'); ?><div class="g-table-meta"><?php echo e($run->platform ?? $run->viewport_type ?? 'web'); ?></div></td>
                            <td><span class="g-status-badge <?php echo e($statusClass); ?>"><?php echo e($run->status ?? 'N/A'); ?></span></td>
                            <td><span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span></td>
                            <td><?php echo e($result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A'); ?></td>
                            <td><?php echo e(optional($run->created_at)->format('Y-m-d H:i') ?? 'N/A'); ?></td>
                            <td><a class="g-btn g-btn-primary" href="<?php echo e(route('test-runs.show', $run)); ?>">Open</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="g-pager"><?php echo e($runCollection->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/test-runs/index.blade.php ENDPATH**/ ?>