<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        <?php echo e($isBulkExport ? 'Selected GAgent Reports' : 'GAgent UX Friction Report'); ?>

    </title>

    <style>
        @page {
            margin: 28px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        h1,
        h2,
        h3,
        h4 {
            margin-top: 0;
            color: #10233f;
        }

        h1 {
            margin-bottom: 6px;
            font-size: 22px;
        }

        h2 {
            margin-bottom: 8px;
            font-size: 16px;
        }

        h3 {
            margin-bottom: 7px;
            font-size: 12px;
        }

        p {
            margin: 4px 0 8px;
        }

        .report-wrapper {
            width: 100%;
        }

        .report-page-break {
            page-break-after: always;
        }

        .report-header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 3px solid #1565c0;
        }

        .system-name {
            margin-bottom: 4px;
            color: #1565c0;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .muted {
            color: #687386;
        }

        .section {
            margin-bottom: 14px;
            padding: 11px;
            border: 1px solid #d9e1ec;
            border-radius: 5px;
        }

        .section-title {
            margin-bottom: 9px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d9e1ec;
            font-size: 13px;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 12px;
            color: #ffffff;
            font-weight: bold;
        }

        .status-low {
            background: #2e7d32;
        }

        .status-medium {
            background: #ed8b00;
        }

        .status-high {
            background: #c62828;
        }

        .status-neutral {
            background: #607080;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px 7px;
            border: 1px solid #d9e1ec;
            text-align: left;
            vertical-align: top;
        }

        th {
            width: 34%;
            background: #f1f5fa;
            color: #26384f;
            font-weight: bold;
        }

        .summary-table td {
            width: 33.33%;
            padding: 10px;
            text-align: center;
        }

        .summary-label {
            margin-bottom: 4px;
            color: #687386;
            font-size: 8px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #10233f;
            font-size: 17px;
            font-weight: bold;
        }

        ul {
            margin: 5px 0 5px 18px;
            padding: 0;
        }

        li {
            margin-bottom: 4px;
        }

        .small {
            font-size: 8px;
        }

        .evidence-image {
            width: 100%;
            max-height: 300px;
            margin-top: 7px;
            object-fit: contain;
            border: 1px solid #d9e1ec;
        }

        .evidence-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .footer-note {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #d9e1ec;
            color: #687386;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>

<body>
<?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $run = $report->testRun;
        $project = $run?->project;
        $metric = $run?->uxMetric;
        $final = $run?->finalFrictionResult;
        $main = $run?->mainGAgentResult;
        $baseline = $run?->baselineResult;

        $level = $final?->friction_level ?? 'Not predicted';

        $statusClass = match ($level) {
            'Low' => 'status-low',
            'Medium' => 'status-medium',
            'High' => 'status-high',
            default => 'status-neutral',
        };

        $confidence = $final?->confidence_score !== null
            ? number_format($final->confidence_score * 100, 1) . '%'
            : 'N/A';

        $recommendations = $main?->recommendations ?? [];

        if (is_string($recommendations)) {
            $decodedRecommendations = json_decode(
                $recommendations,
                true
            );

            $recommendations = is_array($decodedRecommendations)
                ? $decodedRecommendations
                : [$recommendations];
        }

        if (!is_array($recommendations)) {
            $recommendations = [];
        }

        $llmRecommendations = $report->llm_recommendations ?? [];

        if (!is_array($llmRecommendations)) {
            $llmRecommendations = [];
        }

        $screenshots = $run?->screenshots ?? collect();
        $logs = $run?->interactionLogs ?? collect();

        $finalInputFeatures = $final?->input_features ?? [];

        if (is_string($finalInputFeatures)) {
            $finalInputFeatures = json_decode(
                $finalInputFeatures,
                true
            ) ?? [];
        }

        $auditScore = $finalInputFeatures[
            'average_severity_score'
        ] ?? null;

        if (
            $run?->flow_type === 'full_audit'
            && $auditScore !== null
        ) {
            $frictionScore = round(
                ((((float) $auditScore) - 1) / 2) * 100
            );

            $frictionScore = max(
                0,
                min(100, $frictionScore)
            );
        } else {
            $frictionScore = $final?->confidence_score !== null
                ? round($final->confidence_score * 100)
                : 0;
        }
    ?>

    <div class="report-wrapper">
        <div class="report-header">
            <div class="system-name">
                GAgent Autonomous UX Mystery Shopper
            </div>

            <h1>
                <?php echo e($report->title ?? 'UX Friction Report'); ?>

            </h1>

            <div class="muted">
                Report ID: <?php echo e($report->id); ?>

                |
                Run: <?php echo e($run?->run_code ?? 'N/A'); ?>

                |
                Generated:
                <?php echo e(optional($report->generated_at)
                        ?->format('Y-m-d H:i')
                    ?? 'N/A'); ?>

            </div>
        </div>

        <div class="section">
            <div class="section-title">
                Executive Summary
            </div>

            <p>
                <?php echo e($report->summary
                    ?? 'No report summary is available.'); ?>

            </p>

            <table class="summary-table">
                <tr>
                    <td>
                        <div class="summary-label">
                            Friction Level
                        </div>

                        <span class="status <?php echo e($statusClass); ?>">
                            <?php echo e($level); ?>

                        </span>
                    </td>

                    <td>
                        <div class="summary-label">
                            Confidence
                        </div>

                        <div class="summary-value">
                            <?php echo e($confidence); ?>

                        </div>
                    </td>

                    <td>
                        <div class="summary-label">
                            Friction Score
                        </div>

                        <div class="summary-value">
                            <?php echo e($frictionScore); ?>/100
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                Project and Test Information
            </div>

            <table>
                <tr>
                    <th>Project</th>
                    <td><?php echo e($project?->name ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Project Description</th>
                    <td><?php echo e($project?->description ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Target Type</th>
                    <td>
                        <?php echo e($project?->target_type
                            ?? $run?->target_type
                            ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Target URL</th>
                    <td>
                        <?php echo e($run?->target_url
                            ?? $run?->page_url
                            ?? $project?->target_url
                            ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Platform</th>
                    <td><?php echo e($run?->platform ?? 'web'); ?></td>
                </tr>

                <tr>
                    <th>Flow Type</th>
                    <td><?php echo e($run?->flow_type ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Scenario Type</th>
                    <td>
                        <?php echo e($run?->scenario_type
                            ?? $run?->run_mode
                            ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Viewport</th>
                    <td><?php echo e($run?->viewport_type ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Network Condition</th>
                    <td>
                        <?php echo e($run?->network_condition ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Test Status</th>
                    <td><?php echo e($run?->status ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Started At</th>
                    <td>
                        <?php echo e(optional($run?->started_at)
                                ?->format('Y-m-d H:i:s')
                            ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Completed At</th>
                    <td>
                        <?php echo e(optional($run?->completed_at)
                                ?->format('Y-m-d H:i:s')
                            ?? 'N/A'); ?>

                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                UX Metric Evidence
            </div>

            <?php if(!$metric): ?>
                <p>No UX metrics are connected to this report.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Task Completed</th>
                        <td>
                            <?php echo e($metric->task_completed ? 'Yes' : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Completion Time</th>
                        <td>
                            <?php echo e($metric->completion_time !== null
                                    ? $metric->completion_time . ' seconds'
                                    : 'N/A'); ?>

                        </td>
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
                        <td>
                            <?php echo e($metric->unnecessary_clicks ?? 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Path Deviation Score</th>
                        <td>
                            <?php echo e($metric->path_deviation_score
                                ?? 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Page Load Time</th>
                        <td>
                            <?php echo e($metric->page_load_time_ms !== null
                                    ? $metric->page_load_time_ms . ' ms'
                                    : 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Screen Load Time</th>
                        <td>
                            <?php echo e($metric->screen_load_time_ms !== null
                                    ? $metric->screen_load_time_ms . ' ms'
                                    : 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Feedback Delay</th>
                        <td>
                            <?php echo e($metric->feedback_delay_ms !== null
                                    ? $metric->feedback_delay_ms . ' ms'
                                    : 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Interaction Response Time</th>
                        <td>
                            <?php echo e($metric->interaction_response_time_ms !== null
                                    ? $metric->interaction_response_time_ms . ' ms'
                                    : 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Layout Shift</th>
                        <td>
                            <?php echo e($metric->cumulative_layout_shift
                                ?? 'N/A'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Popup Detected</th>
                        <td>
                            <?php echo e($metric->popup_detected ? 'Yes' : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Cookie Banner Detected</th>
                        <td>
                            <?php echo e($metric->cookie_banner_detected
                                    ? 'Yes'
                                    : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Overlay Blocks Action</th>
                        <td>
                            <?php echo e((
                                    $metric->overlay_blocks_cta
                                    || $metric->overlay_blocks_action
                                )
                                    ? 'Yes'
                                    : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Timeout Occurred</th>
                        <td>
                            <?php echo e($metric->timeout_occurred
                                    ? 'Yes'
                                    : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>Crash Detected</th>
                        <td>
                            <?php echo e($metric->crash_detected
                                    ? 'Yes'
                                    : 'No'); ?>

                        </td>
                    </tr>

                    <tr>
                        <th>ANR Detected</th>
                        <td>
                            <?php echo e($metric->anr_detected
                                    ? 'Yes'
                                    : 'No'); ?>

                        </td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title">
                Machine-Learning Prediction
            </div>

            <table>
                <tr>
                    <th>Final Friction Level</th>
                    <td><?php echo e($level); ?></td>
                </tr>

                <tr>
                    <th>Final Confidence</th>
                    <td><?php echo e($confidence); ?></td>
                </tr>

                <tr>
                    <th>Prediction Source</th>
                    <td>
                        <?php echo e($final?->prediction_source ?? 'N/A'); ?>

                    </td>
                </tr>

                <tr>
                    <th>Main Model</th>
                    <td><?php echo e($main?->model_name ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Main Model Type</th>
                    <td><?php echo e($main?->model_type ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Baseline Model</th>
                    <td><?php echo e($baseline?->model_name ?? 'N/A'); ?></td>
                </tr>

                <tr>
                    <th>Baseline Result</th>
                    <td>
                        <?php echo e($baseline?->friction_level ?? 'N/A'); ?>

                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                Model Recommendations
            </div>

            <?php if(empty($recommendations)): ?>
                <p>No model recommendations are available.</p>
            <?php else: ?>
                <ul>
                    <?php $__currentLoopData = $recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <?php echo e(is_array($recommendation)
                                    ? json_encode($recommendation)
                                    : $recommendation); ?>

                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if($report->llm_generated_at): ?>
            <div class="section">
                <div class="section-title">
                    AI Explanation
                </div>

                <h3>Summary</h3>
                <p>
                    <?php echo e($report->llm_summary
                        ?? 'No AI summary is available.'); ?>

                </p>

                <h3>Why This Result Occurred</h3>
                <p>
                    <?php echo e($report->llm_explanation
                        ?? 'No AI explanation is available.'); ?>

                </p>

                <h3>Main Risk Reason</h3>
                <p>
                    <?php echo e($report->llm_risk_reason
                        ?? 'No AI risk reason is available.'); ?>

                </p>

                <h3>AI Improvement Suggestions</h3>

                <?php if(empty($llmRecommendations)): ?>
                    <p>
                        No AI improvement suggestions are available.
                    </p>
                <?php else: ?>
                    <ul>
                        <?php $__currentLoopData = $llmRecommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($recommendation); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>

                <p class="small muted">
                    Explanation model:
                    <?php echo e($report->llm_model_name ?? 'N/A'); ?>

                </p>
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">
                Interaction Evidence
            </div>

            <?php if($logs->isEmpty()): ?>
                <p>No interaction logs are available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Type</th>
                            <th style="width: 35%;">Label</th>
                            <th style="width: 25%;">Value</th>
                            <th style="width: 20%;">Time</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__currentLoopData = $logs->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <?php echo e($log->event_type ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <?php echo e($log->event_label ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <?php echo e($log->event_value ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <?php echo e(optional($log->created_at)
                                            ?->format('Y-m-d H:i:s')
                                        ?? 'N/A'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

                <?php if($logs->count() > 50): ?>
                    <p class="small muted">
                        Only the first 50 interaction logs are
                        displayed in the PDF.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title">
                Screenshot Evidence
            </div>

            <?php if($screenshots->isEmpty()): ?>
                <p>No screenshot evidence is available.</p>
            <?php else: ?>
                <?php $__currentLoopData = $screenshots->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screenshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $localImagePath = storage_path(
                            'app/public/' . $screenshot->file_path
                        );
                    ?>

                    <div class="evidence-block">
                        <strong>
                            <?php echo e($screenshot->label
                                ?? 'Screenshot Evidence'); ?>

                        </strong>

                        <div class="small muted">
                            <?php echo e($screenshot->file_path); ?>

                        </div>

                        <?php if(is_file($localImagePath)): ?>
                            <img
                                class="evidence-image"
                                src="<?php echo e($localImagePath); ?>"
                                alt="Screenshot evidence"
                            >
                        <?php else: ?>
                            <p class="small muted">
                                The screenshot file could not be
                                found in Laravel storage.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($screenshots->count() > 4): ?>
                    <p class="small muted">
                        Only the first four screenshots are
                        included to keep the PDF file manageable.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title">
                Conclusion
            </div>

            <p>
                <?php echo e($report->conclusion
                    ?? 'No conclusion is available.'); ?>

            </p>
        </div>

        <div class="footer-note">
            Generated by GAgent — Autonomous AI-Driven
            Mystery Shopper System for UX Friction Detection.
        </div>
    </div>

    <?php if(!$loop->last): ?>
        <div class="report-page-break"></div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/reports/pdf/report.blade.php ENDPATH**/ ?>