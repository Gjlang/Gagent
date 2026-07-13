@extends('layouts.app')

@section('title', 'Test Run Detail')
@section('kicker', 'Automation Run Analysis')

@section('content')
@php
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
@endphp

<div class="g-page-header">
    <div>
        <h2>{{ $testRun->run_code ?? 'Test Run Detail' }}</h2>
        <p>
            Review the saved UX metrics, AI predictions, baseline comparison, screenshots, logs, and report generation actions for this test run.
        </p>
    </div>

    <div class="g-actions">
        <a class="g-btn" href="{{ route('test-runs.index') }}">Back to Test Runs</a>

        @if ($report)
            <a class="g-btn g-btn-primary" href="{{ route('reports.show', $report) }}">View Report</a>
        @endif
    </div>
</div>

<div class="g-card" style="margin-bottom: 16px;">
    <div class="g-actions">
        @if (\Illuminate\Support\Facades\Route::has('test-runs.predict-gagent'))
            <form method="POST" action="{{ route('test-runs.predict-gagent', $testRun) }}">
                @csrf
                <button class="g-btn g-btn-primary" type="submit">Run Main GAgent Prediction</button>
            </form>
        @endif

        @if (\Illuminate\Support\Facades\Route::has('test-runs.predict-baseline'))
            <form method="POST" action="{{ route('test-runs.predict-baseline', $testRun) }}">
                @csrf
                <button class="g-btn" type="submit">Run Baseline Prediction</button>
            </form>
        @endif

        @if (\Illuminate\Support\Facades\Route::has('reports.generate'))
            <form method="POST" action="{{ route('reports.generate', $testRun) }}">
                @csrf
                <button class="g-btn g-btn-dark" type="submit">Generate Report</button>
            </form>
        @endif
    </div>
</div>

<div class="g-grid g-grid-4">
    <div class="g-metric-card">
        <div class="g-metric-label">Final Friction</div>
        <div style="margin-top: 16px;">
            <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
        </div>
        <div class="g-metric-sub">Final system decision</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Final Confidence</div>
        <div class="g-metric-value">{{ $finalConfidence }}</div>
        <div class="g-metric-sub">Prediction certainty</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Run Status</div>
        <div style="margin-top: 16px;">
            <span class="g-status-badge {{ $statusClass }}">{{ $testRun->status ?? 'N/A' }}</span>
        </div>
        <div class="g-metric-sub">Automation state</div>
    </div>

    <div class="g-metric-card">
        <div class="g-metric-label">Report</div>
        <div class="g-metric-value" style="font-size: 24px;">
            {{ $report ? 'Ready' : 'N/A' }}
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
                <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
            </div>

            <div class="g-table-wrap" style="margin-top: 14px;">
                <table class="g-table">
                    <tbody>
                        <tr>
                            <th>Run Code</th>
                            <td>{{ $testRun->run_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Project</th>
                            <td>{{ $project?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Target Type</th>
                            <td>{{ $project?->target_type ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Flow</th>
                            <td>{{ $testRun->flow_type ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Scenario</th>
                            <td>{{ $testRun->scenario_type ?? $testRun->run_mode ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Viewport</th>
                            <td>{{ $testRun->viewport_type ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Page URL</th>
                            <td>{{ $testRun->page_url ?? $project?->target_url ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td>{{ optional($testRun->created_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="g-grid g-grid-2">
            <div class="g-card">
                <h3>Main GAgent Prediction</h3>

                @if (!$main)
                    <div class="g-empty">
                        <strong>No main GAgent prediction yet.</strong>
                        Click “Run Main GAgent Prediction” to generate the final system prediction.
                    </div>
                @else
                    <div class="g-kv">
                        <div class="g-kv-row">
                            <span>Model</span>
                            <span>{{ $main->model_name ?? 'N/A' }}</span>
                        </div>
                        <div class="g-kv-row">
                            <span>Type</span>
                            <span>{{ $main->model_type ?? 'N/A' }}</span>
                        </div>
                        <div class="g-kv-row">
                            <span>Friction</span>
                            <span>
                                <span class="g-badge {{ match ($main->friction_level ?? '') {
                                    'Low' => 'badge-low',
                                    'Medium' => 'badge-medium',
                                    'High' => 'badge-high',
                                    default => 'badge-neutral',
                                } }}">
                                    {{ $main->friction_level ?? 'N/A' }}
                                </span>
                            </span>
                        </div>
                        <div class="g-kv-row">
                            <span>Confidence</span>
                            <span>{{ $mainConfidence }}</span>
                        </div>
                    </div>

                    <h4 style="margin-top: 16px;">Class Probabilities</h4>
                    <pre class="g-console">{{ json_encode($mainProbabilities, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>

        <div class="g-card">
            <h3>UX Metrics</h3>

            @if (!$metric)
                <div class="g-empty">
                    <strong>No UX metrics available.</strong>
                    This test run has no linked UX metric row.
                </div>
            @else
                <div class="g-table-wrap">
                    <table class="g-table">
                        <tbody>
                            <tr>
                                <th>Task Completed</th>
                                <td>{{ ($metric->task_completed ?? false) ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Task Failed</th>
                                <td>{{ ($metric->task_failed ?? false) ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Completion Time</th>
                                <td>{{ $metric->completion_time ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Click Count</th>
                                <td>{{ $metric->click_count ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Scroll Count</th>
                                <td>{{ $metric->scroll_count ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Keyboard Count</th>
                                <td>{{ $metric->keyboard_count ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Retry Count</th>
                                <td>{{ $metric->retry_count ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Error Count</th>
                                <td>{{ $metric->error_count ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Failed Clicks</th>
                                <td>{{ $metric->failed_clicks ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Unnecessary Clicks</th>
                                <td>{{ $metric->unnecessary_clicks ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Path Deviation Score</th>
                                <td>{{ $metric->path_deviation_score ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Page Load Time</th>
                                <td>{{ $metric->page_load_time_ms !== null ? $metric->page_load_time_ms . ' ms' : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Feedback Delay</th>
                                <td>{{ $metric->feedback_delay_ms !== null ? $metric->feedback_delay_ms . ' ms' : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Cumulative Layout Shift</th>
                                <td>{{ $metric->cumulative_layout_shift ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Popup Detected</th>
                                <td>{{ ($metric->popup_detected ?? false) ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Cookie Banner Detected</th>
                                <td>{{ ($metric->cookie_banner_detected ?? false) ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Overlay Blocks CTA</th>
                                <td>{{ ($metric->overlay_blocks_cta ?? false) ? 'Yes' : 'No' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="g-card">
            <h3>Screenshot Evidence</h3>

            @if ($screenshots->isEmpty())
                <div class="g-empty">
                    <strong>No screenshots available.</strong>
                    Screenshots will appear after Playwright or Appium saves capture evidence.
                </div>
            @else
                <div class="g-evidence-grid">
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
                                <p class="g-muted g-small">
                                    {{ $screenshot->file_path ?? 'No file path available' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="g-card">
            <h3>Interaction Logs</h3>

            @if ($logs->isEmpty())
                <div class="g-empty">
                    <strong>No interaction logs available.</strong>
                    Logs will appear here after the automation runner stores events.
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
    </div>

    <aside class="g-stack">
        <div class="g-panel">
            <div class="g-soft-label">Final GAgent Result</div>
            <div class="g-metric-value" style="font-size: 42px;">
                {{ $frictionScore }}
                <span style="font-size: 15px;">/100</span>
            </div>

            <div style="margin: 12px 0;">
                <span class="g-badge {{ $badgeClass }}">{{ $level }}</span>
            </div>

            <div class="g-progress {{ $level === 'High' ? 'danger' : ($level === 'Medium' ? 'warn' : 'safe') }}">
                <span style="width: {{ $frictionScore }}%;"></span>
            </div>

            <p class="g-muted" style="margin-top: 12px;">
                Main GAgent result is the final system decision.
            </p>
        </div>

        <div class="g-panel">
            <h3>Performance Metrics</h3>

            @if (!$metric)
                <div class="g-empty">
                    <strong>No metric data.</strong>
                    Nothing to visualise yet.
                </div>
            @else
                <div class="g-kv">
                    <div class="g-kv-row">
                        <span>Completion Time</span>
                        <span>{{ $metric->completion_time ?? 'N/A' }}</span>
                    </div>
                    <div class="g-kv-row">
                        <span>Clicks</span>
                        <span>{{ $metric->click_count ?? 'N/A' }}</span>
                    </div>
                    <div class="g-kv-row">
                        <span>Retries</span>
                        <span>{{ $metric->retry_count ?? 'N/A' }}</span>
                    </div>
                    <div class="g-kv-row">
                        <span>Errors</span>
                        <span>{{ $metric->error_count ?? 'N/A' }}</span>
                    </div>
                    <div class="g-kv-row">
                        <span>Failed Clicks</span>
                        <span>{{ $metric->failed_clicks ?? 'N/A' }}</span>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <div class="g-split-row g-small">
                        <strong>Retry Pressure</strong>
                        <span>{{ $metric->retry_count ?? 0 }}</span>
                    </div>
                    <div class="g-progress warn">
                        <span style="width: {{ min(100, (($metric->retry_count ?? 0) / 10) * 100) }}%;"></span>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <div class="g-split-row g-small">
                        <strong>Error Pressure</strong>
                        <span>{{ $metric->error_count ?? 0 }}</span>
                    </div>
                    <div class="g-progress danger">
                        <span style="width: {{ min(100, (($metric->error_count ?? 0) / 10) * 100) }}%;"></span>
                    </div>
                </div>
            @endif
        </div>

        <div class="g-insight-card" style="background: #06172b; color: #dff2ff;">
            <div class="g-soft-label" style="color: #7dd3fc;">AI Recommendations</div>
            <h3 style="margin-top: 7px; color: white;">Suggested Fixes</h3>

            @if (empty($recommendations))
                <p style="color: #cbd5e1;">
                    No recommendations available. Run the Main GAgent prediction again if needed.
                </p>
            @else
                <ul style="padding-left: 18px; line-height: 1.7;">
                    @foreach ($recommendations as $recommendation)
                        <li>{{ is_array($recommendation) ? json_encode($recommendation) : $recommendation }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="g-panel">
            <h3>Report Action</h3>

            @if ($report)
                <p class="g-muted">A report has already been generated for this test run.</p>
                <a class="g-btn g-btn-primary g-btn-block" href="{{ route('reports.show', $report) }}">Open Report</a>
            @else
                <p class="g-muted">Generate a report after prediction results are available.</p>

                @if (\Illuminate\Support\Facades\Route::has('reports.generate'))
                    <form method="POST" action="{{ route('reports.generate', $testRun) }}">
                        @csrf
                        <button class="g-btn g-btn-primary g-btn-block" type="submit">Generate Report</button>
                    </form>
                @endif
            @endif
        </div>
    </aside>
</div>
@endsection
