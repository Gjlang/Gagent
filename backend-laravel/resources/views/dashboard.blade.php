@extends('layouts.app')

@section('title', 'Dashboard')
@section('kicker', 'GAgent AI UX Testing')

@section('content')
@php
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
@endphp

<div class="g-page-header">
    <div>
        <h2>Autonomous UX Friction Dashboard</h2>
        <p>Monitor project coverage, friction severity, report output, and recent AI prediction activity from the Laravel dashboard without changing the backend workflow.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn g-btn-primary" href="{{ route('live-tests.create') }}">Run Live Website Test</a>
        <a class="g-btn" href="{{ route('android-tests.create') }}">Android Test</a>
    </div>
</div>

<div class="g-grid g-grid-4">
    <div class="g-metric-card">
        <div class="g-metric-label">Total Projects</div>
        <div class="g-metric-value">{{ number_format($totalProjects ?? 0) }}</div>
        <div class="g-metric-sub">Active UX test suites</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">UX Tests Run</div>
        <div class="g-metric-value">{{ number_format($totalTestRuns ?? 0) }}</div>
        <div class="g-metric-sub">Web, dummy, and Android runs</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">High Friction</div>
        <div class="g-metric-value" style="color: var(--g-red);">{{ number_format($high) }}</div>
        <div class="g-metric-sub">Action required</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">Avg UX Score</div>
        <div class="g-metric-value">{{ $averageConfidence !== null ? $avgUxScore : 'N/A' }}<span style="font-size: 15px;">{{ $averageConfidence !== null ? ' /100' : '' }}</span></div>
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
                <a class="g-btn g-btn-ghost" href="{{ route('test-runs.index') }}">View All</a>
            </div>
            @if ($latestRuns->isEmpty())
                <div class="g-empty"><strong>No test runs yet.</strong>Run a live website or Android test to populate this stream.</div>
            @else
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
                            @foreach ($latestRuns as $run)
                                @php
                                    $result = $run->finalFrictionResult;
                                    $level = $result?->friction_level ?? 'Not predicted';
                                    $badgeClass = match ($level) {
                                        'Low' => 'badge-low',
                                        'Medium' => 'badge-medium',
                                        'High' => 'badge-high',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <tr>
                                    <td><strong>{{ $run->run_code ?? 'N/A' }}</strong><div class="g-table-meta">{{ optional($run->created_at)->diffForHumans() ?? 'N/A' }}</div></td>
                                    <td>{{ $run->project?->name ?? 'N/A' }}</td>
                                    <td>{{ $run->flow_type ?? 'N/A' }}</td>
                                    <td><span class="g-badge {{ $badgeClass }}">{{ $level }}</span></td>
                                    <td>{{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                                    <td><a class="g-btn g-btn-ghost" href="{{ route('test-runs.show', $run) }}">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="g-stack">
        <div class="g-card">
            <div class="g-soft-label">Severity Breakdown</div>
            <div class="g-donut" style="background: conic-gradient(var(--g-green) 0 {{ $lowPct }}%, var(--g-orange) {{ $lowPct }}% {{ $mediumStop }}%, var(--g-red) {{ $mediumStop }}% 100%);">
                <div class="g-donut-center">{{ $severityTotal === 1 && ($low + $medium + $high) === 0 ? 0 : $low + $medium + $high }}<span>Total</span></div>
            </div>
            <div class="g-legend">
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-red);"></i>Critical Friction</span><strong>{{ $high }}</strong></div>
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-orange);"></i>Moderate Issues</span><strong>{{ $medium }}</strong></div>
                <div class="g-legend-row"><span><i class="g-legend-dot" style="background: var(--g-green);"></i>Low Friction</span><strong>{{ $low }}</strong></div>
            </div>
        </div>

        <div class="g-insight-card">
            <div class="g-soft-label">AI Co-Pilot Insights</div>
            <h3 style="margin-top: 7px;">Urgent Discovery</h3>
            <p class="g-muted">{{ $high > 0 ? 'High-friction sessions exist. Review failed clicks, retries, and feedback delay before the next demo.' : 'No high-friction final results detected yet. Continue collecting live and Android test evidence.' }}</p>
            <a class="g-btn g-btn-primary" href="{{ route('reports.index') }}">View Reports</a>
        </div>

        <div class="g-card">
            <div class="g-soft-label">Flow Distribution</div>
            @if (empty($flowDistribution))
                <div class="g-empty"><strong>No flow data.</strong>UX metrics will appear after tests are saved.</div>
            @else
                <div style="display: grid; gap: 12px; margin-top: 14px;">
                    @foreach ($flowDistribution as $flow => $count)
                        @php $width = round(($count / $maxFlow) * 100); @endphp
                        <div>
                            <div class="g-split-row g-small"><strong>{{ $flow }}</strong><span>{{ $count }}</span></div>
                            <div class="g-progress"><span style="width: {{ $width }}%;"></span></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="g-card" style="margin-top: 16px;">
    <div class="g-split-row">
        <h3>Recent Reports</h3>
        <a class="g-btn g-btn-ghost" href="{{ route('reports.index') }}">All Reports</a>
    </div>
    @if ($latestReports->isEmpty())
        <div class="g-empty"><strong>No reports yet.</strong>Run a prediction and generate a report to see report output here.</div>
    @else
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
                    @foreach ($latestReports as $report)
                        <tr>
                            <td><strong>{{ $report->title ?? 'Untitled Report' }}</strong></td>
                            <td>{{ $report->testRun?->project?->name ?? 'N/A' }}</td>
                            <td>{{ optional($report->generated_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                            <td><a class="g-btn g-btn-primary" href="{{ route('reports.show', $report) }}">View Report</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
