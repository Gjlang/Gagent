<?php $__env->startSection('title', 'Run UX Test'); ?>
<?php $__env->startSection('kicker', 'Unified Website and Android Runner'); ?>

<?php $__env->startSection('content'); ?>
<div class="g-page-header">
    <div>
        <h2>Run UX Test</h2>
        <p>Run Website or Android UX testing from one page. The system will auto-create the project, test run, prediction, and report.</p>
    </div>
    <a class="g-btn" href="<?php echo e(route('test-runs.index')); ?>">View Test Runs</a>
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

<form method="POST" action="<?php echo e(route('unified-tests.store')); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="g-layout-2-1">
        <div class="g-stack">
            <div class="g-card">
                <h3>Choose Test Type</h3>

                <div class="g-form-grid">
                    <div class="g-form-field">
                        <label>Test Type</label>
                        <select class="g-select" name="test_type" id="test_type" required>
                            <option value="website" <?php if(old('test_type', 'website') === 'website'): echo 'selected'; endif; ?>>Website</option>
                            <option value="android" <?php if(old('test_type') === 'android'): echo 'selected'; endif; ?>>Android App</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="g-card" id="website-section">
                <h3>Website Test Configuration</h3>

                <div class="g-form-grid">
                    <div class="g-form-field">
                        <label>Website URL</label>
                        <input class="g-input" type="url" name="target_url" value="<?php echo e(old('target_url', 'http://127.0.0.1:3000/landing-good')); ?>">
                    </div>

                    <input type="hidden" name="web_flow_type" value="auto">

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
                        <input class="g-input" type="number" name="max_duration_seconds" value="<?php echo e(old('max_duration_seconds', 30)); ?>" min="10" max="120">
                    </div>
                </div>
            </div>

            <div class="g-card" id="android-section" style="display: none;">
                <h3>Android Appium Configuration</h3>

                <div class="g-form-grid">
                    <div class="g-form-field">
                        <label>Android Flow</label>
                        <select class="g-select" name="android_flow_type">
                            <?php $__currentLoopData = ['login', 'signup', 'search', 'button_click', 'form_submit']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($flow); ?>" <?php if(old('android_flow_type', 'login') === $flow): echo 'selected'; endif; ?>><?php echo e($flow); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Scenario</label>
                        <select class="g-select" name="android_scenario_type">
                            <option value="good" <?php if(old('android_scenario_type', 'good') === 'good'): echo 'selected'; endif; ?>>good</option>
                            <option value="medium" <?php if(old('android_scenario_type') === 'medium'): echo 'selected'; endif; ?>>medium</option>
                            <option value="bad" <?php if(old('android_scenario_type') === 'bad'): echo 'selected'; endif; ?>>bad</option>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Target App Package</label>
                        <input class="g-input" name="target_app_package" value="<?php echo e(old('target_app_package', 'com.gagent.dummyandroid')); ?>">
                    </div>

                    <div class="g-form-field">
                        <label>Target App Activity</label>
                        <input class="g-input" name="target_app_activity" value="<?php echo e(old('target_app_activity', 'com.gagent.dummyandroid.MainActivity')); ?>">
                    </div>

                    <div class="g-form-field">
                        <label>Device Name</label>
                        <input class="g-input" name="device_name" value="<?php echo e(old('device_name', 'emulator-5554')); ?>">
                    </div>

                    <div class="g-form-field">
                        <label>APK Path</label>
                        <input class="g-input" name="apk_path" value="<?php echo e(old('apk_path')); ?>" placeholder="Leave empty to use dummy APK">
                    </div>

                    <div class="g-form-field" style="grid-column: 1 / -1;">
                        <label>Upload APK Optional</label>
                        <input class="g-input" type="file" name="apk_file" accept=".apk">
                    </div>
                </div>

                <p class="g-muted g-small" style="margin-top: 12px;">
                    Android auto-run requires emulator and Appium server to be running before clicking Run.
                </p>
            </div>

            <div class="g-card">
                <h3>Notes</h3>
                <textarea class="g-textarea" name="notes" rows="4" placeholder="Optional testing notes"><?php echo e(old('notes')); ?></textarea>

                <div class="g-actions" style="margin-top: 18px;">
                    <button class="g-btn g-btn-primary" type="submit">Run UX Test Now</button>
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <div class="g-soft-label">Unified Pipeline</div>
            <h3 style="margin-top: 7px;">Auto Project → Test Run → AI → Report</h3>

            <div class="g-kv" style="margin-top: 14px;">
                <div class="g-kv-row"><span>Website Runner</span><span>Playwright</span></div>
                <div class="g-kv-row"><span>Android Runner</span><span>Appium</span></div>
                <div class="g-kv-row"><span>AI Service</span><span>FastAPI</span></div>
                <div class="g-kv-row"><span>Output</span><span>Low / Medium / High</span></div>
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
                Old pages are still kept as backup. This page is the new simplified flow.
            </p>
        </aside>
    </div>
</form>

<script>
    const testType = document.getElementById('test_type');
    const websiteSection = document.getElementById('website-section');
    const androidSection = document.getElementById('android-section');

    function toggleSections() {
        if (testType.value === 'android') {
            websiteSection.style.display = 'none';
            androidSection.style.display = 'block';
        } else {
            websiteSection.style.display = 'block';
            androidSection.style.display = 'none';
        }
    }

    testType.addEventListener('change', toggleSections);
    toggleSections();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/unified-tests/create.blade.php ENDPATH**/ ?>