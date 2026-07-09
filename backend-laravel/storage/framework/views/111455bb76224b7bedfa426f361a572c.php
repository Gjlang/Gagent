<?php $__env->startSection('title', 'Reports'); ?>
<?php $__env->startSection('kicker', 'UX Audit Output'); ?>

<?php $__env->startSection('content'); ?>
<?php $reportCollection = $reports ?? collect(); ?>

<div class="g-page-header">
    <div>
        <h2>UX Friction Reports</h2>
        <p>Open generated AI-assisted reports with executive summaries, predictions, evidence, metrics, and recommendations.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn g-btn-primary" href="<?php echo e(route('test-runs.index')); ?>">Generate From Test Run</a>
    </div>
</div>

<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 12px;">
        <h3>Report Library</h3>
        <span class="g-badge badge-final"><?php echo e(method_exists($reportCollection, 'total') ? $reportCollection->total() : $reportCollection->count()); ?> reports</span>
    </div>

    <?php if($reportCollection->isEmpty()): ?>
        <div class="g-empty"><strong>No reports yet.</strong>Run a test and generate a report to see results here.</div>
    <?php else: ?>
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Project</th>
                        <th>Test Run</th>
                        <th>Friction Level</th>
                        <th>Confidence</th>
                        <th>Generated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $reportCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $run = $report->testRun;
                            $result = $run?->finalFrictionResult;
                            $level = $result?->friction_level ?? 'Not predicted';
                            $badgeClass = match ($level) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };
                        ?>
                        <tr>
                            <td><strong><?php echo e($report->title ?? 'Untitled Report'); ?></strong><div class="g-table-meta"><?php echo e(\Illuminate\Support\Str::limit($report->summary ?? 'No summary', 76)); ?></div></td>
                            <td><?php echo e($run?->project?->name ?? 'N/A'); ?></td>
                            <td><?php echo e($run?->run_code ?? 'N/A'); ?></td>
                            <td><span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span></td>
                            <td><?php echo e($result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A'); ?></td>
                            <td><?php echo e(optional($report->generated_at)->format('Y-m-d H:i') ?? 'N/A'); ?></td>
                            <td><a class="g-btn g-btn-primary" href="<?php echo e(route('reports.show', $report)); ?>">View</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="g-pager"><?php echo e($reportCollection->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/reports/index.blade.php ENDPATH**/ ?>