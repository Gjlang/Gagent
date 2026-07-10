<?php $__env->startSection('title', 'Test Run Detail'); ?>
<?php $__env->startSection('kicker', 'Automation Run Analysis'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $project = $testRun->project;
    $metric = $testRun->uxMetric;
    $final = $testRun->finalFrictionResult;
    $main = $testRun->mainGAgentResult;
    $baseline = $testRun->baselineResult;
    $report = $testRun->report ?? null;

    $level = $final?->friction_level ?? 'Not predicted';

    $badgeClass = match ($level) {
        'Low' => 'badge-low',
        'Medium' => 'badge-medium',
        'High' => 'badge-high',
        default => 'badge-neutral',
    };

    $status = strtolower($testRun->status ?? 'unknown');
    $statusClass = 'g-status-' . preg_replace('/[^a-z0-9]+/', '-', $status);

    $finalConfidence = $final?->confidence_score !== null
        ? number_format($final->confidence_score * 100, 1) . '%'
        : 'N/A';

    $mainConfidence = $main?->confidence_score !== null
        ? number_format($main->confidence_score * 100, 1) . '%'
        : 'N/A';

    $baselineConfidence = $baseline?->confidence_score !== null
        ? number_format($baseline->confidence_score * 100, 1) . '%'
        : 'N/A';

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

    $screenshots = $testRun->screenshots ?? collect();
    $logs = $testRun->interactionLogs ?? collect();

    $frictionScore = $final?->confidence_score !== null
        ? round($final->confidence_score * 100)
        : 0;
?>

<div class="g-page-header">
    <div>
        <h2><?php echo e($testRun->run_code ?? 'Test Run Detail'); ?></h2>
        <p>
            Review the saved UX metrics, AI predictions, baseline comparison, screenshots, logs, and report generation actions for this test run.
        </p>
    </div>

    <div class="g-actions">
        <a class="g-btn" href="<?php echo e(route('test-runs.index')); ?>">Back to Test Runs</a>

        <?php if($report): ?>
            <a class="g-btn g-btn-primary" href="<?php echo e(route('reports.show', $report)); ?>">View Report</a>
        <?php endif; ?>
    </div>
</div>

<div class="g-card" style="margin-bottom: 16px;">
    <div class="g-actions">
        <?php if(\Illuminate\Support\Facades\Route::has('test-runs.predict-gagent')): ?>
            <form method="POST" action="<?php echo e(route('test-runs.predict-gagent', $testRun)); ?>">
                <?php echo csrf_field(); ?>
                <button class="g-btn g-btn-primary" type="submit">Run Main GAgent Prediction</button>
            </form>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('test-runs.predict-baseline')): ?>
            <form method="POST" action="<?php echo e(route('test-runs.predict-baseline', $testRun)); ?>">
                <?php echo csrf_field(); ?>
                <button class="g-btn" type="submit">Run Baseline Prediction</button>
            </form>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('reports.generate')): ?>
            <form method="POST" action="<?php echo e(route('reports.generate', $testRun)); ?>">
                <?php echo csrf_field(); ?>
                <button class="g-btn g-btn-dark" type="submit">Generate Report</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="g-grid g-grid-4">
    <div class="g-metric-card">
        <div class="g-metric-label">Final Friction</div>
        <div style="margin-top: 16px;">
            <span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span>
        </div>
        <div class="g-metric-sub">Final system decision</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Final Confidence</div>
        <div class="g-metric-value"><?php echo e($finalConfidence); ?></div>
        <div class="g-metric-sub">Prediction certainty</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Run Status</div>
        <div style="margin-top: 16px;">
            <span class="g-status-badge <?php echo e($statusClass); ?>"><?php echo e($testRun->status ?? 'N/A'); ?></span>
        </div>
        <div class="g-metric-sub">Automation state</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Report</div>
        <div class="g-metric-value" style="font-size: 24px;">
            <?php echo e($report ? 'Ready' : 'N/A'); ?>

        </div>
        <div class="g-metric-sub">Generated report status</div>
    </div>
</div>

<div class="g-layout-2-1" style="margin-top: 16px;">
    <div class="g-stack">

        <div class="g-card">
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">Run Overview</div>
                    <h3 style="margin-top: 6px;">Test Run Metadata</h3>
                </div>
                <span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span>
            </div>

            <div class="g-table-wrap" style="margin-top: 14px;">
                <table class="g-table">
                    <tbody>
                        <tr>
                            <th>Run Code</th>
                            <td><?php echo e($testRun->run_code ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Project</th>
                            <td><?php echo e($project?->name ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Target Type</th>
                            <td><?php echo e($project?->target_type ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Flow</th>
                            <td><?php echo e($testRun->flow_type ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Scenario</th>
                            <td><?php echo e($testRun->scenario_type ?? $testRun->run_mode ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Viewport</th>
                            <td><?php echo e($testRun->viewport_type ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Page URL</th>
                            <td><?php echo e($testRun->page_url ?? $project?->target_url ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td><?php echo e(optional($testRun->created_at)->format('Y-m-d H:i') ?? 'N/A'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Main GAgent Prediction</h3>

                <?php if(!$main): ?>
                    <div class="g-empty">
                        <strong>No main GAgent prediction yet.</strong>
                        Click “Run Main GAgent Prediction” to generate the final system prediction.
                    </div>
                <?php else: ?>
                    <div class="g-kv">
                        <div class="g-kv-row">
                            <span>Model</span>
                            <span><?php echo e($main->model_name ?? 'N/A'); ?></span>
                        </div>
                        <div class="g-kv-row">
                            <span>Type</span>
                            <span><?php echo e($main->model_type ?? 'N/A'); ?></span>
                        </div>
                        <div class="g-kv-row">
                            <span>Friction</span>
                            <span>
                                <span class="g-badge <?php echo e(match ($main->friction_level ?? '') {
                                    'Low' => 'badge-low',
                                    'Medium' => 'badge-medium',
                                    'High' => 'badge-high',
                                    default => 'badge-neutral',
                                }); ?>">
                                    <?php echo e($main->friction_level ?? 'N/A'); ?>

                                </span>
                            </span>
                        </div>
                        <div class="g-kv-row">
                            <span>Confidence</span>
                            <span><?php echo e($mainConfidence); ?></span>
                        </div>
                    </div>

                    <h4 style="margin-top: 16px;">Class Probabilities</h4>
                    <pre class="g-console"><?php echo e(json_encode($mainProbabilities, JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
            </div>

            <div class="g-card">
                <h3>Baseline Comparison</h3>

                <?php if(!$baseline): ?>
                    <div class="g-empty">
                        <strong>No baseline prediction yet.</strong>
                        Baseline is optional and used only for comparison.
                    </div>
                <?php else: ?>
                    <div class="g-kv">
                        <div class="g-kv-row">
                            <span>Model</span>
                            <span><?php echo e($baseline->model_name ?? 'N/A'); ?></span>
                        </div>
                        <div class="g-kv-row">
                            <span>Type</span>
                            <span><?php echo e($baseline->model_type ?? 'N/A'); ?></span>
                        </div>
                        <div class="g-kv-row">
                            <span>Friction</span>
                            <span>
                                <span class="g-badge <?php echo e(match ($baseline->friction_level ?? '') {
                                    'Low' => 'badge-low',
                                    'Medium' => 'badge-medium',
                                    'High' => 'badge-high',
                                    default => 'badge-neutral',
                                }); ?>">
                                    <?php echo e($baseline->friction_level ?? 'N/A'); ?>

                                </span>
                            </span>
                        </div>
                        <div class="g-kv-row">
                            <span>Confidence</span>
                            <span><?php echo e($baselineConfidence); ?></span>
                        </div>
                    </div>

                    <?php if($baselineProbabilities): ?>
                        <h4 style="margin-top: 16px;">Class Probabilities</h4>
                        <pre class="g-console"><?php echo e(json_encode($baselineProbabilities, JSON_PRETTY_PRINT)); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="g-card">
            <h3>UX Metrics</h3>

            <?php if(!$metric): ?>
                <div class="g-empty">
                    <strong>No UX metrics available.</strong>
                    This test run has no linked UX metric row.
                </div>
            <?php else: ?>
                <div class="g-table-wrap">
                    <table class="g-table">
                        <tbody>
                            <tr>
                                <th>Task Completed</th>
                                <td><?php echo e(($metric->task_completed ?? false) ? 'Yes' : 'No'); ?></td>
                            </tr>
                            <tr>
                                <th>Task Failed</th>
                                <td><?php echo e(($metric->task_failed ?? false) ? 'Yes' : 'No'); ?></td>
                            </tr>
                            <tr>
                                <th>Completion Time</th>
                                <td><?php echo e($metric->completion_time ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Click Count</th>
                                <td><?php echo e($metric->click_count ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Scroll Count</th>
                                <td><?php echo e($metric->scroll_count ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Keyboard Count</th>
                                <td><?php echo e($metric->keyboard_count ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Retry Count</th>
                                <td><?php echo e($metric->retry_count ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error Count</th>
                                <td><?php echo e($metric->error_count ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Failed Clicks</th>
                                <td><?php echo e($metric->failed_clicks ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Unnecessary Clicks</th>
                                <td><?php echo e($metric->unnecessary_clicks ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Path Deviation Score</th>
                                <td><?php echo e($metric->path_deviation_score ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Page Load Time</th>
                                <td><?php echo e($metric->page_load_time_ms !== null ? $metric->page_load_time_ms . ' ms' : 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Feedback Delay</th>
                                <td><?php echo e($metric->feedback_delay_ms !== null ? $metric->feedback_delay_ms . ' ms' : 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Cumulative Layout Shift</th>
                                <td><?php echo e($metric->cumulative_layout_shift ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Popup Detected</th>
                                <td><?php echo e(($metric->popup_detected ?? false) ? 'Yes' : 'No'); ?></td>
                            </tr>
                            <tr>
                                <th>Cookie Banner Detected</th>
                                <td><?php echo e(($metric->cookie_banner_detected ?? false) ? 'Yes' : 'No'); ?></td>
                            </tr>
                            <tr>
                                <th>Overlay Blocks CTA</th>
                                <td><?php echo e(($metric->overlay_blocks_cta ?? false) ? 'Yes' : 'No'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Screenshot Evidence</h3>

            <?php if($screenshots->isEmpty()): ?>
                <div class="g-empty">
                    <strong>No screenshots available.</strong>
                    Screenshots will appear after Playwright or Appium saves capture evidence.
                </div>
            <?php else: ?>
                <div class="g-evidence-grid">
                    <?php $__currentLoopData = $screenshots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screenshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $path = $screenshot->file_path ?? '';
                            $cleanPath = ltrim($path, '/');

                            if ($path && str_starts_with($path, 'http')) {
                                $imageSrc = $path;
                            } elseif ($path && str_starts_with($cleanPath, 'storage/')) {
                                $imageSrc = asset($cleanPath);
                            } elseif ($path) {
                                $imageSrc = asset('storage/' . $cleanPath);
                            } else {
                                $imageSrc = null;
                            }
                        ?>

                        <div class="g-evidence-card">
                            <?php if($imageSrc): ?>
                                <img class="g-screenshot-img" src="<?php echo e($imageSrc); ?>" alt="<?php echo e($screenshot->label ?? 'Screenshot evidence'); ?>">
                            <?php else: ?>
                                <div class="g-evidence-visual">No Image</div>
                            <?php endif; ?>

                            <div class="g-evidence-body">
                                <strong><?php echo e($screenshot->label ?? 'Screenshot Evidence'); ?></strong>
                                <p class="g-muted g-small">
                                    <?php echo e($screenshot->file_path ?? 'No file path available'); ?>

                                </p>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="g-card">
            <h3>Interaction Logs</h3>

            <?php if($logs->isEmpty()): ?>
                <div class="g-empty">
                    <strong>No interaction logs available.</strong>
                    Logs will appear here after the automation runner stores events.
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
    </div>

    <aside class="g-stack">
        <div class="g-panel">
            <div class="g-soft-label">Final GAgent Result</div>
            <div class="g-metric-value" style="font-size: 42px;">
                <?php echo e($frictionScore); ?>

                <span style="font-size: 15px;">/100</span>
            </div>

            <div style="margin: 12px 0;">
                <span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span>
            </div>

            <div class="g-progress <?php echo e($level === 'High' ? 'danger' : ($level === 'Medium' ? 'warn' : 'safe')); ?>">
                <span style="width: <?php echo e($frictionScore); ?>%;"></span>
            </div>

            <p class="g-muted" style="margin-top: 12px;">
                Main GAgent result is the final system decision.
            </p>
        </div>

        <div class="g-panel">
            <h3>Performance Metrics</h3>

            <?php if(!$metric): ?>
                <div class="g-empty">
                    <strong>No metric data.</strong>
                    Nothing to visualise yet.
                </div>
            <?php else: ?>
                <div class="g-kv">
                    <div class="g-kv-row">
                        <span>Completion Time</span>
                        <span><?php echo e($metric->completion_time ?? 'N/A'); ?></span>
                    </div>
                    <div class="g-kv-row">
                        <span>Clicks</span>
                        <span><?php echo e($metric->click_count ?? 'N/A'); ?></span>
                    </div>
                    <div class="g-kv-row">
                        <span>Retries</span>
                        <span><?php echo e($metric->retry_count ?? 'N/A'); ?></span>
                    </div>
                    <div class="g-kv-row">
                        <span>Errors</span>
                        <span><?php echo e($metric->error_count ?? 'N/A'); ?></span>
                    </div>
                    <div class="g-kv-row">
                        <span>Failed Clicks</span>
                        <span><?php echo e($metric->failed_clicks ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <div class="g-split-row g-small">
                        <strong>Retry Pressure</strong>
                        <span><?php echo e($metric->retry_count ?? 0); ?></span>
                    </div>
                    <div class="g-progress warn">
                        <span style="width: <?php echo e(min(100, (($metric->retry_count ?? 0) / 10) * 100)); ?>%;"></span>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <div class="g-split-row g-small">
                        <strong>Error Pressure</strong>
                        <span><?php echo e($metric->error_count ?? 0); ?></span>
                    </div>
                    <div class="g-progress danger">
                        <span style="width: <?php echo e(min(100, (($metric->error_count ?? 0) / 10) * 100)); ?>%;"></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="g-insight-card" style="background: #06172b; color: #dff2ff;">
            <div class="g-soft-label" style="color: #7dd3fc;">AI Recommendations</div>
            <h3 style="margin-top: 7px; color: white;">Suggested Fixes</h3>

            <?php if(empty($recommendations)): ?>
                <p style="color: #cbd5e1;">
                    No recommendations available. Run the Main GAgent prediction again if needed.
                </p>
            <?php else: ?>
                <ul style="padding-left: 18px; line-height: 1.7;">
                    <?php $__currentLoopData = $recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e(is_array($recommendation) ? json_encode($recommendation) : $recommendation); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="g-panel">
            <h3>Report Action</h3>

            <?php if($report): ?>
                <p class="g-muted">A report has already been generated for this test run.</p>
                <a class="g-btn g-btn-primary g-btn-block" href="<?php echo e(route('reports.show', $report)); ?>">Open Report</a>
            <?php else: ?>
                <p class="g-muted">Generate a report after prediction results are available.</p>

                <?php if(\Illuminate\Support\Facades\Route::has('reports.generate')): ?>
                    <form method="POST" action="<?php echo e(route('reports.generate', $testRun)); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="g-btn g-btn-primary g-btn-block" type="submit">Generate Report</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/test-runs/show.blade.php ENDPATH**/ ?>