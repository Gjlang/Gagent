<?php $__env->startSection('title', 'Project Comparison'); ?>
<?php $__env->startSection('kicker', 'Improvement Tracking'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $statusClasses = [
        'Improved' => 'comparison-good',
        'Partially Improved' => 'comparison-warning',
        'No Significant Change' => 'comparison-neutral',
        'Regressed' => 'comparison-bad',
    ];

    $resultClass = $comparison
        ? ($statusClasses[$comparison['overall_status']]
            ?? 'comparison-neutral')
        : 'comparison-neutral';

    $formatMetricValue = function (
        $value,
        string $type,
        string $unit = ''
    ) {
        if ($type === 'boolean') {
            return $value ? 'Yes' : 'No';
        }

        if (!is_numeric($value)) {
            return 'N/A';
        }

        $formatted = number_format(
            (float) $value,
            abs((float) $value - round((float) $value)) > 0.001
                ? 2
                : 0
        );

        return trim($formatted . ' ' . $unit);
    };

    $screenshotSource = function ($screenshot) {
        if (!$screenshot) {
            return null;
        }

        $path = $screenshot->file_path ?? '';

        if (!$path) {
            return null;
        }

        $cleanPath = ltrim($path, '/');

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    };
?>

<style>
    .comparison-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
    }

    .comparison-field {
        display: grid;
        gap: 8px;
    }

    .comparison-field label {
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    .comparison-select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font: inherit;
    }

    .comparison-summary {
        padding: 22px;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #ffffff;
    }

    .comparison-good {
        border-left: 5px solid #16a34a;
    }

    .comparison-warning {
        border-left: 5px solid #d97706;
    }

    .comparison-neutral {
        border-left: 5px solid #64748b;
    }

    .comparison-bad {
        border-left: 5px solid #dc2626;
    }

    .comparison-arrow {
        color: #64748b;
        font-size: 28px;
        font-weight: 700;
        text-align: center;
    }

    .comparison-delta-positive {
        color: #15803d;
        font-weight: 700;
    }

    .comparison-delta-negative {
        color: #b91c1c;
        font-weight: 700;
    }

    .comparison-delta-neutral {
        color: #64748b;
        font-weight: 700;
    }

    .comparison-list {
        display: grid;
        gap: 10px;
        margin: 0;
        padding-left: 20px;
    }

    .comparison-evidence-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 14px;
    }

    .comparison-evidence {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        background: #ffffff;
    }

    .comparison-evidence img {
        display: block;
        width: 100%;
        height: 280px;
        object-fit: contain;
        background: #f8fafc;
    }

    .comparison-evidence-body {
        padding: 14px;
    }

    .comparison-missing-image {
        display: grid;
        min-height: 280px;
        place-items: center;
        background: #f8fafc;
        color: #64748b;
    }

    @media (max-width: 900px) {
        .comparison-form {
            grid-template-columns: 1fr;
        }

        .comparison-evidence-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="g-page-header">
    <div>
        <h2><?php echo e($project->name); ?> Improvement Comparison</h2>
        <p>
            Compare two completed test runs from the same
            project to identify UX improvements and regressions.
        </p>
    </div>

    <div class="g-actions">
        <a
            class="g-btn"
            href="<?php echo e(route('projects.show', $project)); ?>"
        >
            Back to Project
        </a>
    </div>
</div>

<?php if($errors->any()): ?>
    <div class="g-card" style="margin-bottom: 16px; border-left: 5px solid #dc2626;">
        <strong>Comparison could not be completed.</strong>

        <ul style="margin-bottom: 0;">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="g-card" style="margin-bottom: 16px; border-left: 5px solid #dc2626;">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if(!isset($savedComparison)): ?>
<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 16px;">
        <div>
            <h3>Select Test Runs</h3>
            <p class="g-muted">
                Only completed runs with UX metrics and a final
                friction prediction are available.
            </p>
        </div>

        <span class="g-badge badge-final">
            <?php echo e($eligibleRuns->count()); ?> eligible runs
        </span>
    </div>

    <?php if($eligibleRuns->count() < 2): ?>
        <div class="g-empty">
            <strong>At least two eligible test runs are required.</strong>
            Complete another test run and generate its final friction
            prediction before using this comparison.
        </div>
    <?php else: ?>
        <form
            method="GET"
            action="<?php echo e(route('projects.comparison', $project)); ?>"
            class="comparison-form"
        >
            <div class="comparison-field">
                <label for="before_run">
                    Before Test Run
                </label>

                <select
                    id="before_run"
                    name="before_run"
                    class="comparison-select"
                    required
                >
                    <option value="">
                        Select the older test run
                    </option>

                    <?php $__currentLoopData = $eligibleRuns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($run->id); ?>"
                            <?php if(
                                (string) old(
                                    'before_run',
                                    request('before_run')
                                )
                                === (string) $run->id
                            ): echo 'selected'; endif; ?>
                        >
                            <?php echo e($run->run_code); ?>

                            —
                            <?php echo e(ucfirst($run->platform ?? 'web')); ?>

                            —
                            <?php echo e($run->finalFrictionResult?->friction_level ?? 'Not predicted'); ?>

                            —
                            <?php echo e(optional($run->completed_at)->format('Y-m-d H:i')); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="comparison-field">
                <label for="after_run">
                    After Test Run
                </label>

                <select
                    id="after_run"
                    name="after_run"
                    class="comparison-select"
                    required
                >
                    <option value="">
                        Select the newer test run
                    </option>

                    <?php $__currentLoopData = $eligibleRuns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($run->id); ?>"
                            <?php if(
                                (string) old(
                                    'after_run',
                                    request('after_run')
                                )
                                === (string) $run->id
                            ): echo 'selected'; endif; ?>
                        >
                            <?php echo e($run->run_code); ?>

                            —
                            <?php echo e(ucfirst($run->platform ?? 'web')); ?>

                            —
                            <?php echo e($run->finalFrictionResult?->friction_level ?? 'Not predicted'); ?>

                            —
                            <?php echo e(optional($run->completed_at)->format('Y-m-d H:i')); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <button
                type="submit"
                class="g-btn g-btn-primary"
            >
                Compare Runs
            </button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if($comparison): ?>
    <div
        class="comparison-summary <?php echo e($resultClass); ?>"
        style="margin-top: 16px;"
    >
        <div class="g-split-row">
            <div>
                <div class="g-soft-label">
                    Overall Comparison Result
                </div>

                <h2 style="margin: 6px 0;">
                    <?php echo e($comparison['overall_status']); ?>

                </h2>

                <p style="margin-bottom: 0;">
                    <?php echo e($comparison['summary']); ?>

                </p>
            </div>

            <span class="g-badge badge-final">
                Same Project Confirmed
            </span>
        </div>
    </div>

    <?php if(isset($savedComparison)): ?>
        <div
            class="g-card"
            style="margin-top: 16px;"
        >
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">
                        Explanation Layer Only
                    </div>

                    <h3 style="margin-top: 6px;">
                        AI Comparison Explanation
                    </h3>

                    <p
                        class="g-muted"
                        style="margin-top: 7px;"
                    >
                        Ollama explains the saved before-and-after
                        comparison. It does not change the machine-
                        learning predictions, metric values, or UX
                        scores.
                    </p>
                </div>

                <form
                    method="POST"
                    action="<?php echo e(route(
                            'comparisons.generate-explanation',
                            $savedComparison
                        )); ?>"
                >
                    <?php echo csrf_field(); ?>

                    <button
                        type="submit"
                        class="g-btn g-btn-primary"
                    >
                        <?php echo e($savedComparison->llm_generated_at
                                ? 'Regenerate AI Explanation'
                                : 'Generate AI Explanation'); ?>

                    </button>
                </form>
            </div>

            <?php if($savedComparison->llm_generated_at): ?>
                <div
                    class="g-grid g-grid-2"
                    style="margin-top: 18px;"
                >
                    <div class="g-panel">
                        <div class="g-soft-label">
                            Overall Summary
                        </div>

                        <p style="margin-top: 8px;">
                            <?php echo e($savedComparison->llm_summary
                                ?? 'No summary was returned.'); ?>

                        </p>

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Assessment
                        </div>

                        <p style="margin-top: 8px;">
                            <?php echo e($savedComparison->llm_assessment
                                ?? 'No assessment was returned.'); ?>

                        </p>
                    </div>

                    <div class="g-panel">
                        <div class="g-soft-label">
                            Key Improvements
                        </div>

                        <?php $__empty_1 = true; $__currentLoopData = $savedComparison->llm_improvements ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li><?php echo e($item); ?></li>
                            </ul>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="g-muted">
                                No specific improvements were identified.
                            </p>
                        <?php endif; ?>

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Remaining Risks
                        </div>

                        <?php $__empty_1 = true; $__currentLoopData = $savedComparison->llm_regressions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li><?php echo e($item); ?></li>
                            </ul>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="g-muted">
                                No measured regressions were identified.
                            </p>
                        <?php endif; ?>

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Recommended Next Actions
                        </div>

                        <?php $__empty_1 = true; $__currentLoopData = $savedComparison->llm_next_actions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li><?php echo e($item); ?></li>
                            </ul>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="g-muted">
                                No additional next actions were returned.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div
                    class="g-kv"
                    style="margin-top: 16px;"
                >
                    <div class="g-kv-row">
                        <span>Provider</span>
                        <span>
                            <?php echo e($savedComparison->llm_provider
                                ?? 'N/A'); ?>

                        </span>
                    </div>

                    <div class="g-kv-row">
                        <span>Model</span>
                        <span>
                            <?php echo e($savedComparison->llm_model
                                ?? 'N/A'); ?>

                        </span>
                    </div>

                    <div class="g-kv-row">
                        <span>Generated</span>
                        <span>
                            <?php echo e(optional(
                                    $savedComparison->llm_generated_at
                                )->format('Y-m-d H:i')
                                ?? 'N/A'); ?>

                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div
                    class="g-empty"
                    style="margin-top: 16px;"
                >
                    <strong>
                        No AI comparison explanation generated.
                    </strong>

                    Click “Generate AI Explanation” to explain
                    the existing comparison.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div
        class="g-grid g-grid-4"
        style="margin-top: 16px;"
    >
        <div class="g-metric-card">
            <div class="g-metric-label">
                Before Friction
            </div>

            <div
                class="g-metric-value"
                style="font-size: 28px;"
            >
                <?php echo e($comparison['friction_comparison']['before']); ?>

            </div>

            <div class="g-metric-sub">
                <?php echo e($comparison['before']->run_code); ?>

            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                After Friction
            </div>

            <div
                class="g-metric-value"
                style="font-size: 28px;"
            >
                <?php echo e($comparison['friction_comparison']['after']); ?>

            </div>

            <div class="g-metric-sub">
                <?php echo e($comparison['after']->run_code); ?>

            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                Before UX Score
            </div>

            <div class="g-metric-value">
                <?php echo e($comparison['before_score']); ?>

            </div>

            <div class="g-metric-sub">
                Derived score out of 100
            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                After UX Score
            </div>

            <div class="g-metric-value">
                <?php echo e($comparison['after_score']); ?>

            </div>

            <div class="g-metric-sub">
                <?php if($comparison['score_difference'] > 0): ?>
                    +<?php echo e($comparison['score_difference']); ?>

                    points improvement
                <?php elseif($comparison['score_difference'] < 0): ?>
                    <?php echo e($comparison['score_difference']); ?>

                    points
                <?php else: ?>
                    No score change
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <div class="g-split-row">
            <div>
                <h3>Metric Comparison</h3>
                <p class="g-muted">
                    Green indicates improvement, red indicates
                    regression, and grey indicates no change.
                </p>
            </div>

            <div class="g-actions">
                <span class="g-badge badge-low">
                    <?php echo e(count($comparison['improved_metrics'])); ?>

                    improved
                </span>

                <span class="g-badge badge-high">
                    <?php echo e(count($comparison['worsened_metrics'])); ?>

                    worsened
                </span>
            </div>
        </div>

        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Difference</th>
                        <th>Result</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $__currentLoopData = $comparison['metrics']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $metric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $deltaClass = match ($metric['status']) {
                                'improved' => 'comparison-delta-positive',
                                'worsened' => 'comparison-delta-negative',
                                default => 'comparison-delta-neutral',
                            };

                            $statusText = ucfirst(
                                $metric['status']
                            );
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?php echo e($metric['label']); ?>

                                </strong>
                            </td>

                            <td>
                                <?php echo e($formatMetricValue(
                                        $metric['before'],
                                        $metric['type'],
                                        $metric['unit']
                                    )); ?>

                            </td>

                            <td>
                                <?php echo e($formatMetricValue(
                                        $metric['after'],
                                        $metric['type'],
                                        $metric['unit']
                                    )); ?>

                            </td>

                            <td>
                                <?php if(
                                    $metric['type'] === 'number'
                                    && $metric['difference'] !== null
                                ): ?>
                                    <?php echo e($metric['difference'] > 0
                                            ? '+'
                                            : ''); ?><?php echo e(number_format(
                                            $metric['difference'],
                                            2
                                        )); ?>

                                    <?php echo e($metric['unit']); ?>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td class="<?php echo e($deltaClass); ?>">
                                <?php echo e($statusText); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <div
        class="g-layout-2-1"
        style="margin-top: 16px;"
    >
        <div class="g-card">
            <h3>Recommendation Changes</h3>

            <div
                class="g-grid"
                style="grid-template-columns: repeat(3, minmax(0, 1fr));"
            >
                <div>
                    <h4>Resolved Recommendations</h4>

                    <?php if(
                        empty(
                            $comparison['resolved_recommendations']
                        )
                    ): ?>
                        <p class="g-muted">
                            No previous recommendations were removed.
                        </p>
                    <?php else: ?>
                        <ul class="comparison-list">
                            <?php $__currentLoopData = $comparison['resolved_recommendations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($recommendation); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div>
                    <h4>Remaining Recommendations</h4>

                    <?php if(
                        empty(
                            $comparison['remaining_recommendations']
                        )
                    ): ?>
                        <p class="g-muted">
                            No recommendations remained unchanged.
                        </p>
                    <?php else: ?>
                        <ul class="comparison-list">
                            <?php $__currentLoopData = $comparison['remaining_recommendations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($recommendation); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div>
                    <h4>New Recommendations</h4>

                    <?php if(
                        empty(
                            $comparison['new_recommendations']
                        )
                    ): ?>
                        <p class="g-muted">
                            No new recommendations were introduced.
                        </p>
                    <?php else: ?>
                        <ul class="comparison-list">
                            <?php $__currentLoopData = $comparison['new_recommendations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($recommendation); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <h3>Run Information</h3>

            <div class="g-kv">
                <div class="g-kv-row">
                    <span>Project</span>
                    <span><?php echo e($project->name); ?></span>
                </div>

                <div class="g-kv-row">
                    <span>Platform</span>
                    <span>
                        <?php echo e(ucfirst($comparison['before']->platform ?? 'web')); ?>

                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Before</span>
                    <span>
                        <?php echo e($comparison['before']->run_code); ?>

                    </span>
                </div>

                <div class="g-kv-row">
                    <span>After</span>
                    <span>
                        <?php echo e($comparison['after']->run_code); ?>

                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Score Difference</span>
                    <span>
                        <?php echo e($comparison['score_difference'] > 0
                                ? '+'
                                : ''); ?><?php echo e($comparison['score_difference']); ?>

                    </span>
                </div>
            </div>
        </aside>
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <h3>Before and After Screenshot Evidence</h3>

        <?php if(
            empty(
                $comparison['screenshot_pairs']
            )
        ): ?>
            <div class="g-empty">
                <strong>No screenshot evidence available.</strong>
                Screenshots will appear when either test run contains
                saved Playwright or Appium evidence.
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $comparison['screenshot_pairs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pair): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $beforeSource = $screenshotSource(
                        $pair['before']
                    );

                    $afterSource = $screenshotSource(
                        $pair['after']
                    );
                ?>

                <div style="margin-top: 20px;">
                    <h4>
                        <?php echo e($pair['key']); ?>

                    </h4>

                    <div class="comparison-evidence-row">
                        <div class="comparison-evidence">
                            <?php if($beforeSource): ?>
                                <img
                                    src="<?php echo e($beforeSource); ?>"
                                    alt="Before screenshot"
                                >
                            <?php else: ?>
                                <div class="comparison-missing-image">
                                    No matching before screenshot
                                </div>
                            <?php endif; ?>

                            <div class="comparison-evidence-body">
                                <strong>Before</strong>

                                <div class="g-muted g-small">
                                    <?php echo e($comparison['before']->run_code); ?>

                                </div>
                            </div>
                        </div>

                        <div class="comparison-evidence">
                            <?php if($afterSource): ?>
                                <img
                                    src="<?php echo e($afterSource); ?>"
                                    alt="After screenshot"
                                >
                            <?php else: ?>
                                <div class="comparison-missing-image">
                                    No matching after screenshot
                                </div>
                            <?php endif; ?>

                            <div class="comparison-evidence-body">
                                <strong>After</strong>

                                <div class="g-muted g-small">
                                    <?php echo e($comparison['after']->run_code); ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <h3>Score Explanation</h3>

        <p class="g-muted">
            The displayed UX score is a derived comparison score
            calculated from task completion, errors, failed clicks,
            retries, delays, blocking overlays, timeouts, crashes,
            and related UX metrics. It is separate from the machine
            learning confidence score and does not modify the AI
            prediction.
        </p>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/projects/comparison.blade.php ENDPATH**/ ?>