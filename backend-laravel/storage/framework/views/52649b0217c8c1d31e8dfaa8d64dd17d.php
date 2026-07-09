<?php $__env->startSection('title', 'Live Website Testing'); ?>
<?php $__env->startSection('kicker', 'Playwright Runner'); ?>

<?php $__env->startSection('content'); ?>
<div class="g-page-header">
    <div>
        <h2>Run Live Website Test</h2>
        <p>Create a Playwright live website session, collect UX metrics, send them to FastAPI, and save the prediction/report through the existing controller.</p>
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

<div class="g-layout-2-1">
    <div class="g-card">
        <h3>Website Test Configuration</h3>
        <form method="POST" action="<?php echo e(route('live-tests.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="g-form-grid">
                <div class="g-form-field">
                    <label>Project</label>
                    <select class="g-select" name="project_id" required>
                        <option value="">Select project</option>
                        <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($project->id); ?>" <?php if(old('project_id') == $project->id): echo 'selected'; endif; ?>><?php echo e($project->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Website URL</label>
                    <input class="g-input" type="url" name="target_url" value="<?php echo e(old('target_url', 'http://127.0.0.1:3000')); ?>" required>
                </div>
                <div class="g-form-field">
                    <label>Flow Type</label>
                    <select class="g-select" name="flow_type" required>
                        <option value="landing_navigation" <?php if(old('flow_type') === 'landing_navigation'): echo 'selected'; endif; ?>>landing_navigation</option>
                        <option value="cta_click" <?php if(old('flow_type') === 'cta_click'): echo 'selected'; endif; ?>>cta_click</option>
                        <option value="basic_search" <?php if(old('flow_type') === 'basic_search'): echo 'selected'; endif; ?>>basic_search</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Viewport Type</label>
                    <select class="g-select" name="viewport_type" required>
                        <option value="desktop" <?php if(old('viewport_type', 'desktop') === 'desktop'): echo 'selected'; endif; ?>>desktop</option>
                        <option value="tablet" <?php if(old('viewport_type') === 'tablet'): echo 'selected'; endif; ?>>tablet</option>
                        <option value="mobile" <?php if(old('viewport_type') === 'mobile'): echo 'selected'; endif; ?>>mobile</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Network Condition</label>
                    <select class="g-select" name="network_condition" required>
                        <option value="normal" <?php if(old('network_condition', 'normal') === 'normal'): echo 'selected'; endif; ?>>normal</option>
                        <option value="slow" <?php if(old('network_condition') === 'slow'): echo 'selected'; endif; ?>>slow</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Max Duration Seconds</label>
                    <input class="g-input" type="number" name="max_duration_seconds" value="<?php echo e(old('max_duration_seconds', 30)); ?>" min="10" max="120" required>
                </div>
                <div class="g-form-field" style="grid-column: 1 / -1;">
                    <label>Notes</label>
                    <textarea class="g-textarea" name="notes" rows="4" placeholder="Optional testing notes"><?php echo e(old('notes')); ?></textarea>
                </div>
            </div>
            <div class="g-actions" style="margin-top: 18px;">
                <button class="g-btn g-btn-primary" type="submit">Create Live Test Run</button>
            </div>
        </form>
    </div>

    <aside class="g-panel">
        <div class="g-soft-label">Execution Pipeline</div>
        <h3 style="margin-top: 7px;">Playwright → FastAPI → Report</h3>
        <div class="g-device-stage" style="min-height: 260px; margin-top: 14px;">
            <div class="g-phone">
                <div class="g-phone-line"></div>
                <div class="g-phone-line"></div>
                <div class="g-phone-line active"></div>
                <div class="g-phone-line"></div>
            </div>
        </div>
        <p class="g-muted g-small">This page only changes the Blade UI. The form action remains <strong>live-tests.store</strong>.</p>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/live-tests/create.blade.php ENDPATH**/ ?>