@extends('layouts.app')

@section('title', 'UX Friction Report')
@section('kicker', 'Generated UX Audit')

@section('content')
@php
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

    $frictionScore = $final?->confidence_score !== null
        ? round($final->confidence_score * 100)
        : 0;
@endphp

<div class="g-page-header">
    <div>
        <h2>{{ $report->title ?? 'UX Friction Report' }}</h2>
        <p>
            Generated at:
            {{ $report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('Y-m-d H:i') : 'N/A' }}
        </p>
    </div>

    <div class="g-actions">
        <a class="g-btn" href="{{ route('reports.index') }}">Back to Reports</a>

        @if ($run)
            <a class="g-btn g-btn-primary" href="{{ route('test-runs.show', $run) }}">View Test Run</a>
        @endif
    </div>
</div>

<div class="g-layout-2-1">
    <div class="g-stack">

        <div class="g-report-card">
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">Report Analysis</div>
                    <h3 style="margin-top: 6px;">Executive Summary</h3>
                </div>

                <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
            </div>

            <p class="g-muted" style="margin-top: 12px;">
                {{ $report->summary ?? 'No executive summary available for this report.' }}
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
                        {{ (int) $errorCount + (int) $failedClicks }}
                    </div>
                    <div class="g-metric-sub">Errors and failed clicks</div>
                </div>

                <div class="g-metric-card">
                    <div class="g-metric-label">Avg Time to Complete</div>
                    <div class="g-metric-value" style="font-size: 28px;">
                        {{ $completionTime }}
                    </div>
                    <div class="g-metric-sub">Completion time metric</div>
                </div>
            </div>
        </div>

        <div class="g-card">
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">Friction Points Evidence</div>
                    <h3 style="margin-top: 6px;">Screenshot Evidence</h3>
                </div>

                <span class="g-badge badge-neutral">{{ $screenshots->count() }} captures</span>
            </div>

            @if (!$run || $screenshots->isEmpty())
                <div class="g-empty">
                    <strong>No screenshots available.</strong>
                    Screenshot evidence will appear here after the test runner saves captures.
                </div>
            @else
                <div class="g-evidence-grid" style="margin-top: 14px;">
                    @foreach ($screenshots as $screenshot)
                        @php
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
                        @endphp

                        <div class="g-evidence-card">
                            @if ($imageSrc)
                                <img class="g-screenshot-img" src="{{ $imageSrc }}" alt="{{ $screenshot->label ?? 'Screenshot evidence' }}">
                            @else
                                <div class="g-evidence-visual">No Image</div>
                            @endif

                            <div class="g-evidence-body">
                                <strong>{{ $screenshot->label ?? 'Screenshot Evidence' }}</strong>
                                <p class="g-muted g-small" style="margin-bottom: 8px;">
                                    {{ $screenshot->file_path ?? 'No file path available' }}
                                </p>

                                <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Retry Analysis</h3>

                @if (!$metric)
                    <div class="g-empty">
                        <strong>No UX metrics available.</strong>
                        Run metrics were not attached to this report.
                    </div>
                @else
                    <div style="display: grid; gap: 14px;">
                        <div>
                            <div class="g-split-row g-small">
                                <strong>Retry Count</strong>
                                <span>{{ $metric->retry_count ?? 0 }}</span>
                            </div>
                            <div class="g-progress warn">
                                <span style="width: {{ min(100, (($metric->retry_count ?? 0) / 10) * 100) }}%;"></span>
                            </div>
                        </div>

                        <div>
                            <div class="g-split-row g-small">
                                <strong>Error Count</strong>
                                <span>{{ $metric->error_count ?? 0 }}</span>
                            </div>
                            <div class="g-progress danger">
                                <span style="width: {{ min(100, (($metric->error_count ?? 0) / 10) * 100) }}%;"></span>
                            </div>
                        </div>

                        <div>
                            <div class="g-split-row g-small">
                                <strong>Failed Clicks</strong>
                                <span>{{ $metric->failed_clicks ?? 0 }}</span>
                            </div>
                            <div class="g-progress danger">
                                <span style="width: {{ min(100, (($metric->failed_clicks ?? 0) / 10) * 100) }}%;"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="g-card">
                <h3>Engagement Metrics</h3>

                @if (!$metric)
                    <div class="g-empty">
                        <strong>No engagement data.</strong>
                        Engagement values will appear when metrics exist.
                    </div>
                @else
                    <div class="g-mini-bars">
                        <span style="height: {{ min(120, max(12, ($metric->click_count ?? 1) * 8)) }}px;"></span>
                        <span style="height: {{ min(120, max(12, ($metric->scroll_count ?? 1) * 12)) }}px;"></span>
                        <span style="height: {{ min(120, max(12, ($metric->keyboard_count ?? 1) * 10)) }}px;"></span>
                        <span style="height: {{ min(120, max(12, ($metric->retry_count ?? 1) * 20)) }}px;"></span>
                        <span style="height: {{ min(120, max(12, ($metric->error_count ?? 1) * 22)) }}px;"></span>
                    </div>

                    <div class="g-kv" style="margin-top: 12px;">
                        <div class="g-kv-row"><span>Click Count</span><span>{{ $metric->click_count ?? 'N/A' }}</span></div>
                        <div class="g-kv-row"><span>Scroll Count</span><span>{{ $metric->scroll_count ?? 'N/A' }}</span></div>
                        <div class="g-kv-row"><span>Keyboard Count</span><span>{{ $metric->keyboard_count ?? 'N/A' }}</span></div>
                        <div class="g-kv-row"><span>Task Completed</span><span>{{ ($metric->task_completed ?? false) ? 'Yes' : 'No' }}</span></div>
                    </div>
                @endif
            </div>
        </div>

        <div class="g-card">
            <h3>UX Metric Evidence</h3>

            @if (!$metric)
                <div class="g-empty">
                    <strong>No UX metrics available.</strong>
                    The report exists, but no linked UX metric row was found.
                </div>
            @else
                <div class="g-table-wrap">
                    <table class="g-table">
                        <tbody>
                            <tr><th>Completion Time</th><td>{{ $metric->completion_time ?? 'N/A' }}</td></tr>
                            <tr><th>Click Count</th><td>{{ $metric->click_count ?? 'N/A' }}</td></tr>
                            <tr><th>Scroll Count</th><td>{{ $metric->scroll_count ?? 'N/A' }}</td></tr>
                            <tr><th>Keyboard Count</th><td>{{ $metric->keyboard_count ?? 'N/A' }}</td></tr>
                            <tr><th>Retry Count</th><td>{{ $metric->retry_count ?? 'N/A' }}</td></tr>
                            <tr><th>Error Count</th><td>{{ $metric->error_count ?? 'N/A' }}</td></tr>
                            <tr><th>Failed Clicks</th><td>{{ $metric->failed_clicks ?? 'N/A' }}</td></tr>
                            <tr><th>Unnecessary Clicks</th><td>{{ $metric->unnecessary_clicks ?? 'N/A' }}</td></tr>
                            <tr><th>Path Deviation</th><td>{{ $metric->path_deviation_score ?? 'N/A' }}</td></tr>
                            <tr><th>Page Load Time</th><td>{{ $metric->page_load_time_ms !== null ? $metric->page_load_time_ms . ' ms' : 'N/A' }}</td></tr>
                            <tr><th>Feedback Delay</th><td>{{ $metric->feedback_delay_ms !== null ? $metric->feedback_delay_ms . ' ms' : 'N/A' }}</td></tr>
                            <tr><th>Cumulative Layout Shift</th><td>{{ $metric->cumulative_layout_shift ?? 'N/A' }}</td></tr>
                            <tr><th>Popup Detected</th><td>{{ ($metric->popup_detected ?? false) ? 'Yes' : 'No' }}</td></tr>
                            <tr><th>Cookie Banner Detected</th><td>{{ ($metric->cookie_banner_detected ?? false) ? 'Yes' : 'No' }}</td></tr>
                            <tr><th>Overlay Blocks CTA</th><td>{{ ($metric->overlay_blocks_cta ?? false) ? 'Yes' : 'No' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="g-card">
            <h3>Interaction Logs</h3>

            @if (!$run || $logs->isEmpty())
                <div class="g-empty">
                    <strong>No interaction logs available.</strong>
                    Event logs will appear here after the automation runner stores them.
                </div>
            @else
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
                            @foreach ($logs as $log)
                                <tr>
                                    <td>{{ $log->event_type ?? 'N/A' }}</td>
                                    <td>{{ $log->event_label ?? 'N/A' }}</td>
                                    <td>{{ $log->event_value ?? 'N/A' }}</td>
                                    <td>{{ $log->event_time ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="g-card">
            <h3>Conclusion</h3>
            <p class="g-muted">
                {{ $report->conclusion ?? 'No conclusion available for this report.' }}
            </p>
        </div>
    </div>

    <aside class="g-stack">
        <div class="g-panel">
            <div class="g-soft-label">AI Prediction</div>
            <h3 style="margin-top: 7px;">Likely UX Friction Identified</h3>

            <div style="margin-top: 14px;">
                <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
            </div>

            <div class="g-metric-value" style="font-size: 42px; margin-top: 16px;">
                {{ $frictionScore }}
                <span style="font-size: 15px;">/100</span>
            </div>

            <div class="g-progress {{ $level === 'High' ? 'danger' : ($level === 'Medium' ? 'warn' : 'safe') }}">
                <span style="width: {{ $frictionScore }}%;"></span>
            </div>

            <div class="g-kv" style="margin-top: 16px;">
                <div class="g-kv-row"><span>Confidence</span><span>{{ $confidence }}</span></div>
                <div class="g-kv-row"><span>Source</span><span>{{ $final?->prediction_source ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Run Code</span><span>{{ $run?->run_code ?? 'N/A' }}</span></div>
            </div>
        </div>

        <div class="g-panel">
            <h3>Project Details</h3>
            <div class="g-kv">
                <div class="g-kv-row"><span>Project</span><span>{{ $project?->name ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Target Type</span><span>{{ $project?->target_type ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Target URL</span><span>{{ $project?->target_url ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Flow</span><span>{{ $run?->flow_type ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Scenario</span><span>{{ $run?->scenario_type ?? $run?->run_mode ?? 'N/A' }}</span></div>
                <div class="g-kv-row"><span>Viewport</span><span>{{ $run?->viewport_type ?? 'N/A' }}</span></div>
            </div>
        </div>

        <div class="g-insight-card" style="background: #06172b; color: #dff2ff;">
            <div class="g-soft-label" style="color: #7dd3fc;">AI Recommendation</div>
            <h3 style="margin-top: 7px; color: white;">Fix Priority</h3>

            @if (empty($recommendations))
                <p style="color: #cbd5e1;">
                    No recommendations were returned by the main GAgent model.
                </p>
            @else
                <ul style="padding-left: 18px; line-height: 1.7;">
                    @foreach ($recommendations as $recommendation)
                        <li>{{ is_array($recommendation) ? json_encode($recommendation) : $recommendation }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="g-card">
            <h3>Main GAgent Prediction</h3>

            @if (!$main)
                <div class="g-empty">
                    <strong>No main GAgent prediction.</strong>
                    Run GAgent prediction from the test run page.
                </div>
            @else
                <div class="g-kv">
                    <div class="g-kv-row"><span>Model</span><span>{{ $main->model_name ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Type</span><span>{{ $main->model_type ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Friction</span><span>{{ $main->friction_level ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Confidence</span><span>{{ $main->confidence_score !== null ? number_format($main->confidence_score * 100, 1) . '%' : 'N/A' }}</span></div>
                </div>

                <h4 style="margin-top: 16px;">Class Probabilities</h4>
                <pre class="g-console">{{ json_encode($mainProbabilities, JSON_PRETTY_PRINT) }}</pre>
            @endif
        </div>

        <div class="g-card">
            <h3>Baseline Comparison</h3>

            @if (!$baseline)
                <div class="g-empty">
                    <strong>No baseline prediction.</strong>
                    Baseline is optional and used for comparison only.
                </div>
            @else
                <div class="g-kv">
                    <div class="g-kv-row"><span>Model</span><span>{{ $baseline->model_name ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Type</span><span>{{ $baseline->model_type ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Friction</span><span>{{ $baseline->friction_level ?? 'N/A' }}</span></div>
                    <div class="g-kv-row"><span>Confidence</span><span>{{ $baseline->confidence_score !== null ? number_format($baseline->confidence_score * 100, 1) . '%' : 'N/A' }}</span></div>
                </div>

                @if ($baselineProbabilities)
                    <h4 style="margin-top: 16px;">Class Probabilities</h4>
                    <pre class="g-console">{{ json_encode($baselineProbabilities, JSON_PRETTY_PRINT) }}</pre>
                @endif
            @endif
        </div>
    </aside>
</div>
@endsection
