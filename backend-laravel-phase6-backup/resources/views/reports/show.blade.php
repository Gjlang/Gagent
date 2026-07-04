@extends('layouts.app')

@section('title', 'UX Friction Report')

@section('content')
@php
    $run = $report->testRun;
    $project = $run?->project;
    $metric = $run?->uxMetric;
    $result = $run?->frictionResult;
    $level = $result?->friction_level ?? 'Not predicted';
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

    <form method="POST" action="{{ route('reports.predict', $report) }}" style="display:inline-block;">
        @csrf
        <button class="btn" type="submit">Run FastAPI Prediction</button>
    </form>

    <a class="btn btn-secondary" href="{{ route('reports.export', $report) }}">Download Text Report</a>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="muted">Friction Severity</div>
        <div style="margin-top:12px;">
            <span class="badge {{ $badgeClass }}">{{ $level }}</span>
        </div>
    </div>

    <div class="card">
        <div class="muted">Confidence Score</div>
        <div class="stat-value">
            {{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}
        </div>
    </div>

    <div class="card">
        <div class="muted">Model Used</div>
        <div class="stat-value" style="font-size:20px;">
            {{ $result?->model_used ?? 'N/A' }}
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Project Information</h3>
        <p><strong>Project:</strong> {{ $project?->project_name ?? 'N/A' }}</p>
        <p><strong>Platform:</strong> {{ $project?->platform_type ?? 'N/A' }}</p>
        <p><strong>Website URL:</strong> {{ $project?->website_url ?? 'N/A' }}</p>
        <p><strong>Project Status:</strong> {{ $project?->status ?? 'N/A' }}</p>
    </div>

    <div class="card">
        <h3>Test Run Information</h3>
        <p><strong>Test Run ID:</strong> {{ $run?->id ?? 'N/A' }}</p>
        <p><strong>Flow Type:</strong> {{ $run?->flow_type ?? 'N/A' }}</p>
        <p><strong>Page URL:</strong> {{ $run?->page_url ?? 'N/A' }}</p>
        <p><strong>Status:</strong> {{ $run?->status ?? 'N/A' }}</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>UX Metrics Table</h3>

        @if (!$metric)
            <p class="muted">No UX metrics available.</p>
        @else
            <table>
                <tbody>
                    <tr><th>Completion Time</th><td>{{ $metric->completion_time }}</td></tr>
                    <tr><th>Click Count</th><td>{{ $metric->click_count }}</td></tr>
                    <tr><th>Scroll Count</th><td>{{ $metric->scroll_count }}</td></tr>
                    <tr><th>Keyboard Count</th><td>{{ $metric->keyboard_count }}</td></tr>
                    <tr><th>Retry Count</th><td>{{ $metric->retry_count }}</td></tr>
                    <tr><th>Error Count</th><td>{{ $metric->error_count }}</td></tr>
                    <tr><th>Failed Clicks</th><td>{{ $metric->failed_clicks }}</td></tr>
                    <tr><th>Feedback Delay</th><td>{{ $metric->feedback_delay }}</td></tr>
                    <tr><th>Task Completed</th><td>{{ $metric->task_completed }}</td></tr>
                    <tr><th>Screenshot Count</th><td>{{ $metric->screenshot_count }}</td></tr>
                    <tr><th>Error Message Clarity</th><td>{{ $metric->error_message_clarity }}</td></tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3>UX Metrics Chart</h3>
        @if ($metric)
            <canvas id="reportMetricsChart"></canvas>
        @else
            <p class="muted">No chart available.</p>
        @endif
    </div>
</div>

<div class="card">
    <h3>Recommendation</h3>

    @if (!$result || empty($result->recommendation))
        <p class="muted">No recommendation available. Click "Run FastAPI Prediction" if metrics are available.</p>
    @else
        <ul>
            @foreach ($result->recommendation as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="card">
    <h3>Screenshot Evidence</h3>

    @if (!$run || $run->screenshots->isEmpty())
        <p class="muted">No screenshot evidence available.</p>
    @else
        <div class="grid grid-3">
            @foreach ($run->screenshots as $screenshot)
                <div class="screenshot-box">
                    <strong>{{ $screenshot->label ?? 'Screenshot' }}</strong>
                    <p class="muted">{{ $screenshot->file_path }}</p>

                    @if (str_starts_with($screenshot->file_path, 'http'))
                        <img src="{{ $screenshot->file_path }}" alt="{{ $screenshot->label ?? 'Screenshot' }}">
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
                        <td>{{ $log->event_label ?? 'N/A' }}</td>
                        <td>{{ $log->event_value ?? 'N/A' }}</td>
                        <td>{{ $log->event_time ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h3>Report Conclusion</h3>
    <p>{{ $report->conclusion ?? 'No conclusion available yet.' }}</p>

    <p class="muted">
        This dashboard provides AI-assisted UX friction diagnosis based on collected metrics.
        It supports human UX evaluation, but it does not replace human judgment.
    </p>
</div>
@endsection

@if ($metric)
@push('scripts')
<script>
    const reportMetricsCtx = document.getElementById('reportMetricsChart');

    new Chart(reportMetricsCtx, {
        type: 'bar',
        data: {
            labels: ['Completion Time', 'Retry Count', 'Error Count', 'Failed Clicks', 'Feedback Delay'],
            datasets: [{
                label: 'UX Metrics',
                data: [
                    {{ $metric->completion_time }},
                    {{ $metric->retry_count }},
                    {{ $metric->error_count }},
                    {{ $metric->failed_clicks }},
                    {{ $metric->feedback_delay }}
                ]
            }]
        }
    });
</script>
@endpush
@endif
