<?php $__env->startSection('title', 'UX Friction Report'); ?>
<?php $__env->startSection('kicker', 'Generated UX Audit'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $run = $report->testRun;
    $project = $run?->project;
    $metric = $run?->uxMetric;
    $final = $run?->finalFrictionResult;
    $main = $run?->mainGAgentResult;
    $baseline = $run?->baselineResult;

    $level = $final?->friction_level ?? 'Not predicted';

    $badgeClass = match ($level) {
        'Low' => 'badge-low',
        'Medium' => 'badge-medium',
        'High' => 'badge-high',
        default => 'badge-neutral',
    };

    $confidence = $final?->confidence_score !== null
        ? number_format($final->confidence_score * 100, 1) . '%'
        : 'N/A';

    $completionTime = $metric?->completion_time ?? 'N/A';
    $retryCount = $metric?->retry_count ?? 0;
    $errorCount = $metric?->error_count ?? 0;
    $failedClicks = $metric?->failed_clicks ?? 0;

    $recommendations = [];

    if ($main && !empty($main->recommendations)) {
        if (is_array($main->recommendations)) {
            $recommendations = $main->recommendations;
        } elseif (is_string($main->recommendations)) {
            $decodedRecommendations = json_decode($main->recommendations, true);
            $recommendations = is_array($decodedRecommendations)
                ? $decodedRecommendations
                : [$main->recommendations];
        }
    }

    $mainProbabilities = $main?->class_probabilities ?? null;

    if (is_string($mainProbabilities)) {
        $decodedMainProbabilities = json_decode($mainProbabilities, true);
        $mainProbabilities = $decodedMainProbabilities ?: $mainProbabilities;
    }

    $baselineProbabilities = $baseline?->class_probabilities ?? null;

    if (is_string($baselineProbabilities)) {
        $decodedBaselineProbabilities = json_decode($baselineProbabilities, true);
        $baselineProbabilities = $decodedBaselineProbabilities ?: $baselineProbabilities;
    }

    $screenshots = $run?->screenshots ?? collect();
    $logs = $run?->interactionLogs ?? collect();
    $auditLogs = $logs->where('event_type', 'audit_flow');
    $finalInputFeatures = $final?->input_features ?? [];

    if (is_string($finalInputFeatures)) {
        $finalInputFeatures = json_decode($finalInputFeatures, true) ?? [];
    }

    $overallAuditScore = $finalInputFeatures['average_severity_score'] ?? null;
    $isFullAuditReport = $run?->flow_type === 'full_audit';

    if ($isFullAuditReport && $overallAuditScore !== null) {
    $frictionScore = round((((float) $overallAuditScore - 1) / 2) * 100);
    $frictionScore = max(0, min(100, $frictionScore));
} else {
    $frictionScore = $final?->confidence_score !== null
        ? round($final->confidence_score * 100)
        : 0;
}
?>

<div class="g-page-header">
    <div>
        <h2><?php echo e($report->title ?? 'UX Friction Report'); ?></h2>
        <p>
            Generated at:
            <?php echo e($report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('Y-m-d H:i') : 'N/A'); ?>

        </p>
    </div>

    <div class="g-actions">
        <a class="g-btn" href="<?php echo e(route('reports.index')); ?>">Back to Reports</a>

        <?php if($run): ?>
            <a class="g-btn g-btn-primary" href="<?php echo e(route('test-runs.show', $run)); ?>">View Test Run</a>
        <?php endif; ?>
    </div>
</div>

<div class="g-layout-2-1 g-report-page-layout">
    <div class="g-stack">

        
        <div class="g-report-card">
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">Report Analysis</div>

                    <h3 style="margin-top: 6px;">
                        Executive Summary
                    </h3>
                </div>

                <span class="g-badge <?php echo e($badgeClass); ?>">
                    <?php echo e($level); ?>

                </span>
            </div>

            <p class="g-muted" style="margin-top: 12px;">
                <?php echo e($report->summary ?? 'No executive summary available for this report.'); ?>

            </p>

            <div class="g-grid g-grid-3" style="margin-top: 18px;">
                <div class="g-metric-card">
                    <div class="g-metric-label">Total Sessions</div>
                    <div class="g-metric-value">1</div>
                    <div class="g-metric-sub">Current analysed test run</div>
                </div>

                <div class="g-metric-card">
                    <div class="g-metric-label">Friction Alerts</div>
                    <div class="g-metric-value" style="color: var(--g-red);">
                        <?php echo e((int) $errorCount + (int) $failedClicks); ?>

                    </div>
                    <div class="g-metric-sub">Errors and failed clicks</div>
                </div>

                <div class="g-metric-card">
                    <div class="g-metric-label">Avg Time to Complete</div>
                    <div class="g-metric-value" style="font-size: 28px;">
                        <?php echo e($completionTime); ?>

                    </div>
                    <div class="g-metric-sub">Completion time metric</div>
                </div>
            </div>
        </div>

        
        <?php if($run?->flow_type === 'full_audit' && $auditLogs->isNotEmpty()): ?>
            <div class="g-card">
                <div class="g-split-row">
                    <div>
                        <div class="g-soft-label">Full Website Audit</div>
                        <h3 style="margin-top: 6px;">Detected Features and Test Results</h3>
                    </div>

                    <span class="g-badge badge-final"><?php echo e($auditLogs->count()); ?> flows</span>
                </div>

                <?php if($overallAuditScore !== null): ?>
    <div class="g-card" style="margin-top: 14px; background: var(--g-surface-soft); box-shadow: none;">
        <div class="g-split-row">
            <div>
                <div class="g-soft-label">Overall Average Result</div>

                <strong>
                    <?php echo e(number_format((float) $overallAuditScore, 2)); ?> / 3.00
                </strong>
            </div>

            <span class="g-badge <?php echo e($badgeClass); ?>">
                <?php echo e($level); ?>

            </span>
        </div>
    </div>
<?php endif; ?>

                <div class="g-kv" style="margin-top: 14px;">
                    <?php $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $auditLog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $rawMetadata = $auditLog->metadata;

                            if (is_array($rawMetadata)) {
                                $metadata = $rawMetadata;
                            } elseif (is_string($rawMetadata)) {
                                $metadata = json_decode($rawMetadata, true) ?? [];
                            } else {
                                $metadata = [];
                            }

                            $status = $metadata['status'] ?? $auditLog->event_value ?? 'unknown';

                            $statusClass = match ($status) {
                                'passed' => 'badge-low',
                                'failed' => 'badge-high',
                                'skipped' => 'badge-neutral',
                                default => 'badge-neutral',
                            };

                            $prediction = $metadata['prediction'] ?? null;

                            $flowLevel = is_array($prediction)
                                ? ($prediction['friction_level'] ?? null)
                                : null;

                            $flowConfidence = is_array($prediction) && isset($prediction['confidence_score'])
                                ? number_format(((float) $prediction['confidence_score']) * 100, 1) . '%'
                                : null;
                        ?>

                        <div style="padding: 14px 0; border-bottom: 1px solid var(--g-border);">
                            <div class="g-split-row">
                                <strong><?php echo e($auditLog->event_label); ?></strong>
                                <span class="g-badge <?php echo e($statusClass); ?>"><?php echo e($status); ?></span>
                            </div>

                            <p class="g-muted g-small" style="margin: 7px 0 0;">
                                <?php echo e($metadata['reason'] ?? 'No details available.'); ?>

                            </p>

                            <?php if($flowLevel): ?>
                                <div class="g-small" style="margin-top: 8px;">
                                    AI result: <strong><?php echo e($flowLevel); ?></strong>
                                    <?php if($flowConfidence): ?>
                                        — <?php echo e($flowConfidence); ?>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Retry Analysis</h3>

                <?php if(!$metric): ?>
                    <div class="g-empty">
                        <strong>No UX metrics available.</strong>
                        Run metrics were not attached to this report.
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 14px;">
                        <div>
                            <div class="g-split-row g-small">
                                <strong>Retry Count</strong>
                                <span><?php echo e($metric->retry_count ?? 0); ?></span>
                            </div>
                            <div class="g-progress warn">
                                <span style="width: <?php echo e(min(100, (($metric->retry_count ?? 0) / 10) * 100)); ?>%;"></span>
                            </div>
                        </div>

                        <div>
                            <div class="g-split-row g-small">
                                <strong>Error Count</strong>
                                <span><?php echo e($metric->error_count ?? 0); ?></span>
                            </div>
                            <div class="g-progress danger">
                                <span style="width: <?php echo e(min(100, (($metric->error_count ?? 0) / 10) * 100)); ?>%;"></span>
                            </div>
                        </div>

                        <div>
                            <div class="g-split-row g-small">
                                <strong>Failed Clicks</strong>
                                <span><?php echo e($metric->failed_clicks ?? 0); ?></span>
                            </div>
                            <div class="g-progress danger">
                                <span style="width: <?php echo e(min(100, (($metric->failed_clicks ?? 0) / 10) * 100)); ?>%;"></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="g-card">
                <h3>Engagement Metrics</h3>

                <?php if(!$metric): ?>
                    <div class="g-empty">
                        <strong>No engagement data.</strong>
                        Engagement values will appear when metrics exist.
                    </div>
                <?php else: ?>
                    <div class="g-mini-bars">
                        <span style="height: <?php echo e(min(120, max(12, ($metric->click_count ?? 1) * 8))); ?>px;"></span>
                        <span style="height: <?php echo e(min(120, max(12, ($metric->scroll_count ?? 1) * 12))); ?>px;"></span>
                        <span style="height: <?php echo e(min(120, max(12, ($metric->keyboard_count ?? 1) * 10))); ?>px;"></span>
                        <span style="height: <?php echo e(min(120, max(12, ($metric->retry_count ?? 1) * 20))); ?>px;"></span>
                        <span style="height: <?php echo e(min(120, max(12, ($metric->error_count ?? 1) * 22))); ?>px;"></span>
                    </div>

                    <div class="g-kv" style="margin-top: 12px;">
                        <div class="g-kv-row"><span>Click Count</span><span><?php echo e($metric->click_count ?? 'N/A'); ?></span></div>
                        <div class="g-kv-row"><span>Scroll Count</span><span><?php echo e($metric->scroll_count ?? 'N/A'); ?></span></div>
                        <div class="g-kv-row"><span>Keyboard Count</span><span><?php echo e($metric->keyboard_count ?? 'N/A'); ?></span></div>
                        <div class="g-kv-row"><span>Task Completed</span><span><?php echo e(($metric->task_completed ?? false) ? 'Yes' : 'No'); ?></span></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="g-card">
            <h3>UX Metric Evidence</h3>

            <?php if(!$metric): ?>
                <div class="g-empty">
                    <strong>No UX metrics available.</strong>
                    The report exists, but no linked UX metric row was found.
                </div>
            <?php else: ?>
                <div class="g-table-wrap">
                    <table class="g-table">
                        <tbody>
                            <tr><th>Completion Time</th><td><?php echo e($metric->completion_time ?? 'N/A'); ?></td></tr>
                            <tr><th>Click Count</th><td><?php echo e($metric->click_count ?? 'N/A'); ?></td></tr>
                            <tr><th>Scroll Count</th><td><?php echo e($metric->scroll_count ?? 'N/A'); ?></td></tr>
                            <tr><th>Keyboard Count</th><td><?php echo e($metric->keyboard_count ?? 'N/A'); ?></td></tr>
                            <tr><th>Retry Count</th><td><?php echo e($metric->retry_count ?? 'N/A'); ?></td></tr>
                            <tr><th>Error Count</th><td><?php echo e($metric->error_count ?? 'N/A'); ?></td></tr>
                            <tr><th>Failed Clicks</th><td><?php echo e($metric->failed_clicks ?? 'N/A'); ?></td></tr>
                            <tr><th>Unnecessary Clicks</th><td><?php echo e($metric->unnecessary_clicks ?? 'N/A'); ?></td></tr>
                            <tr><th>Path Deviation</th><td><?php echo e($metric->path_deviation_score ?? 'N/A'); ?></td></tr>
                            <tr><th>Page Load Time</th><td><?php echo e($metric->page_load_time_ms !== null ? $metric->page_load_time_ms . ' ms' : 'N/A'); ?></td></tr>
                            <tr><th>Feedback Delay</th><td><?php echo e($metric->feedback_delay_ms !== null ? $metric->feedback_delay_ms . ' ms' : 'N/A'); ?></td></tr>
                            <tr><th>Cumulative Layout Shift</th><td><?php echo e($metric->cumulative_layout_shift ?? 'N/A'); ?></td></tr>
                            <tr><th>Popup Detected</th><td><?php echo e(($metric->popup_detected ?? false) ? 'Yes' : 'No'); ?></td></tr>
                            <tr><th>Cookie Banner Detected</th><td><?php echo e(($metric->cookie_banner_detected ?? false) ? 'Yes' : 'No'); ?></td></tr>
                            <tr><th>Overlay Blocks CTA</th><td><?php echo e(($metric->overlay_blocks_cta ?? false) ? 'Yes' : 'No'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Interaction Logs</h3>

            <?php if(!$run || $logs->isEmpty()): ?>
                <div class="g-empty">
                    <strong>No interaction logs available.</strong>
                    Event logs will appear here after the automation runner stores them.
                </div>
            <?php else: ?>
                <div class="g-table-wrap">
                    <table class="g-table">
                        <thead>
                            <tr>
                                <th>Event Type</th>
                                <th>Label</th>
                                <th>Value</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($log->event_type ?? 'N/A'); ?></td>
                                    <td><?php echo e($log->event_label ?? 'N/A'); ?></td>
                                    <td><?php echo e($log->event_value ?? 'N/A'); ?></td>
                                    <td><?php echo e($log->event_time ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Conclusion</h3>
            <p class="g-muted">
                <?php echo e($report->conclusion ?? 'No conclusion available for this report.'); ?>

            </p>
        </div>
    </div>

    <aside class="g-stack">
        <div class="g-panel">
            <div class="g-soft-label">AI Prediction</div>
            <h3 style="margin-top: 7px;">Likely UX Friction Identified</h3>

            <div style="margin-top: 14px;">
                <span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span>
            </div>

            <div class="g-metric-value" style="font-size: 42px; margin-top: 16px;">
                <?php echo e($frictionScore); ?>

                <span style="font-size: 15px;">/100</span>
            </div>

            <div class="g-progress <?php echo e($level === 'High' ? 'danger' : ($level === 'Medium' ? 'warn' : 'safe')); ?>">
                <span style="width: <?php echo e($frictionScore); ?>%;"></span>
            </div>

            <div class="g-kv" style="margin-top: 16px;">
                <div class="g-kv-row"><span>Confidence</span><span><?php echo e($confidence); ?></span></div>
                <div class="g-kv-row"><span>Source</span><span><?php echo e($final?->prediction_source ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Run Code</span><span><?php echo e($run?->run_code ?? 'N/A'); ?></span></div>
            </div>
        </div>

        <div class="g-panel">
            <h3>Project Details</h3>
            <div class="g-kv">
                <div class="g-kv-row"><span>Project</span><span><?php echo e($project?->name ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Target Type</span><span><?php echo e($project?->target_type ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Target URL</span><span><?php echo e($project?->target_url ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Flow</span><span><?php echo e($run?->flow_type ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Scenario</span><span><?php echo e($run?->scenario_type ?? $run?->run_mode ?? 'N/A'); ?></span></div>
                <div class="g-kv-row"><span>Viewport</span><span><?php echo e($run?->viewport_type ?? 'N/A'); ?></span></div>
            </div>
        </div>

        <div class="g-insight-card" style="background: #06172b; color: #dff2ff;">
            <div class="g-soft-label" style="color: #7dd3fc;">AI Recommendation</div>
            <h3 style="margin-top: 7px; color: white;">Fix Priority</h3>

            <?php if(empty($recommendations)): ?>
                <p style="color: #cbd5e1;">
                    No recommendations were returned by the main GAgent model.
                </p>
            <?php else: ?>
                <ul style="padding-left: 18px; line-height: 1.7;">
                    <?php $__currentLoopData = $recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e(is_array($recommendation) ? json_encode($recommendation) : $recommendation); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Main GAgent Prediction</h3>

            <?php if(!$main): ?>
                <div class="g-empty">
                    <strong>No main GAgent prediction.</strong>
                    Run GAgent prediction from the test run page.
                </div>
            <?php else: ?>
                <div class="g-kv">
                    <div class="g-kv-row"><span>Model</span><span><?php echo e($main->model_name ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Type</span><span><?php echo e($main->model_type ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Friction</span><span><?php echo e($main->friction_level ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Confidence</span><span><?php echo e($main->confidence_score !== null ? number_format($main->confidence_score * 100, 1) . '%' : 'N/A'); ?></span></div>
                </div>

                <h4 style="margin-top: 16px;">Class Probabilities</h4>
                <pre class="g-console"><?php echo e(json_encode($mainProbabilities, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Baseline Comparison</h3>

            <?php if(!$baseline): ?>
                <div class="g-empty">
                    <strong>No baseline prediction.</strong>
                    Baseline is optional and used for comparison only.
                </div>
            <?php else: ?>
                <div class="g-kv">
                    <div class="g-kv-row"><span>Model</span><span><?php echo e($baseline->model_name ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Type</span><span><?php echo e($baseline->model_type ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Friction</span><span><?php echo e($baseline->friction_level ?? 'N/A'); ?></span></div>
                    <div class="g-kv-row"><span>Confidence</span><span><?php echo e($baseline->confidence_score !== null ? number_format($baseline->confidence_score * 100, 1) . '%' : 'N/A'); ?></span></div>
                </div>

                <?php if($baselineProbabilities): ?>
                    <h4 style="margin-top: 16px;">Class Probabilities</h4>
                    <pre class="g-console"><?php echo e(json_encode($baselineProbabilities, JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>


<div class="g-card g-report-screenshot-section">
    <div class="g-split-row">
        <div>
            <div class="g-soft-label">Friction Points Evidence</div>
            <h3 style="margin-top: 6px;">Screenshot Evidence</h3>
        </div>

        <span class="g-badge badge-neutral"><?php echo e($screenshots->count()); ?> captures</span>
    </div>

    <?php if(!$run || $screenshots->isEmpty()): ?>
        <div class="g-empty">
            <strong>No screenshots available.</strong>
            Screenshot evidence will appear here after the test runner saves captures.
        </div>
    <?php else: ?>
        <div class="g-screenshot-full-list">
            <div class="g-screenshot-thumb-grid">
                <?php $__currentLoopData = $screenshots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screenshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $imageUrl = asset('storage/' . $screenshot->file_path);

                        $screenshotLevel = $screenshot->friction_level ?: $level;

                        $screenshotBadgeClass = match ($screenshotLevel) {
                            'Low' => 'badge-low',
                            'Medium' => 'badge-medium',
                            'High' => 'badge-high',
                            default => 'badge-neutral',
                        };

                        $screenshotConfidence = $screenshot->confidence_score !== null
                            ? number_format($screenshot->confidence_score * 100, 1) . '%'
                            : null;
                    ?>

                    <div class="g-screenshot-thumb-card">
                        <div class="g-split-row" style="align-items: flex-start; margin-bottom: 10px;">
                            <div style="min-width: 0;">
                                <strong><?php echo e($screenshot->label ?? 'Screenshot Evidence'); ?></strong>
                                <div class="g-muted g-small" style="margin-top: 4px; word-break: break-all;">
                                    <?php echo e($screenshot->file_path); ?>

                                </div>
                            </div>

                            <div style="text-align: right; flex-shrink: 0;">
                                <span class="g-badge <?php echo e($screenshotBadgeClass); ?>">
                                    <?php echo e($screenshotLevel); ?>

                                </span>

                                <?php if($screenshotConfidence): ?>
                                    <div class="g-muted g-small" style="margin-top: 5px;">
                                        <?php echo e($screenshotConfidence); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a
                            href="<?php echo e($imageUrl); ?>"
                            target="_blank"
                            rel="noopener"
                            class="g-screenshot-thumb-link"
                        >
                            <img
                                src="<?php echo e($imageUrl); ?>"
                                alt="<?php echo e($screenshot->label ?? 'Screenshot Evidence'); ?>"
                                class="g-screenshot-thumb-img"
                                loading="lazy"
                            >
                        </a>

                        <div style="margin-top: 10px;">
                            <a
                                href="<?php echo e($imageUrl); ?>"
                                target="_blank"
                                rel="noopener"
                                class="g-btn g-btn-ghost g-btn-block"
                            >
                                Open Full Screenshot
                            </a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/reports/show.blade.php ENDPATH**/ ?>