<?php $__env->startSection('title', 'AI Analysis'); ?>
<?php $__env->startSection('kicker', 'FastAPI Model Check'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $healthStatus = $health['status'] ?? 'N/A';
    $modelStatus = $modelInfo['status'] ?? 'N/A';
    $mainStatus = $mainPrediction['status'] ?? 'N/A';
    $baseStatus = $baselinePrediction['status'] ?? 'N/A';
?>

<div class="g-page-header">
    <div>
        <h2>UX Friction Analysis</h2>
        <p>This page keeps the existing Laravel-to-FastAPI service test logic but presents it like a modern AI analysis panel.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn" href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
        <button class="g-btn g-btn-dark" type="button" onclick="window.location.reload()">Rerun Session</button>
    </div>
</div>

<div class="g-layout-3-2">
    <div class="g-stack">
        <div class="g-card">
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">Analysis Engine</div>
                    <h3 style="margin-top: 7px;">Live Heatmap Overlay</h3>
                </div>
                <span class="g-badge badge-final">Endpoint test</span>
            </div>
            <div class="g-heatmap" style="margin-top: 14px;">
                <div class="g-tilted-card">
                    <div class="g-phone-line" style="width: 74%;"></div>
                    <div class="g-phone-line" style="width: 95%;"></div>
                    <div class="g-phone-line" style="width: 88%;"></div>
                    <div class="g-hotspot"></div>
                    <div class="g-phone-line active"></div>
                </div>
            </div>
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Health Result</h3>
                <p><strong>Status:</strong> <span class="g-status-badge <?php echo e($healthStatus === 'success' ? 'g-status-completed' : 'g-status-failed'); ?>"><?php echo e($healthStatus); ?></span></p>
                <p><strong>HTTP:</strong> <?php echo e($health['http_status'] ?? 'N/A'); ?></p>
                <pre class="g-console"><?php echo e(json_encode($health, JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <div class="g-card">
                <h3>Model Info</h3>
                <p><strong>Status:</strong> <span class="g-status-badge <?php echo e($modelStatus === 'success' ? 'g-status-completed' : 'g-status-failed'); ?>"><?php echo e($modelStatus); ?></span></p>
                <p><strong>HTTP:</strong> <?php echo e($modelInfo['http_status'] ?? 'N/A'); ?></p>
                <pre class="g-console"><?php echo e(json_encode($modelInfo, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Main GAgent Prediction Test</h3>
                <p><strong>Status:</strong> <span class="g-status-badge <?php echo e($mainStatus === 'success' ? 'g-status-completed' : 'g-status-failed'); ?>"><?php echo e($mainStatus); ?></span></p>
                <p><strong>HTTP:</strong> <?php echo e($mainPrediction['http_status'] ?? 'N/A'); ?></p>
                <pre class="g-console"><?php echo e(json_encode($mainPrediction, JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <div class="g-card">
                <h3>Baseline Prediction Test</h3>
                <p><strong>Status:</strong> <span class="g-status-badge <?php echo e($baseStatus === 'success' ? 'g-status-completed' : 'g-status-failed'); ?>"><?php echo e($baseStatus); ?></span></p>
                <p><strong>HTTP:</strong> <?php echo e($baselinePrediction['http_status'] ?? 'N/A'); ?></p>
                <pre class="g-console"><?php echo e(json_encode($baselinePrediction, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    </div>

    <aside class="g-stack">
        <div class="g-panel">
            <div class="g-soft-label">Friction Score</div>
            <div class="g-metric-value"><?php echo e(($mainPrediction['data']['friction_level'] ?? $mainPrediction['data']['prediction'] ?? null) ? 'Ready' : 'N/A'); ?></div>
            <p class="g-muted">Service URL: <?php echo e(env('GAGENT_AI_SERVICE_URL', 'http://127.0.0.1:8001')); ?></p>
        </div>

        <div class="g-panel">
            <h3>Failed Steps</h3>
            <?php if(($health['status'] ?? null) !== 'success'): ?>
                <div class="g-alert-error">FastAPI service unavailable. Start the AI service and try again.</div>
            <?php else: ?>
                <p class="g-muted">No failed service step detected from the health check.</p>
            <?php endif; ?>
        </div>

        <div class="g-insight-card" style="background: #06172b; color: #dff2ff;">
            <div class="g-soft-label" style="color: #7dd3fc;">AI Recommendation</div>
            <h3 style="margin-top: 7px; color: white;">Connection Checklist</h3>
            <ul style="padding-left: 18px; line-height: 1.7;">
                <li>FastAPI must run on port 8001.</li>
                <li>Laravel must use the correct GAGENT_AI_SERVICE_URL.</li>
                <li>Main and baseline model endpoints must return success.</li>
            </ul>
        </div>

        <div class="g-card">
            <h3>Payload Sent to Main GAgent</h3>
            <pre class="g-console"><?php echo e(json_encode($sampleGAgentFeatures, JSON_PRETTY_PRINT)); ?></pre>
        </div>
        <div class="g-card">
            <h3>Payload Sent to Baseline</h3>
            <pre class="g-console"><?php echo e(json_encode($baselineFeatures, JSON_PRETTY_PRINT)); ?></pre>
        </div>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/ai-service-test.blade.php ENDPATH**/ ?>