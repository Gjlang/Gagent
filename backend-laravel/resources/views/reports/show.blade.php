@extends('layouts.app')

@section('title', 'UX Friction Report')

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
@endphp

<div class="card">
    <h2>{{ $report->title }}</h2>
    <p class="muted">Generated at: {{ $report->generated_at ?? 'N/A' }}</p>
    <p>{{ $report->summary }}</p>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="muted">Final Friction Level</div>
        <p><span class="badge {{ $badgeClass }}">{{ $level }}</span></p>
    </div>

    <div class="card">
        <div class="muted">Confidence Score</div>
        <div class="stat-value">
            {{ $final?->confidence_score !== null ? number_format($final->confidence_score * 100, 1) . '%' : 'N/A' }}
        </div>
    </div>

    <div class="card">
        <div class="muted">Prediction Source</div>
        <div class="stat-value" style="font-size:22px;">
            {{ $final?->prediction_source ?? 'N/A' }}
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Project Details</h3>
        <p><strong>Project:</strong> {{ $project?->name ?? 'N/A' }}</p>
        <p><strong>Target Type:</strong> {{ $project?->target_type ?? 'N/A' }}</p>
        <p><strong>Target URL:</strong> {{ $project?->target_url ?? 'N/A' }}</p>
    </div>

    <div class="card">
        <h3>Test Run Details</h3>
        <p><strong>Run Code:</strong> {{ $run?->run_code ?? 'N/A' }}</p>
        <p><strong>Flow:</strong> {{ $run?->flow_type ?? 'N/A' }}</p>
        <p><strong>Scenario:</strong> {{ $run?->scenario_type ?? 'N/A' }}</p>
        <p><strong>Viewport:</strong> {{ $run?->viewport_type ?? 'N/A' }}</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Main GAgent Prediction</h3>
        @if (!$main)
            <p class="muted">No main GAgent prediction available.</p>
        @else
            <p><strong>Model:</strong> {{ $main->model_name }}</p>
            <p><strong>Type:</strong> {{ $main->model_type }}</p>
            <p><strong>Friction:</strong> {{ $main->friction_level }} <span class="badge badge-final">Final</span></p>
            <p><strong>Confidence:</strong> {{ number_format(($main->confidence_score ?? 0) * 100, 1) }}%</p>
            <h4>Class Probabilities</h4>
            <pre>{{ json_encode($main->class_probabilities, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>

    <div class="card">
        <h3>Baseline Comparison</h3>
        @if (!$baseline)
            <p class="muted">No baseline prediction available.</p>
        @else
            <p><strong>Model:</strong> {{ $baseline->model_name }}</p>
            <p><strong>Type:</strong> {{ $baseline->model_type }}</p>
            <p><strong>Friction:</strong> {{ $baseline->friction_level }}</p>
            <p><strong>Confidence:</strong> {{ number_format(($baseline->confidence_score ?? 0) * 100, 1) }}%</p>
            <p class="muted">Baseline is shown only for comparison.</p>
        @endif
    </div>
</div>

<div class="card">
    <h3>Recommendations</h3>
    @if (!$main || empty($main->recommendations))
        <p class="muted">No recommendations available.</p>
    @else
        <ul>
            @foreach ($main->recommendations as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="card">
    <h3>UX Metric Evidence</h3>
    @if (!$metric)
        <p class="muted">No UX metrics available.</p>
    @else
        <table>
            <tbody>
                <tr><th>Completion Time</th><td>{{ $metric->completion_time }}</td></tr>
                <tr><th>Click Count</th><td>{{ $metric->click_count }}</td></tr>
                <tr><th>Retry Count</th><td>{{ $metric->retry_count }}</td></tr>
                <tr><th>Error Count</th><td>{{ $metric->error_count }}</td></tr>
                <tr><th>Failed Clicks</th><td>{{ $metric->failed_clicks }}</td></tr>
                <tr><th>Unnecessary Clicks</th><td>{{ $metric->unnecessary_clicks }}</td></tr>
                <tr><th>Path Deviation</th><td>{{ $metric->path_deviation_score }}</td></tr>
                <tr><th>Page Load Time</th><td>{{ $metric->page_load_time_ms }} ms</td></tr>
                <tr><th>Feedback Delay</th><td>{{ $metric->feedback_delay_ms }} ms</td></tr>
                <tr><th>CLS</th><td>{{ $metric->cumulative_layout_shift }}</td></tr>
                <tr><th>Popup Detected</th><td>{{ $metric->popup_detected ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Cookie Banner Detected</th><td>{{ $metric->cookie_banner_detected ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Overlay Blocks CTA</th><td>{{ $metric->overlay_blocks_cta ? 'Yes' : 'No' }}</td></tr>
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h3>Screenshot Evidence</h3>
    @if (!$run || $run->screenshots->isEmpty())
        <p class="muted">No screenshots available.</p>
    @else
        <div class="grid grid-3">
            @foreach ($run->screenshots as $screenshot)
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

<div class="card">
    <h3>Interaction Logs</h3>
    @if (!$run || $run->interactionLogs->isEmpty())
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
                @foreach ($run->interactionLogs as $log)
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
    <h3>Conclusion</h3>
    <p>{{ $report->conclusion }}</p>
</div>
@endsection
