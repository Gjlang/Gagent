<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('kicker', 'GAgent AI UX Testing'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $severityCounts = $severityCounts ?? ['Low' => 0, 'Medium' => 0, 'High' => 0];
    $low = (int) ($severityCounts['Low'] ?? 0);
    $medium = (int) ($severityCounts['Medium'] ?? 0);
    $high = (int) ($severityCounts['High'] ?? 0);
    $severityTotal = max(1, $low + $medium + $high);
    $lowPct = round(($low / $severityTotal) * 100, 1);
    $mediumPct = round(($medium / $severityTotal) * 100, 1);
    $highPct = round(($high / $severityTotal) * 100, 1);
    $mediumStop = min(100, $lowPct + $mediumPct);
    $avgUxScore = $averageConfidence !== null ? round($averageConfidence * 100) : 0;
    $latestRuns = $recentTestRuns ?? collect();
    $latestReports = $recentReports ?? collect();
    $maxFlow = max(1, count($flowDistribution ?? []) ? max(array_values($flowDistribution)) : 1);
    $sortedFlowDistribution = collect($flowDistribution ?? [])
    ->sortDesc();

    $totalFlows = $sortedFlowDistribution->sum();

    $mostUsedFlow = $sortedFlowDistribution->keys()->first();

    // Build trend points from the SAME $latestRuns already passed to the view.
    // No new queries — just reordered/mapped for the chart.
    $trendRuns = $latestRuns->reverse()->values();
    $trendPoints = $trendRuns->map(function ($run) {
        $result = $run->finalFrictionResult;
        return [
            'label'    => $run->run_code ?? 'N/A',
            'date'     => optional($run->created_at)->format('M d') ?? 'N/A',
            'score'    => $result?->confidence_score !== null ? round($result->confidence_score * 100, 1) : null,
            'friction' => $result?->friction_level ?? 'Not predicted',
        ];
    })->filter(fn ($point) => $point['score'] !== null)->values();

    $currentScore  = $trendPoints->last()['score'] ?? null;
    $previousScore = $trendPoints->count() > 1 ? $trendPoints[$trendPoints->count() - 2]['score'] : null;
    $scoreDiff     = ($currentScore !== null && $previousScore !== null) ? round($currentScore - $previousScore, 1) : null;
?>

<div class="g-page-header">
    <div>
        <h2>Autonomous UX Friction Dashboard</h2>
        <p>Monitor project coverage, friction severity, report output, and recent AI prediction activity from the Laravel dashboard without changing the backend workflow.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn g-btn-primary" href="<?php echo e(route('live-tests.create')); ?>">Run Live Website Test</a>
        <a class="g-btn" href="<?php echo e(route('android-tests.create')); ?>">Android Test</a>
    </div>
</div>

<div class="g-grid g-grid-4">
    <div class="g-metric-card">
        <div class="g-metric-label">Total Projects</div>
        <div class="g-metric-value"><?php echo e(number_format($totalProjects ?? 0)); ?></div>
        <div class="g-metric-sub">Active UX test suites</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">UX Tests Run</div>
        <div class="g-metric-value"><?php echo e(number_format($totalTestRuns ?? 0)); ?></div>
        <div class="g-metric-sub">Web, dummy, and Android runs</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">High Friction</div>
        <div class="g-metric-value" style="color: var(--g-red);"><?php echo e(number_format($high)); ?></div>
        <div class="g-metric-sub">Action required</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">Avg UX Score</div>
        <div class="g-metric-value"><?php echo e($averageConfidence !== null ? $avgUxScore : 'N/A'); ?><span style="font-size: 15px;"><?php echo e($averageConfidence !== null ? ' /100' : ''); ?></span></div>
        <div class="g-metric-sub">Based on final confidence</div>
    </div>
</div>

<div class="g-layout-2-1" style="margin-top: 16px;">
    <div class="g-stack">
        <div class="g-card">
            <div class="g-trend-top">
                <div>
                    <div class="g-soft-label">UX Score Trends</div>
                    <h3 style="margin-top: 5px;">Aggregate performance across recent project versions</h3>
                </div>
                <span class="g-badge badge-final">Live Metrics</span>
            </div>

            <div class="g-trend-summary">
                <div class="g-trend-score-block">
                    <span class="g-trend-score-value"><?php echo e($currentScore !== null ? $currentScore : 'N/A'); ?><span class="g-trend-score-max"><?php echo e($currentScore !== null ? '/100' : ''); ?></span></span>
                    <?php if($scoreDiff !== null): ?>
                        <span class="g-trend-diff <?php echo e($scoreDiff >= 0 ? 'is-up' : 'is-down'); ?>">
                            <?php echo e($scoreDiff >= 0 ? '▲' : '▼'); ?> <?php echo e(abs($scoreDiff)); ?> pts vs previous run
                        </span>
                    <?php endif; ?>
                </div>
                <div class="g-trend-legend">
                    <span class="g-trend-legend-item"><i class="g-trend-swatch g-trend-swatch-current"></i>Current UX Score</span>
                    <span class="g-trend-legend-item"><i class="g-trend-swatch g-trend-swatch-previous"></i>Previous UX Score</span>
                </div>
            </div>

            <div class="g-trend-line" id="ux-trend-chart" data-points='<?php echo json_encode($trendPoints, 15, 512) ?>'>
                <?php if($trendPoints->isEmpty()): ?>
                    <div class="g-empty"><strong>No score history yet.</strong>Run more tests to populate the trend chart.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="g-card">
            <div class="g-split-row">
                <h3>Recent Test Runs</h3>
                <a class="g-btn g-btn-ghost" href="<?php echo e(route('test-runs.index')); ?>">View All</a>
            </div>
            <?php if($latestRuns->isEmpty()): ?>
                <div class="g-empty"><strong>No test runs yet.</strong>Run a live website or Android test to populate this stream.</div>
            <?php else: ?>
                <div class="g-table-wrap">
                    <table class="g-table">
                        <thead>
                            <tr>
                                <th>Run</th>
                                <th>Project</th>
                                <th>Flow</th>
                                <th>Final Friction</th>
                                <th>Confidence</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $latestRuns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $result = $run->finalFrictionResult;
                                    $level = $result?->friction_level ?? 'Not predicted';
                                    $badgeClass = match ($level) {
                                        'Low' => 'badge-low',
                                        'Medium' => 'badge-medium',
                                        'High' => 'badge-high',
                                        default => 'badge-neutral',
                                    };
                                ?>
                                <tr>
                                    <td><strong><?php echo e($run->run_code ?? 'N/A'); ?></strong><div class="g-table-meta"><?php echo e(optional($run->created_at)->diffForHumans() ?? 'N/A'); ?></div></td>
                                    <td><?php echo e($run->project?->name ?? 'N/A'); ?></td>
                                    <td><?php echo e($run->flow_type ?? 'N/A'); ?></td>
                                    <td><span class="g-badge <?php echo e($badgeClass); ?>"><?php echo e($level); ?></span></td>
                                    <td><?php echo e($result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A'); ?></td>
                                    <td><a class="g-btn g-btn-ghost" href="<?php echo e(route('test-runs.show', $run)); ?>">Open</a></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="g-stack">
        <div class="g-card">
            <div class="g-soft-label">Severity Breakdown</div>
            <div class="g-donut" style="background: conic-gradient(var(--g-green) 0 <?php echo e($lowPct); ?>%, var(--g-orange) <?php echo e($lowPct); ?>% <?php echo e($mediumStop); ?>%, var(--g-red) <?php echo e($mediumStop); ?>% 100%);">
                <div class="g-donut-center"><?php echo e($severityTotal === 1 && ($low + $medium + $high) === 0 ? 0 : $low + $medium + $high); ?><span>Total</span></div>
            </div>
            <div class="g-legend">
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-red);"></i>Critical Friction</span><strong><?php echo e($high); ?></strong></div>
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-orange);"></i>Moderate Issues</span><strong><?php echo e($medium); ?></strong></div>
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-green);"></i>Low Friction</span><strong><?php echo e($low); ?></strong></div>
            </div>
        </div>


        <div class="g-card g-flow-card">
    <div class="g-flow-header">
        <div>
            <div class="g-soft-label">Flow Distribution</div>
            <p class="g-flow-description">
                Distribution of test flows across recent UX test runs.
            </p>
        </div>

        <?php if($totalFlows > 0): ?>
            <div class="g-flow-total">
                <strong><?php echo e(number_format($totalFlows)); ?></strong>
                <span>Total Flows</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if($sortedFlowDistribution->isEmpty()): ?>
        <div class="g-empty">
            <strong>No flow data.</strong>
            UX metrics will appear after tests are saved.
        </div>
    <?php else: ?>
        <div class="g-flow-list">
            <?php $__currentLoopData = $sortedFlowDistribution; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flow => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $width = round(($count / $maxFlow) * 100);
                    $percentage = $totalFlows > 0
                        ? round(($count / $totalFlows) * 100, 1)
                        : 0;
                    $isMostUsed = $flow === $mostUsedFlow;
                    $readableFlow = ucwords(str_replace('_', ' ', $flow));
                ?>

                <div
                    class="g-flow-item <?php echo e($isMostUsed ? 'is-most-used' : ''); ?>"
                    title="<?php echo e($readableFlow); ?>: <?php echo e($count); ?> runs, <?php echo e($percentage); ?>% of all flows"
                >
                    <div class="g-flow-row">
                        <div class="g-flow-name">
                            <strong><?php echo e($readableFlow); ?></strong>

                            <?php if($isMostUsed): ?>
                                <span class="g-flow-badge">Most Used</span>
                            <?php endif; ?>
                        </div>

                        <div class="g-flow-stats">
                            <strong><?php echo e(number_format($count)); ?></strong>
                            <span><?php echo e($percentage); ?>%</span>
                        </div>
                    </div>

                    <div
                        class="g-progress g-flow-progress"
                        role="progressbar"
                        aria-label="<?php echo e($readableFlow); ?>"
                        aria-valuenow="<?php echo e($percentage); ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <span style="width: <?php echo e($width); ?>%;"></span>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="g-flow-insight">
            <span>Most frequently tested flow</span>
            <strong><?php echo e(ucwords(str_replace('_', ' ', $mostUsedFlow))); ?></strong>
        </div>
    <?php endif; ?>
</div>
    </div>
</div>

<div class="g-card" style="margin-top: 16px;">
    <div class="g-split-row">
        <h3>Recent Reports</h3>
        <a class="g-btn g-btn-ghost" href="<?php echo e(route('reports.index')); ?>">All Reports</a>
    </div>
    <?php if($latestReports->isEmpty()): ?>
        <div class="g-empty"><strong>No reports yet.</strong>Run a prediction and generate a report to see report output here.</div>
    <?php else: ?>
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Project</th>
                        <th>Generated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $latestReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($report->title ?? 'Untitled Report'); ?></strong></td>
                            <td><?php echo e($report->testRun?->project?->name ?? 'N/A'); ?></td>
                            <td><?php echo e(optional($report->generated_at)->format('Y-m-d H:i') ?? 'N/A'); ?></td>
                            <td><a class="g-btn g-btn-primary" href="<?php echo e(route('reports.show', $report)); ?>">View Report</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const container = document.getElementById('ux-trend-chart');
    if (!container) return;
    const points = JSON.parse(container.dataset.points || '[]');
    if (!points.length) return;

    const width = 800, height = 210, padding = { top: 16, right: 16, bottom: 28, left: 34 };
    const innerW = width - padding.left - padding.right;
    const innerH = height - padding.top - padding.bottom;
    const xStep = points.length > 1 ? innerW / (points.length - 1) : 0;
    const yFor = (score) => padding.top + innerH - (score / 100) * innerH;
    const xFor = (i) => padding.left + i * xStep;

    const currentPath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i)} ${yFor(p.score)}`).join(' ');

    const prevSegments = [];
    let seg = [];
    points.forEach((p, i) => {
        if (i === 0) { if (seg.length) prevSegments.push(seg); seg = []; return; }
        const prevScore = points[i - 1].score;
        seg.push(`${seg.length === 0 ? 'M' : 'L'} ${xFor(i)} ${yFor(prevScore)}`);
    });
    if (seg.length) prevSegments.push(seg);
    const prevPath = prevSegments.map((s) => s.join(' ')).join(' ');

    const gridLines = [0, 25, 50, 75, 100].map((v) => {
        const y = yFor(v);
        return `<line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" class="g-trend-grid" />
                <text x="${padding.left - 8}" y="${y + 4}" class="g-trend-axis-y" text-anchor="end">${v}</text>`;
    }).join('');

    const xLabels = points.map((p, i) => `<text x="${xFor(i)}" y="${height - 6}" class="g-trend-axis-x" text-anchor="middle">${p.date}</text>`).join('');
    const dots = points.map((p, i) => `<circle cx="${xFor(i)}" cy="${yFor(p.score)}" r="5" class="g-trend-dot" data-index="${i}" />`).join('');

    container.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="xMidYMid meet" aria-label="UX score trend visual">
            ${gridLines}
            <path d="${prevPath}" fill="none" stroke="#6c6d7a" stroke-width="3" stroke-dasharray="8 8" stroke-linecap="round" opacity=".7" />
            <path d="${currentPath}" fill="none" stroke="#a78bfa" stroke-width="4" stroke-linecap="round" />
            ${dots}
            ${xLabels}
        </svg>
        <div class="g-trend-tooltip" id="ux-trend-tooltip" hidden></div>
    `;

    const tooltip = container.querySelector('#ux-trend-tooltip');
    container.querySelectorAll('.g-trend-dot').forEach((dot) => {
        dot.addEventListener('mouseenter', () => {
            const i = Number(dot.dataset.index);
            const p = points[i];
            const prevScore = i > 0 ? points[i - 1].score : null;
            tooltip.innerHTML = `<strong>${p.date}</strong><br>Current: ${p.score}/100<br>Previous: ${prevScore !== null ? prevScore + '/100' : 'N/A'}<br>Friction: ${p.friction}`;
            tooltip.hidden = false;
            const rect = container.getBoundingClientRect();
            const dotRect = dot.getBoundingClientRect();
            tooltip.style.left = (dotRect.left - rect.left) + 'px';
            tooltip.style.top = (dotRect.top - rect.top) + 'px';
        });
        dot.addEventListener('mouseleave', () => { tooltip.hidden = true; });
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/dashboard.blade.php ENDPATH**/ ?>