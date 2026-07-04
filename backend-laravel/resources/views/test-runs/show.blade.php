@extends('layouts.app')

@section('title', 'Test Run Detail')

@section('content')
@php
    $metric = $testRun->uxMetric;
    $final = $testRun->finalFrictionResult;
    $main = $testRun->mainGAgentResult;
    $baseline = $testRun->baselineResult;
@endphp

<div class="card">
    <h2>{{ $testRun->run_code }}</h2>
    <p><strong>Project:</strong> {{ $testRun->project?->name ?? 'N/A' }}</p>
    <p><strong>Flow:</strong> {{ $testRun->flow_type ?? 'N/A' }}</p>
    <p><strong>Scenario:</strong> {{ $testRun->scenario_type ?? 'N/A' }}</p>
    <p><strong>Viewport:</strong> {{ $testRun->viewport_type ?? 'N/A' }}</p>
    <p><strong>Page URL:</strong> {{ $testRun->page_url ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ $testRun->status }}</p>

    <form method="POST" action="{{ route('test-runs.predict-gagent', $testRun) }}" style="display:inline-block;">
        @csrf
        <button class="btn" type="submit">Run Main GAgent Prediction</button>
    </form>

    <form method="POST" action="{{ route('test-runs.predict-baseline', $testRun) }}" style="display:inline-block;">
        @csrf
        <button class="btn btn-secondary" type="submit">Run Baseline Prediction</button>
    </form>

    <form method="POST" action="{{ route('reports.generate', $testRun) }}" style="display:inline-block;">
        @csrf
        <button class="btn btn-secondary" type="submit">Generate Report</button>
    </form>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="muted">Final GAgent Result</div>
        @php
            $level = $final?->friction_level ?? 'Not predicted';
            $badgeClass = match ($level) {
                'Low' => 'badge-low',
                'Medium' => 'badge-medium',
                'High' => 'badge-high',
                default => 'badge-neutral',
            };
        @endphp
        <p><span class="badge {{ $badgeClass }}">{{ $level }}</span></p>
        <p class="muted">Main GAgent result is the final system decision.</p>
    </div>

    <div class="card">
        <div class="muted">Final Confidence</div>
        <div class="stat-value">
            {{ $final?->confidence_score !== null ? number_format($final->confidence_score * 100, 1) . '%' : 'N/A' }}
        </div>
    </div>

    <div class="card">
        <div class="muted">Baseline Comparison</div>
        <div class="stat-value" style="font-size:22px;">
            {{ $baseline?->friction_level ?? 'N/A' }}
        </div>
        <p class="muted">Baseline is not the final decision.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>UX Metrics</h3>

        @if (!$metric)
            <p class="muted">No UX metrics found.</p>
        @else
            <table>
                <tbody>
                    <tr><th>Task Completed</th><td>{{ $metric->task_completed ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Task Failed</th><td>{{ $metric->task_failed ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Completion Time</th><td>{{ $metric->completion_time }}</td></tr>
                    <tr><th>Click Count</th><td>{{ $metric->click_count }}</td></tr>
                    <tr><th>Scroll Count</th><td>{{ $metric->scroll_count }}</td></tr>
                    <tr><th>Keyboard Count</th><td>{{ $metric->keyboard_count }}</td></tr>
                    <tr><th>Retry Count</th><td>{{ $metric->retry_count }}</td></tr>
                    <tr><th>Error Count</th><td>{{ $metric->error_count }}</td></tr>
                    <tr><th>Failed Clicks</th><td>{{ $metric->failed_clicks }}</td></tr>
                    <tr><th>Unnecessary Clicks</th><td>{{ $metric->unnecessary_clicks }}</td></tr>
                    <tr><th>Path Deviation Score</th><td>{{ $metric->path_deviation_score }}</td></tr>
                    <tr><th>Page Load Time</th><td>{{ $metric->page_load_time_ms }} ms</td></tr>
                    <tr><th>DOM Content Loaded</th><td>{{ $metric->dom_content_loaded_ms }} ms</td></tr>
                    <tr><th>TTFB</th><td>{{ $metric->time_to_first_byte_ms }} ms</td></tr>
                    <tr><th>Feedback Delay</th><td>{{ $metric->feedback_delay_ms }} ms</td></tr>
                    <tr><th>INP</th><td>{{ $metric->interaction_to_next_paint_ms }} ms</td></tr>
                    <tr><th>CLS</th><td>{{ $metric->cumulative_layout_shift }}</td></tr>
                    <tr><th>Error Message Present</th><td>{{ $metric->error_message_present ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Error Message Clarity</th><td>{{ $metric->error_message_clarity }}</td></tr>
                    <tr><th>Popup Detected</th><td>{{ $metric->popup_detected ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Cookie Banner Detected</th><td>{{ $metric->cookie_banner_detected ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Overlay Blocks CTA</th><td>{{ $metric->overlay_blocks_cta ? 'Yes' : 'No' }}</td></tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3>Metric Evidence Chart</h3>
        @if ($metric)
            <canvas id="metricChart"></canvas>
        @else
            <p class="muted">No chart available.</p>
        @endif
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Main GAgent Result</h3>
        @if (!$main)
            <p class="muted">No main GAgent result saved yet.</p>
        @else
            <p><strong>Model:</strong> {{ $main->model_name }} / {{ $main->model_type }}</p>
            <p><strong>Friction:</strong> {{ $main->friction_level }} <span class="badge badge-final">Final</span></p>
            <p><strong>Confidence:</strong> {{ number_format(($main->confidence_score ?? 0) * 100, 1) }}%</p>
            <h4>Class Probabilities</h4>
            <pre>{{ json_encode($main->class_probabilities, JSON_PRETTY_PRINT) }}</pre>
            <h4>Recommendations</h4>
            <ul>
                @foreach (($main->recommendations ?? []) as $recommendation)
                    <li>{{ $recommendation }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h3>Baseline Result</h3>
        @if (!$baseline)
            <p class="muted">No baseline result saved yet.</p>
        @else
            <p><strong>Model:</strong> {{ $baseline->model_name }} / {{ $baseline->model_type }}</p>
            <p><strong>Friction:</strong> {{ $baseline->friction_level }}</p>
            <p><strong>Confidence:</strong> {{ number_format(($baseline->confidence_score ?? 0) * 100, 1) }}%</p>
            <h4>Class Probabilities</h4>
            <pre>{{ json_encode($baseline->class_probabilities, JSON_PRETTY_PRINT) }}</pre>
            <p class="muted">Baseline is stored only for comparison.</p>
        @endif
    </div>
</div>

<div class="card">
    <h3>Interaction Logs</h3>
    @if ($testRun->interactionLogs->isEmpty())
        <p class="muted">No interaction logs available.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Event Type</th>
                    <th>Label</th>
                    <th>Value</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testRun->interactionLogs as $log)
                    <tr>
                        <td>{{ $log->event_type }}</td>
                        <td>{{ $log->event_label }}</td>
                        <td>{{ $log->event_value }}</td>
                        <td>{{ $log->event_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h3>Screenshot Evidence</h3>
    @if ($testRun->screenshots->isEmpty())
        <p class="muted">No screenshots available.</p>
    @else
        <div class="grid grid-3">
            @foreach ($testRun->screenshots as $screenshot)
                <div class="screenshot-box">
                    <strong>{{ $screenshot->label }}</strong>
                    <p class="muted">{{ $screenshot->file_path }}</p>
                    @if (str_starts_with($screenshot->file_path, 'http'))
                        <img src="{{ $screenshot->file_path }}" alt="{{ $screenshot->label }}">
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@if ($metric)
@push('scripts')
<script>
    new Chart(document.getElementById('metricChart'), {
        type: 'bar',
        data: {
            labels: ['Clicks', 'Retries', 'Errors', 'Failed Clicks', 'Unnecessary Clicks', 'Feedback Delay'],
            datasets: [{
                label: 'Metric Value',
                data: [
                    {{ $metric->click_count }},
                    {{ $metric->retry_count }},
                    {{ $metric->error_count }},
                    {{ $metric->failed_clicks }},
                    {{ $metric->unnecessary_clicks }},
                    {{ $metric->feedback_delay_ms }}
                ]
            }]
        }
    });
</script>
@endpush
@endif
