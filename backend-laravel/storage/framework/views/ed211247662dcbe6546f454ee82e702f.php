<?php $__env->startSection('title', 'Live Website Test Details'); ?>

<?php $__env->startSection('content'); ?>
    <div class="card">
        <h2><?php echo e($testRun->run_code); ?></h2>

        <p class="muted">
            Live website test run for UX friction detection.
        </p>

        <table>
            <tr>
                <th>Project</th>
                <td><?php echo e($testRun->project->name ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Target URL</th>
                <td>
                    <a href="<?php echo e($testRun->target_url); ?>" target="_blank">
                        <?php echo e($testRun->target_url); ?>

                    </a>
                </td>
            </tr>
            <tr>
                <th>Flow Type</th>
                <td><?php echo e($testRun->flow_type); ?></td>
            </tr>
            <tr>
                <th>Viewport</th>
                <td><?php echo e($testRun->viewport_type); ?></td>
            </tr>
            <tr>
                <th>Network</th>
                <td><?php echo e($testRun->network_condition); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge badge-neutral"><?php echo e($testRun->status); ?></span>
                </td>
            </tr>
            <tr>
                <th>Started At</th>
                <td><?php echo e(optional($testRun->started_at)->format('Y-m-d H:i:s') ?? 'Not started'); ?></td>
            </tr>
            <tr>
                <th>Completed At</th>
                <td><?php echo e(optional($testRun->completed_at)->format('Y-m-d H:i:s') ?? 'Not completed'); ?></td>
            </tr>
            <tr>
                <th>Duration</th>
                <td><?php echo e($testRun->duration_seconds ? number_format($testRun->duration_seconds, 2) . ' seconds' : 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Playwright Exit Code</th>
                <td><?php echo e($testRun->playwright_exit_code ?? 'N/A'); ?></td>
            </tr>
        </table>

        <br>

        <?php if($testRun->status !== 'running'): ?>
            <form method="POST" action="<?php echo e(route('live-tests.run', $testRun)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn">
                    Run Live Test
                </button>
            </form>
        <?php else: ?>
            <p class="muted">Test is currently running.</p>
        <?php endif; ?>

        <?php if($testRun->error_message): ?>
            <div class="alert-error" style="margin-top: 16px;">
                <strong>Error:</strong> <?php echo e($testRun->error_message); ?>

            </div>
        <?php endif; ?>
    </div>

    <?php if($testRun->uxMetric): ?>
        <div class="card">
            <h3>Collected UX Metrics</h3>

            <table>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>

                <?php $__currentLoopData = $testRun->uxMetric->toArray(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(!in_array($key, ['id', 'test_run_id', 'created_at', 'updated_at'])): ?>
                        <tr>
                            <td><?php echo e($key); ?></td>
                            <td>
                                <?php if(is_bool($value)): ?>
                                    <?php echo e($value ? '1' : '0'); ?>

                                <?php else: ?>
                                    <?php echo e($value); ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
        </div>
    <?php endif; ?>

    <?php if($testRun->mainGAgentResult): ?>
        <div class="card">
            <h3>Final GAgent Prediction</h3>

            <?php
                $level = $testRun->mainGAgentResult->friction_level;
                $badgeClass = match ($level) {
                    'Low' => 'badge-low',
                    'Medium' => 'badge-medium',
                    'High' => 'badge-high',
                    default => 'badge-neutral',
                };
            ?>

            <p>
                <strong>Friction Level:</strong>
                <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($level ?? 'Unknown'); ?></span>
            </p>

            <p>
                <strong>Confidence:</strong>
                <?php echo e($testRun->mainGAgentResult->confidence_score !== null
                    ? number_format($testRun->mainGAgentResult->confidence_score * 100, 1) . '%'
                    : 'N/A'); ?>

            </p>

            <?php if($testRun->mainGAgentResult->class_probabilities): ?>
                <h4>Class Probabilities</h4>
                <pre><?php echo e(json_encode($testRun->mainGAgentResult->class_probabilities, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>

            <?php if($testRun->mainGAgentResult->recommendations): ?>
                <h4>Recommendations</h4>
                <ul>
                    <?php $__currentLoopData = $testRun->mainGAgentResult->recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($recommendation); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if($testRun->report): ?>
        <div class="card">
            <h3>Generated Report</h3>

            <p>
                <strong><?php echo e($testRun->report->title); ?></strong>
            </p>

            <p><?php echo e($testRun->report->summary); ?></p>
            <p><?php echo e($testRun->report->conclusion); ?></p>

            <a href="<?php echo e(route('reports.show', $testRun->report)); ?>" class="btn">
                Open Full Report
            </a>
        </div>
    <?php endif; ?>

    <?php if($testRun->raw_metrics_path): ?>
        <div class="card">
            <h3>Raw Metrics Path</h3>
            <pre><?php echo e($testRun->raw_metrics_path); ?></pre>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/live-tests/show.blade.php ENDPATH**/ ?>