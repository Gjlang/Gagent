<?php $__env->startSection('title', 'Run Android Test'); ?>

<?php $__env->startSection('content'); ?>
<div class="g-page-header">
    <div>
        <div class="g-soft-label">Android Appium Testing</div>
        <h2>Run Android UX Test</h2>
        <p>
            Test the controlled dummy app, a permitted APK,
            or an already-installed Android application.
        </p>
    </div>
</div>

<?php if($errors->any()): ?>
    <div class="g-alert g-alert-error">
        <strong>Please correct these errors:</strong>

        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<form
    method="POST"
    action="<?php echo e(route('android-tests.store')); ?>"
    enctype="multipart/form-data"
>
    <?php echo csrf_field(); ?>

    <div class="g-grid g-grid-2">
        <div class="g-card">
            <h3>Android Test Configuration</h3>

            <div class="g-form-field">
                <label for="project_id">Project</label>

                <select
                    class="g-select"
                    id="project_id"
                    name="project_id"
                    required
                >
                    <option value="">Select project</option>

                    <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($project->id); ?>"
                            <?php if(
                                old('project_id') == $project->id
                            ): echo 'selected'; endif; ?>
                        >
                            <?php echo e($project->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="g-form-field">
                <label for="test_mode">Test Mode</label>

                <select
                    class="g-select"
                    id="test_mode"
                    name="test_mode"
                    required
                >
                    <option
                        value="dummy_app"
                        <?php if(
                            old('test_mode', 'dummy_app')
                            === 'dummy_app'
                        ): echo 'selected'; endif; ?>
                    >
                        Controlled Dummy App
                    </option>

                    <option
                        value="real_apk"
                        <?php if(
                            old('test_mode')
                            === 'real_apk'
                        ): echo 'selected'; endif; ?>
                    >
                        Real APK
                    </option>

                    <option
                        value="installed_app"
                        <?php if(
                            old('test_mode')
                            === 'installed_app'
                        ): echo 'selected'; endif; ?>
                    >
                        Installed App
                    </option>
                </select>
            </div>

            <div class="g-form-field">
                <label for="flow_type">Flow Type</label>

                <select
                    class="g-select"
                    id="flow_type"
                    name="flow_type"
                    required
                >
                    <?php $__currentLoopData = [
                        'basic_navigation' => 'Basic Navigation',
                        'button_click' => 'Button Click',
                        'form_input' => 'Form Input',
                        'search_flow' => 'Search Flow',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($value); ?>"
                            <?php if(
                                old(
                                    'flow_type',
                                    'basic_navigation'
                                ) === $value
                            ): echo 'selected'; endif; ?>
                        >
                            <?php echo e($label); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div
                class="g-form-field"
                id="apk-path-group"
            >
                <label for="apk_path">
                    APK Path
                </label>

                <input
                    class="g-input"
                    id="apk_path"
                    type="text"
                    name="apk_path"
                    value="<?php echo e(old('apk_path')); ?>"
                    placeholder="D:\Apps\rantau_mate.apk"
                >

                <small class="muted">
                    Required for Real APK mode.
                    Dummy mode uses the existing dummy APK automatically.
                </small>
            </div>

            <div
                class="g-form-field"
                id="apk-upload-group"
            >
                <label for="apk_file">
                    Or Upload APK
                </label>

                <input
                    class="g-input"
                    id="apk_file"
                    type="file"
                    name="apk_file"
                    accept=".apk,application/vnd.android.package-archive"
                >
            </div>

            <div
                class="g-form-field"
                id="package-group"
            >
                <label for="app_package">
                    App Package
                </label>

                <input
                    class="g-input"
                    id="app_package"
                    type="text"
                    name="app_package"
                    value="<?php echo e(old('app_package')); ?>"
                    placeholder="com.example.rantau_mate"
                >

                <small class="muted">
                    Required for Installed App mode.
                    Optional for APK mode.
                </small>
            </div>

            <div
                class="g-form-field"
                id="activity-group"
            >
                <label for="app_activity">
                    App Activity
                </label>

                <input
                    class="g-input"
                    id="app_activity"
                    type="text"
                    name="app_activity"
                    value="<?php echo e(old('app_activity')); ?>"
                    placeholder=".MainActivity"
                >

                <small class="muted">
                    Optional, but recommended for installed applications.
                </small>
            </div>

            <div class="g-form-field">
                <label for="device_name">
                    Device Name
                </label>

                <input
                    class="g-input"
                    id="device_name"
                    type="text"
                    name="device_name"
                    value="<?php echo e(old(
                        'device_name',
                        'emulator-5554'
                    )); ?>"
                    required
                >
            </div>

            <div class="g-form-field">
                <label for="max_duration_seconds">
                    Maximum Duration
                </label>

                <input
                    class="g-input"
                    id="max_duration_seconds"
                    type="number"
                    name="max_duration_seconds"
                    min="10"
                    max="180"
                    value="<?php echo e(old(
                        'max_duration_seconds',
                        60
                    )); ?>"
                    required
                >
            </div>

            <div class="g-form-field">
                <label for="notes">Notes</label>

                <textarea
                    class="g-textarea"
                    id="notes"
                    name="notes"
                    rows="4"
                    placeholder="Optional test notes"
                ><?php echo e(old('notes')); ?></textarea>
            </div>

            <button
                class="g-btn g-btn-primary"
                type="submit"
            >
                Create Android Test
            </button>
        </div>

        <aside class="g-card">
            <h3>Android Testing Rules</h3>

            <p>
                Test only applications that you own,
                created, or have permission to evaluate.
            </p>

            <p>
                The generic runner does not bypass:
            </p>

            <ul>
                <li>Authentication</li>
                <li>CAPTCHA</li>
                <li>Two-factor authentication</li>
                <li>Payment protections</li>
                <li>Private data protections</li>
            </ul>

            <div class="g-alert">
                Real Android app testing uses generic Appium
                exploration. It supports simple flows only.
                The controlled dummy app remains the main
                reliable FYP evaluation target.
            </div>
        </aside>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeSelect =
        document.getElementById('test_mode');

    const apkPathGroup =
        document.getElementById('apk-path-group');

    const apkUploadGroup =
        document.getElementById('apk-upload-group');

    const packageGroup =
        document.getElementById('package-group');

    const activityGroup =
        document.getElementById('activity-group');

    const apkPath =
        document.getElementById('apk_path');

    const packageInput =
        document.getElementById('app_package');

    function updateModeFields() {
        const mode = modeSelect.value;

        const isDummy =
            mode === 'dummy_app';

        const isRealApk =
            mode === 'real_apk';

        const isInstalled =
            mode === 'installed_app';

        apkPathGroup.style.display =
            isInstalled ? 'none' : 'block';

        apkUploadGroup.style.display =
            isInstalled || isDummy
                ? 'none'
                : 'block';

        packageGroup.style.display =
            isDummy ? 'none' : 'block';

        activityGroup.style.display =
            isDummy ? 'none' : 'block';

        apkPath.required = isRealApk;
        packageInput.required = isInstalled;
    }

    modeSelect.addEventListener(
        'change',
        updateModeFields
    );

    updateModeFields();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/android-tests/create.blade.php ENDPATH**/ ?>