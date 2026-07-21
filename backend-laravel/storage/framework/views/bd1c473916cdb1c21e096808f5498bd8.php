<?php $__env->startSection('title', 'Run Website UX Test'); ?>
<?php $__env->startSection('kicker', 'Playwright Website Testing'); ?>


<?php $__env->startSection('content'); ?>
<?php
    $comparisonMode = $comparisonMode ?? false;
    $comparisonProject = $comparisonProject ?? null;
    $beforeRun = $beforeRun ?? null;

    $comparisonUrl = $beforeRun?->target_url
        ?: $beforeRun?->page_url
        ?: $comparisonProject?->target_url;
?>
<div class="g-page-header">
    <div>
        <h2>
            <?php echo e($comparisonMode
                    ? 'Retest Website for Comparison'
                    : 'Run Website UX Test'); ?>

        </h2>

        <p>
            <?php if($comparisonMode): ?>
                Run a new test for the same website.
                The result will automatically be compared
                with <?php echo e($beforeRun->run_code); ?>.
            <?php else: ?>
                Run automated website UX testing using
                Playwright. The system will automatically
                create the project, test run, prediction,
                and report.
            <?php endif; ?>
        </p>
    </div>

    <a
        class="g-btn"
        href="<?php echo e(route('test-runs.index')); ?>"
    >
        View Test Runs
    </a>
</div>

<?php if($errors->any()): ?>
    <div class="g-alert-error">
        <strong>Validation error:</strong>
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<?php if($comparisonMode): ?>
    <div
        class="g-card"
        style="
            margin-bottom: 18px;
            border-left: 5px solid #2563eb;
        "
    >
        <div class="g-split-row">
            <div>
                <div class="g-soft-label">
                    Comparison Retest
                </div>

                <h3 style="margin-top: 6px;">
                    Before Test:
                    <?php echo e($beforeRun->run_code); ?>

                </h3>

                <p class="g-muted">
                    This new test will be saved under the
                    same project and automatically compared
                    after completion.
                </p>
            </div>

            <div class="g-kv">
                <div class="g-kv-row">
                    <span>Project</span>
                    <span>
                        <?php echo e($comparisonProject->name); ?>

                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Before Run</span>
                    <span>
                        <?php echo e($beforeRun->run_code); ?>

                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Website</span>
                    <span>
                        <?php echo e($comparisonUrl); ?>

                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<form
    id="ux-test-form"
    method="POST"
    action="<?php echo e(route('unified-tests.store')); ?>"
>
    <?php echo csrf_field(); ?>

    <input
        type="hidden"
        name="test_type"
        value="website"
    >

    <?php if($comparisonMode): ?>
        <input
            type="hidden"
            name="comparison_project_id"
            value="<?php echo e($comparisonProject->id); ?>"
        >

        <input
            type="hidden"
            name="compare_from"
            value="<?php echo e($beforeRun->id); ?>"
        >
    <?php endif; ?>

    <div class="g-layout-2-1">
        <div class="g-stack">
           <div class="g-card">
    <h3>Website Runner Configuration</h3>

    <div class="g-form-grid">
        <div class="g-form-field">
            <label>Show Live Browser</label>

            <select
                class="g-select"
                name="show_browser"
                id="show_browser"
            >
                <option
                    value="1"
                    <?php if(old('show_browser', '1') === '1'): echo 'selected'; endif; ?>
                >
                    Yes — show browser testing
                </option>

                <option
                    value="0"
                    <?php if(old('show_browser') === '0'): echo 'selected'; endif; ?>
                >
                    No — run browser in background
                </option>
            </select>
        </div>

        <div class="g-form-field">
            <label>Playwright Action Delay</label>

            <input
                class="g-input"
                type="number"
                name="slow_mo_ms"
                value="<?php echo e(old('slow_mo_ms', 350)); ?>"
                min="0"
                max="1000"
                step="50"
            >

            <span class="g-muted g-small">
                Recommended for demonstration: 300–500 milliseconds.
            </span>
        </div>
    </div>
</div>

            <div class="g-card" id="website-section">
                <h3>Website Test Configuration</h3>

                <div class="g-form-grid">
                    <div class="g-form-field">
                        <label>Website URL</label>
                        <input
                            class="g-input"
                            type="url"
                            name="target_url"
                            value="<?php echo e(old(
                                    'target_url',
                                    $comparisonMode
                                        ? $comparisonUrl
                                        : 'http://127.0.0.1:3000/landing-good'
                                )); ?>"
                            <?php if($comparisonMode): echo 'readonly'; endif; ?>
                            required
                        >

                        <?php if($comparisonMode): ?>
                            <span class="g-muted g-small">
                                The URL is locked because this test will be
                                compared with the selected previous test.
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="g-form-field">
    <label>Website Audit Mode</label>

    <select
        class="g-select"
        name="web_flow_type"
        required
    >
        <option
            value="full_audit"
            <?php if(old('web_flow_type', 'full_audit') === 'full_audit'): echo 'selected'; endif; ?>
        >
            Full Website Audit — test all detected safe features
        </option>

        <option
            value="auto"
            <?php if(old('web_flow_type') === 'auto'): echo 'selected'; endif; ?>
        >
            Quick Auto Test — test one detected flow
        </option>

        <option
            value="landing_navigation"
            <?php if(old('web_flow_type') === 'landing_navigation'): echo 'selected'; endif; ?>
        >
            Page Loading and Navigation Only
        </option>

        <option
            value="basic_search"
            <?php if(old('web_flow_type') === 'basic_search'): echo 'selected'; endif; ?>
        >
            Search Only
        </option>

        <option
            value="cta_click"
            <?php if(old('web_flow_type') === 'cta_click'): echo 'selected'; endif; ?>
        >
            CTA Only
        </option>
    </select>
</div>

                    <div class="g-form-field">
                        <label>Viewport Type</label>
                        <select class="g-select" name="viewport_type">
                            <option value="desktop" <?php if(old('viewport_type', 'desktop') === 'desktop'): echo 'selected'; endif; ?>>desktop</option>
                            <option value="tablet" <?php if(old('viewport_type') === 'tablet'): echo 'selected'; endif; ?>>tablet</option>
                            <option value="mobile" <?php if(old('viewport_type') === 'mobile'): echo 'selected'; endif; ?>>mobile</option>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Network Condition</label>
                        <select class="g-select" name="network_condition">
                            <option value="normal" <?php if(old('network_condition', 'normal') === 'normal'): echo 'selected'; endif; ?>>normal</option>
                            <option value="slow" <?php if(old('network_condition') === 'slow'): echo 'selected'; endif; ?>>slow</option>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Max Duration Seconds</label>

                        <input
                            class="g-input"
                            type="number"
                            name="max_duration_seconds"
                            value="<?php echo e(old('max_duration_seconds', 180)); ?>"
                            min="30"
                            max="300"
                        >

                        <span class="g-muted g-small">
                            Recommended for Full Website Audit: 180 seconds.
                        </span>
                    </div>
                </div>
            </div>

            <div class="g-card">
                <h3>Notes</h3>
                <textarea class="g-textarea" name="notes" rows="4" placeholder="Optional testing notes"><?php echo e(old('notes')); ?></textarea>

                <div class="g-actions" style="margin-top: 18px;">
                    <button
    class="g-btn g-btn-primary"
    type="submit"
    id="run-ux-test-button"
>
    <?php echo e($comparisonMode
            ? 'Run New Test and Compare'
            : 'Run Website UX Test'); ?>

</button>
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <div class="g-soft-label">Website Testing Pipeline</div>
            <h3 style="margin-top: 7px;">Website → Playwright → AI → Report</h3>

            <div class="g-kv" style="margin-top: 14px;">
                <div class="g-kv-row">
                    <span>Website Runner</span>
                    <span>Playwright</span>
                </div>

                <div class="g-kv-row">
                    <span>AI Service</span>
                    <span>FastAPI</span>
                </div>

                <div class="g-kv-row">
                    <span>Prediction</span>
                    <span>Web ML Model</span>
                </div>

                <div class="g-kv-row">
                    <span>Output</span>
                    <span>Low / Medium / High</span>
                </div>
            </div>

            <div class="g-device-stage" style="min-height: 260px; margin-top: 18px;">
                <div class="g-phone">
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line active"></div>
                    <div class="g-phone-line"></div>
                </div>
            </div>

            <p class="g-muted g-small">
                This page is dedicated to automated website UX testing.
            </p>
        </aside>
    </div>
</form>
<div id="g-test-running-overlay" class="g-test-running-overlay" hidden>
    <div class="g-test-running-card">
        <div class="g-ai-scan-screen" aria-hidden="true">
            <div class="g-ai-scan-grid"></div>
            <div class="g-ai-scan-target g-ai-scan-target-one"></div>
            <div class="g-ai-scan-target g-ai-scan-target-two"></div>
            <div class="g-ai-scan-line"></div>
        </div>

        <div class="g-soft-label">GAgent Autonomous Testing</div>

        <h3 id="g-test-running-title">
            Starting UX Test
        </h3>

        <p id="g-test-running-message" class="g-muted">
            Preparing the automation runner...
        </p>

        <div class="g-running-progress">
            <span></span>
        </div>

        <p class="g-muted g-small">
            Do not close this page while the test is running.
        </p>
    </div>
</div>

<script>
    const uxTestForm = document.getElementById('ux-test-form');
    const runButton = document.getElementById('run-ux-test-button');

    const runningOverlay = document.getElementById(
        'g-test-running-overlay'
    );

    const runningTitle = document.getElementById(
        'g-test-running-title'
    );

    const runningMessage = document.getElementById(
        'g-test-running-message'
    );

    function startRunningAnimation() {
        const messages = [
            'Launching the Playwright browser...',
            'Opening the target website...',
            'Inspecting page structure and interactive elements...',
            'Testing navigation, scrolling, and user actions...',
            'Collecting UX performance metrics...',
            'Capturing screenshot evidence...',
            'Sending metrics to the AI prediction service...',
            'Generating the final UX friction report...'
        ];

        runningTitle.textContent = 'Website UX Test Running';
        runningMessage.textContent = messages[0];
        runningOverlay.hidden = false;

        runButton.disabled = true;
        runButton.textContent = 'Running Website Test...';

        let messageIndex = 0;

        window.setInterval(() => {
            messageIndex = (
                messageIndex + 1
            ) % messages.length;

            runningMessage.textContent = messages[
                messageIndex
            ];
        }, 2600);
    }

    uxTestForm.addEventListener('submit', function () {
        startRunningAnimation();
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/unified-tests/create.blade.php ENDPATH**/ ?>