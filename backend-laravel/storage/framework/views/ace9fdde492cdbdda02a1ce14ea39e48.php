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
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">UX Score Trends</div>
                    <h3 style="margin-top: 5px;">Aggregate performance across recent project versions</h3>
                </div>
                <span class="g-badge badge-final">Live Metrics</span>
            </div>

            <div class="g-trend-line" style="margin-top: 12px;">
                <svg viewBox="0 0 800 210" preserveAspectRatio="none" aria-label="UX score trend visual">
                    <path d="M0 158 C90 132, 135 154, 205 122 C280 82, 315 84, 365 130 C420 182, 475 165, 520 108 C585 30, 665 22, 735 70 C760 88, 780 108, 800 120" fill="none" stroke="#0b84ff" stroke-width="8" stroke-linecap="round"/>
                    <path d="M0 176 C90 150, 145 172, 220 142 C300 110, 330 112, 382 154 C434 192, 482 178, 540 130 C605 74, 675 58, 740 98 C770 116, 790 136, 800 148" fill="none" stroke="#94a3b8" stroke-width="5" stroke-dasharray="10 10" stroke-linecap="round" opacity=".65"/>
                </svg>
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

        <div class="g-insight-card">
            <div class="g-soft-label">AI Co-Pilot Insights</div>
            <h3 style="margin-top: 7px;">Urgent Discovery</h3>
            <p class="g-muted"><?php echo e($high > 0 ? 'High-friction sessions exist. Review failed clicks, retries, and feedback delay before the next demo.' : 'No high-friction final results detected yet. Continue collecting live and Android test evidence.'); ?></p>
            <a class="g-btn g-btn-primary" href="<?php echo e(route('reports.index')); ?>">View Reports</a>
        </div>

        <div class="g-card">
            <div class="g-soft-label">Flow Distribution</div>
            <?php if(empty($flowDistribution)): ?>
                <div class="g-empty"><strong>No flow data.</strong>UX metrics will appear after tests are saved.</div>
            <?php else: ?>
                <div style="display: grid; gap: 12px; margin-top: 14px;">
                    <?php $__currentLoopData = $flowDistribution; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flow => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $width = round(($count / $maxFlow) * 100); ?>
                        <div>
                            <div class="g-split-row g-small"><strong><?php echo e($flow); ?></strong><span><?php echo e($count); ?></span></div>
                            <div class="g-progress"><span style="width: <?php echo e($width); ?>%;"></span></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/dashboard.blade.php ENDPATH**/ ?>