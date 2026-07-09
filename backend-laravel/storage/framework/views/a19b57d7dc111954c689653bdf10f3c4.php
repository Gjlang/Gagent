<?php $__env->startSection('title', 'Test Run Detail'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $metric = $testRun->uxMetric;
    $final = $testRun->finalFrictionResult;
    $main = $testRun->mainGAgentResult;
    $baseline = $testRun->baselineResult;
?>

<div class="card">
    <h2><?php echo e($testRun->run_code); ?></h2>
    <p><strong>Project:</strong> <?php echo e($testRun->project?->name ?? 'N/A'); ?></p>
    <p><strong>Flow:</strong> <?php echo e($testRun->flow_type ?? 'N/A'); ?></p>
    <p><strong>Scenario:</strong> <?php echo e($testRun->scenario_type ?? 'N/A'); ?></p>
    <p><strong>Viewport:</strong> <?php echo e($testRun->viewport_type ?? 'N/A'); ?></p>
    <p><strong>Page URL:</strong> <?php echo e($testRun->page_url ?? 'N/A'); ?></p>
    <p><strong>Status:</strong> <?php echo e($testRun->status); ?></p>

    <form method="POST" action="<?php echo e(route('test-runs.predict-gagent', $testRun)); ?>" style="display:inline-block;">
        <?php echo csrf_field(); ?>
        <button class="btn" type="submit">Run Main GAgent Prediction</button>
    </form>

    <form method="POST" action="<?php echo e(route('test-runs.predict-baseline', $testRun)); ?>" style="display:inline-block;">
        <?php echo csrf_field(); ?>
        <button class="btn btn-secondary" type="submit">Run Baseline Prediction</button>
    </form>

    <form method="POST" action="<?php echo e(route('reports.generate', $testRun)); ?>" style="display:inline-block;">
        <?php echo csrf_field(); ?>
        <button class="btn btn-secondary" type="submit">Generate Report</button>
    </form>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="muted">Final GAgent Result</div>
        <?php
            $level = $final?->friction_level ?? 'Not predicted';
            $badgeClass = match ($level) {
                'Low' => 'badge-low',
                'Medium' => 'badge-medium',
                'High' => 'badge-high',
                default => 'badge-neutral',
            };
        ?>
        <p><span class="badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span></p>
        <p class="muted">Main GAgent result is the final system decision.</p>
    </div>

    <div class="card">
        <div class="muted">Final Confidence</div>
        <div class="stat-value">
            <?php echo e($final?->confidence_score !== null ? number_format($final->confidence_score * 100, 1) . '%' : 'N/A'); ?>

        </div>
    </div>

    <div class="card">
        <div class="muted">Baseline Comparison</div>
        <div class="stat-value" style="font-size:22px;">
            <?php echo e($baseline?->friction_level ?? 'N/A'); ?>

        </div>
        <p class="muted">Baseline is not the final decision.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>UX Metrics</h3>

        <?php if(!$metric): ?>
            <p class="muted">No UX metrics found.</p>
        <?php else: ?>
            <table>
                <tbody>
                    <tr><th>Task Completed</th><td><?php echo e($metric->task_completed ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th>Task Failed</th><td><?php echo e($metric->task_failed ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th>Completion Time</th><td><?php echo e($metric->completion_time); ?></td></tr>
                    <tr><th>Click Count</th><td><?php echo e($metric->click_count); ?></td></tr>
                    <tr><th>Scroll Count</th><td><?php echo e($metric->scroll_count); ?></td></tr>
                    <tr><th>Keyboard Count</th><td><?php echo e($metric->keyboard_count); ?></td></tr>
                    <tr><th>Retry Count</th><td><?php echo e($metric->retry_count); ?></td></tr>
                    <tr><th>Error Count</th><td><?php echo e($metric->error_count); ?></td></tr>
                    <tr><th>Failed Clicks</th><td><?php echo e($metric->failed_clicks); ?></td></tr>
                    <tr><th>Unnecessary Clicks</th><td><?php echo e($metric->unnecessary_clicks); ?></td></tr>
                    <tr><th>Path Deviation Score</th><td><?php echo e($metric->path_deviation_score); ?></td></tr>
                    <tr><th>Page Load Time</th><td><?php echo e($metric->page_load_time_ms); ?> ms</td></tr>
                    <tr><th>DOM Content Loaded</th><td><?php echo e($metric->dom_content_loaded_ms); ?> ms</td></tr>
                    <tr><th>TTFB</th><td><?php echo e($metric->time_to_first_byte_ms); ?> ms</td></tr>
                    <tr><th>Feedback Delay</th><td><?php echo e($metric->feedback_delay_ms); ?> ms</td></tr>
                    <tr><th>INP</th><td><?php echo e($metric->interaction_to_next_paint_ms); ?> ms</td></tr>
                    <tr><th>CLS</th><td><?php echo e($metric->cumulative_layout_shift); ?></td></tr>
                    <tr><th>Error Message Present</th><td><?php echo e($metric->error_message_present ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th>Error Message Clarity</th><td><?php echo e($metric->error_message_clarity); ?></td></tr>
                    <tr><th>Popup Detected</th><td><?php echo e($metric->popup_detected ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th>Cookie Banner Detected</th><td><?php echo e($metric->cookie_banner_detected ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th>Overlay Blocks CTA</th><td><?php echo e($metric->overlay_blocks_cta ? 'Yes' : 'No'); ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Metric Evidence Chart</h3>
        <?php if($metric): ?>
            <canvas id="metricChart"></canvas>
        <?php else: ?>
            <p class="muted">No chart available.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Main GAgent Result</h3>
        <?php if(!$main): ?>
            <p class="muted">No main GAgent result saved yet.</p>
        <?php else: ?>
            <p><strong>Model:</strong> <?php echo e($main->model_name); ?> / <?php echo e($main->model_type); ?></p>
            <p><strong>Friction:</strong> <?php echo e($main->friction_level); ?> <span class="badge badge-final">Final</span></p>
            <p><strong>Confidence:</strong> <?php echo e(number_format(($main->confidence_score ?? 0) * 100, 1)); ?>%</p>
            <h4>Class Probabilities</h4>
            <pre><?php echo e(json_encode($main->class_probabilities, JSON_PRETTY_PRINT)); ?></pre>
            <h4>Recommendations</h4>
            <ul>
                <?php $__currentLoopData = ($main->recommendations ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($recommendation); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Baseline Result</h3>
        <?php if(!$baseline): ?>
            <p class="muted">No baseline result saved yet.</p>
        <?php else: ?>
            <p><strong>Model:</strong> <?php echo e($baseline->model_name); ?> / <?php echo e($baseline->model_type); ?></p>
            <p><strong>Friction:</strong> <?php echo e($baseline->friction_level); ?></p>
            <p><strong>Confidence:</strong> <?php echo e(number_format(($baseline->confidence_score ?? 0) * 100, 1)); ?>%</p>
            <h4>Class Probabilities</h4>
            <pre><?php echo e(json_encode($baseline->class_probabilities, JSON_PRETTY_PRINT)); ?></pre>
            <p class="muted">Baseline is stored only for comparison.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Interaction Logs</h3>
    <?php if($testRun->interactionLogs->isEmpty()): ?>
        <p class="muted">No interaction logs available.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Event Type</th>
                    <th>Label</th>
                    <th>Value</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $testRun->interactionLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($log->event_type); ?></td>
                        <td><?php echo e($log->event_label); ?></td>
                        <td><?php echo e($log->event_value); ?></td>
                        <td><?php echo e($log->event_time); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Screenshot Evidence</h3>
    <?php if($testRun->screenshots->isEmpty()): ?>
        <p class="muted">No screenshots available.</p>
    <?php else: ?>
        <div class="grid grid-3">
            <?php $__currentLoopData = $testRun->screenshots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screenshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="screenshot-box">
                    <strong><?php echo e($screenshot->label); ?></strong>
                    <p class="muted"><?php echo e($screenshot->file_path); ?></p>
                    <?php if(str_starts_with($screenshot->file_path, 'http')): ?>
                        <img src="<?php echo e($screenshot->file_path); ?>" alt="<?php echo e($screenshot->label); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php if($metric): ?>
<?php $__env->startPush('scripts'); ?>
<script>
    new Chart(document.getElementById('metricChart'), {
        type: 'bar',
        data: {
            labels: ['Clicks', 'Retries', 'Errors', 'Failed Clicks', 'Unnecessary Clicks', 'Feedback Delay'],
            datasets: [{
                label: 'Metric Value',
                data: [
                    <?php echo e($metric->click_count); ?>,
                    <?php echo e($metric->retry_count); ?>,
                    <?php echo e($metric->error_count); ?>,
                    <?php echo e($metric->failed_clicks); ?>,
                    <?php echo e($metric->unnecessary_clicks); ?>,
                    <?php echo e($metric->feedback_delay_ms); ?>

                ]
            }]
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/test-runs/show.blade.php ENDPATH**/ ?>