<?php $__env->startSection('title', 'Android Test Result'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $metric = $testRun->uxMetric;
    $android = $testRun->androidResult;
?>

<div class="g-page-header">
    <div>
        <div class="g-soft-label">
            Android Automation Run
        </div>

        <h2><?php echo e($testRun->run_code); ?></h2>

        <p>
            Review Android Appium metrics and
            AI friction prediction.
        </p>
    </div>

    <div class="g-actions">
        <a
            class="g-btn"
            href="<?php echo e(route('android-tests.create')); ?>"
        >
            New Android Test
        </a>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="g-alert g-alert-success">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="g-alert g-alert-error">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if($testRun->error_message): ?>
    <div class="g-alert g-alert-error">
        <strong>Last error:</strong>
        <?php echo e($testRun->error_message); ?>

    </div>
<?php endif; ?>

<div class="g-card">
    <h3>Test Configuration</h3>

    <div class="g-kv">
        <div class="g-kv-row">
            <span>Status</span>
            <span><?php echo e(strtoupper($testRun->status)); ?></span>
        </div>

        <div class="g-kv-row">
            <span>Test Mode</span>
            <span><?php echo e($testRun->test_mode ?? '-'); ?></span>
        </div>

        <div class="g-kv-row">
            <span>Flow</span>
            <span><?php echo e($testRun->flow_type); ?></span>
        </div>

        <div class="g-kv-row">
            <span>Device</span>
            <span><?php echo e($testRun->device_name); ?></span>
        </div>

        <div class="g-kv-row">
            <span>Package</span>
            <span>
                <?php echo e($testRun->target_app_package ?? '-'); ?>

            </span>
        </div>

        <div class="g-kv-row">
            <span>Activity</span>
            <span>
                <?php echo e($testRun->target_app_activity ?? '-'); ?>

            </span>
        </div>

        <div class="g-kv-row">
            <span>APK</span>
            <span><?php echo e($testRun->apk_path ?? '-'); ?></span>
        </div>

        <div class="g-kv-row">
            <span>Maximum Duration</span>
            <span>
                <?php echo e($testRun->max_duration_seconds); ?> seconds
            </span>
        </div>
    </div>

    <?php if(
        in_array(
            $testRun->status,
            ['pending', 'failed', 'completed'],
            true
        )
    ): ?>
        <form
            method="POST"
            action="<?php echo e(route(
                'android-tests.run',
                $testRun
            )); ?>"
            style="margin-top: 18px;"
        >
            <?php echo csrf_field(); ?>

            <button
                class="g-btn g-btn-primary"
                type="submit"
            >
                <?php echo e($testRun->status === 'completed'
                    ? 'Run Android Test Again'
                    : 'Run Android Appium Test'); ?>

            </button>
        </form>
    <?php elseif($testRun->status === 'running'): ?>
        <div
            class="g-alert"
            style="margin-top: 18px;"
        >
            Android Appium test is currently running.
        </div>
    <?php endif; ?>
</div>

<div class="g-grid g-grid-2">
    <div class="g-card">
        <h3>Collected Android Metrics</h3>

        <?php if(!$metric): ?>
            <p class="muted">
                No Android metrics have been collected yet.
            </p>
        <?php else: ?>
            <table>
                <tbody>
                    <?php $__currentLoopData = [
                        'task_completed' => 'Task Completed',
                        'task_failed' => 'Task Failed',
                        'completion_time' => 'Completion Time',
                        'click_count' => 'Click Count',
                        'scroll_count' => 'Scroll Count',
                        'keyboard_count' => 'Keyboard Count',
                        'retry_count' => 'Retry Count',
                        'error_count' => 'Error Count',
                        'failed_clicks' => 'Failed Clicks',
                        'unnecessary_clicks' => 'Unnecessary Clicks',
                        'path_deviation_score' => 'Path Deviation Score',
                        'app_launch_time_ms' => 'App Launch Time MS',
                        'screen_load_time_ms' => 'Screen Load Time MS',
                        'feedback_delay_ms' => 'Feedback Delay MS',
                        'interaction_response_time_ms' => 'Interaction Response Time MS',
                        'finish_time_ms' => 'Finish Time MS',
                        'error_message_present' => 'Error Message Present',
                        'error_message_clarity' => 'Error Message Clarity',
                        'popup_detected' => 'Popup Detected',
                        'overlay_blocks_action' => 'Overlay Blocks Action',
                        'timeout_occurred' => 'Timeout Occurred',
                        'crash_detected' => 'Crash Detected',
                        'anr_detected' => 'ANR Detected',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <th><?php echo e($label); ?></th>
                            <td><?php echo e($metric->{$field}); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="g-card">
        <h3>Android AI Prediction</h3>

        <?php if(!$android): ?>
            <p class="muted">
                No Android prediction has been saved.
            </p>
        <?php else: ?>
            <p>
                <strong>Friction Level:</strong>

                <span class="g-badge">
                    <?php echo e($android->friction_level); ?>

                </span>
            </p>

            <p>
                <strong>Confidence:</strong>

                <?php echo e(number_format(
                    ($android->confidence_score ?? 0) * 100,
                    1
                )); ?>%
            </p>

            <p>
                <strong>Model:</strong>
                <?php echo e($android->model_name); ?>

            </p>

            <h4>Class Probabilities</h4>

            <pre><?php echo e(json_encode(
                $android->class_probabilities,
                JSON_PRETTY_PRINT
            )); ?></pre>

            <h4>Recommendations</h4>

            <ul>
                <?php $__empty_1 = true; $__currentLoopData = ($android->recommendations ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li><?php echo e($recommendation); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li>No recommendations returned.</li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="g-card">
    <h3>Android Testing Limitation</h3>

    <p class="muted">
        Real Android app testing uses generic Appium
        exploration. It supports simple flows only and
        does not bypass authentication, CAPTCHA, payment,
        two-factor authentication, or private application
        protections. The controlled dummy Android app
        remains the main reliable test target for the FYP.
    </p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/android-tests/show.blade.php ENDPATH**/ ?>