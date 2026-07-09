<?php $__env->startSection('title', 'Android Test Result'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $metric = $testRun->uxMetric;
    $android = $testRun->androidResult;
?>

<div class="card">
    <h3>Android Test Run</h3>

    <p><strong>Run Code:</strong> <?php echo e($testRun->run_code); ?></p>
    <p><strong>Project:</strong> <?php echo e($testRun->project->name ?? '-'); ?></p>
    <p><strong>Status:</strong> <?php echo e($testRun->status); ?></p>
    <p><strong>Flow Type:</strong> <?php echo e($testRun->flow_type); ?></p>
    <p><strong>Platform:</strong> <?php echo e($testRun->platform); ?></p>
    <p><strong>Automation Driver:</strong> <?php echo e($testRun->automation_driver); ?></p>
    <p><strong>Device:</strong> <?php echo e($testRun->device_name); ?></p>
    <p><strong>App Package:</strong> <?php echo e($testRun->target_app_package); ?></p>
    <p><strong>App Activity:</strong> <?php echo e($testRun->target_app_activity); ?></p>

    <?php if(!$android): ?>
        <form method="POST" action="<?php echo e(route('android-tests.predict', $testRun)); ?>">
            <?php echo csrf_field(); ?>
            <button class="btn" type="submit">Run Android Prediction</button>
        </form>
    <?php else: ?>
        <span class="badge badge-final">Android prediction saved</span>
    <?php endif; ?>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Collected Android Metrics</h3>

        <?php if(!$metric): ?>
            <p class="muted">No Android metrics found.</p>
        <?php else: ?>
            <table>
                <tbody>
                    <tr><th>Task Completed</th><td><?php echo e((int) $metric->task_completed); ?></td></tr>
                    <tr><th>Task Failed</th><td><?php echo e((int) $metric->task_failed); ?></td></tr>
                    <tr><th>Completion Time</th><td><?php echo e($metric->completion_time); ?></td></tr>
                    <tr><th>Click Count</th><td><?php echo e($metric->click_count); ?></td></tr>
                    <tr><th>Scroll Count</th><td><?php echo e($metric->scroll_count); ?></td></tr>
                    <tr><th>Keyboard Count</th><td><?php echo e($metric->keyboard_count); ?></td></tr>
                    <tr><th>Retry Count</th><td><?php echo e($metric->retry_count); ?></td></tr>
                    <tr><th>Error Count</th><td><?php echo e($metric->error_count); ?></td></tr>
                    <tr><th>Failed Clicks</th><td><?php echo e($metric->failed_clicks); ?></td></tr>
                    <tr><th>Unnecessary Clicks</th><td><?php echo e($metric->unnecessary_clicks); ?></td></tr>
                    <tr><th>Path Deviation Score</th><td><?php echo e($metric->path_deviation_score); ?></td></tr>
                    <tr><th>App Launch Time MS</th><td><?php echo e($metric->app_launch_time_ms); ?></td></tr>
                    <tr><th>Screen Load Time MS</th><td><?php echo e($metric->screen_load_time_ms); ?></td></tr>
                    <tr><th>Feedback Delay MS</th><td><?php echo e($metric->feedback_delay_ms); ?></td></tr>
                    <tr><th>Interaction Response Time MS</th><td><?php echo e($metric->interaction_response_time_ms); ?></td></tr>
                    <tr><th>Finish Time MS</th><td><?php echo e($metric->finish_time_ms); ?></td></tr>
                    <tr><th>Error Message Present</th><td><?php echo e((int) $metric->error_message_present); ?></td></tr>
                    <tr><th>Error Message Clarity</th><td><?php echo e($metric->error_message_clarity); ?></td></tr>
                    <tr><th>Popup Detected</th><td><?php echo e((int) $metric->popup_detected); ?></td></tr>
                    <tr><th>Overlay Blocks Action</th><td><?php echo e((int) $metric->overlay_blocks_action); ?></td></tr>
                    <tr><th>Timeout Occurred</th><td><?php echo e((int) $metric->timeout_occurred); ?></td></tr>
                    <tr><th>Crash Detected</th><td><?php echo e((int) $metric->crash_detected); ?></td></tr>
                    <tr><th>ANR Detected</th><td><?php echo e((int) $metric->anr_detected); ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Android Prediction Result</h3>

        <?php if(!$android): ?>
            <p class="muted">No Android prediction saved yet.</p>
        <?php else: ?>
            <p>
                <strong>Friction Level:</strong>
                <span class="badge badge-<?php echo e(strtolower($android->friction_level)); ?>">
                    <?php echo e($android->friction_level); ?>

                </span>
            </p>

            <p><strong>Model:</strong> <?php echo e($android->model_name); ?></p>
            <p><strong>Model Type:</strong> <?php echo e($android->model_type); ?></p>
            <p><strong>Confidence:</strong> <?php echo e(number_format(($android->confidence_score ?? 0) * 100, 1)); ?>%</p>

            <h4>Class Probabilities</h4>
            <pre><?php echo e(json_encode($android->class_probabilities, JSON_PRETTY_PRINT)); ?></pre>

            <h4>Recommendations</h4>
            <ul>
                <?php $__currentLoopData = ($android->recommendations ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($recommendation); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>

            <h4>Input Features Sent to FastAPI</h4>
            <pre><?php echo e(json_encode($android->input_features, JSON_PRETTY_PRINT)); ?></pre>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Phase 8 Scope Note</h3>
    <p class="muted">
        This Android module is an experimental extension of GAgent. The Web GAgent model remains the main model.
        The Android result is based on controlled Android UX metrics and should not be overclaimed as full real-world Android generalization.
    </p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/android-tests/show.blade.php ENDPATH**/ ?>