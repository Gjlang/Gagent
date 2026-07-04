@extends('layouts.app')

@section('title', 'Test Run Detail')

@section('content')
@php
    $metric = $testRun->uxMetric;
    $result = $testRun->frictionResult;
    $level = $result?->friction_level ?? 'Not predicted';
    $badgeClass = match ($level) {
        'Low' => 'badge-low',
        'Medium' => 'badge-medium',
        'High' => 'badge-high',
        default => 'badge-neutral',
    };
@endphp

<div class="grid grid-3">
    <div class="card">
        <div class="muted">Test Run ID</div>
        <div class="stat-value">{{ $testRun->id }}</div>
    </div>

    <div class="card">
        <div class="muted">Friction Level</div>
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
</div>

<div class="card">
    <h3>Run Information</h3>
    <p><strong>Project:</strong> {{ $testRun->project?->project_name ?? 'N/A' }}</p>
    <p><strong>Flow Type:</strong> {{ $testRun->flow_type ?? 'N/A' }}</p>
    <p><strong>Page URL:</strong> {{ $testRun->page_url ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ $testRun->status }}</p>
    <p><strong>Started At:</strong> {{ $testRun->started_at ?? 'N/A' }}</p>
    <p><strong>Completed At:</strong> {{ $testRun->completed_at ?? 'N/A' }}</p>

    @if ($testRun->report)
        <a class="btn" href="{{ route('reports.show', $testRun->report) }}">Open Report</a>
    @endif
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>UX Metrics</h3>

        @if (!$metric)
            <p class="muted">No UX metrics found.</p>
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
        <h3>Metrics Chart</h3>
        @if ($metric)
            <canvas id="metricsChart"></canvas>
        @else
            <p class="muted">No chart available.</p>
        @endif
    </div>
</div>

<div class="card">
    <h3>Recommendations</h3>

    @if (!$result || empty($result->recommendation))
        <p class="muted">No recommendation available.</p>
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

    @if ($testRun->screenshots->isEmpty())
        <p class="muted">No screenshots available.</p>
    @else
        <div class="grid grid-3">
            @foreach ($testRun->screenshots as $screenshot)
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
                        <td>{{ $log->event_label ?? 'N/A' }}</td>
                        <td>{{ $log->event_value ?? 'N/A' }}</td>
                        <td>{{ $log->event_time ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@if ($metric)
@push('scripts')
<script>
    const metricsCtx = document.getElementById('metricsChart');

    new Chart(metricsCtx, {
        type: 'bar',
        data: {
            labels: ['Clicks', 'Scrolls', 'Keyboard', 'Retries', 'Errors', 'Failed Clicks'],
            datasets: [{
                label: 'UX Metrics',
                data: [
                    {{ $metric->click_count }},
                    {{ $metric->scroll_count }},
                    {{ $metric->keyboard_count }},
                    {{ $metric->retry_count }},
                    {{ $metric->error_count }},
                    {{ $metric->failed_clicks }}
                ]
            }]
        }
    });
</script>
@endpush
@endif
