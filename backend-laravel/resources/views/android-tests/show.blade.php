@extends('layouts.app')

@section('title', 'Android Test Result')

@section('content')
@php
    $metric = $testRun->uxMetric;
    $android = $testRun->androidResult;
@endphp

<div class="card">
    <h3>Android Test Run</h3>

    <p><strong>Run Code:</strong> {{ $testRun->run_code }}</p>
    <p><strong>Project:</strong> {{ $testRun->project->name ?? '-' }}</p>
    <p><strong>Status:</strong> {{ $testRun->status }}</p>
    <p><strong>Flow Type:</strong> {{ $testRun->flow_type }}</p>
    <p><strong>Platform:</strong> {{ $testRun->platform }}</p>
    <p><strong>Automation Driver:</strong> {{ $testRun->automation_driver }}</p>
    <p><strong>Device:</strong> {{ $testRun->device_name }}</p>
    <p><strong>App Package:</strong> {{ $testRun->target_app_package }}</p>
    <p><strong>App Activity:</strong> {{ $testRun->target_app_activity }}</p>

    @if (!$android)
        <form method="POST" action="{{ route('android-tests.predict', $testRun) }}">
            @csrf
            <button class="btn" type="submit">Run Android Prediction</button>
        </form>
    @else
        <span class="badge badge-final">Android prediction saved</span>
    @endif
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Collected Android Metrics</h3>

        @if (!$metric)
            <p class="muted">No Android metrics found.</p>
        @else
            <table>
                <tbody>
                    <tr><th>Task Completed</th><td>{{ (int) $metric->task_completed }}</td></tr>
                    <tr><th>Task Failed</th><td>{{ (int) $metric->task_failed }}</td></tr>
                    <tr><th>Completion Time</th><td>{{ $metric->completion_time }}</td></tr>
                    <tr><th>Click Count</th><td>{{ $metric->click_count }}</td></tr>
                    <tr><th>Scroll Count</th><td>{{ $metric->scroll_count }}</td></tr>
                    <tr><th>Keyboard Count</th><td>{{ $metric->keyboard_count }}</td></tr>
                    <tr><th>Retry Count</th><td>{{ $metric->retry_count }}</td></tr>
                    <tr><th>Error Count</th><td>{{ $metric->error_count }}</td></tr>
                    <tr><th>Failed Clicks</th><td>{{ $metric->failed_clicks }}</td></tr>
                    <tr><th>Unnecessary Clicks</th><td>{{ $metric->unnecessary_clicks }}</td></tr>
                    <tr><th>Path Deviation Score</th><td>{{ $metric->path_deviation_score }}</td></tr>
                    <tr><th>App Launch Time MS</th><td>{{ $metric->app_launch_time_ms }}</td></tr>
                    <tr><th>Screen Load Time MS</th><td>{{ $metric->screen_load_time_ms }}</td></tr>
                    <tr><th>Feedback Delay MS</th><td>{{ $metric->feedback_delay_ms }}</td></tr>
                    <tr><th>Interaction Response Time MS</th><td>{{ $metric->interaction_response_time_ms }}</td></tr>
                    <tr><th>Finish Time MS</th><td>{{ $metric->finish_time_ms }}</td></tr>
                    <tr><th>Error Message Present</th><td>{{ (int) $metric->error_message_present }}</td></tr>
                    <tr><th>Error Message Clarity</th><td>{{ $metric->error_message_clarity }}</td></tr>
                    <tr><th>Popup Detected</th><td>{{ (int) $metric->popup_detected }}</td></tr>
                    <tr><th>Overlay Blocks Action</th><td>{{ (int) $metric->overlay_blocks_action }}</td></tr>
                    <tr><th>Timeout Occurred</th><td>{{ (int) $metric->timeout_occurred }}</td></tr>
                    <tr><th>Crash Detected</th><td>{{ (int) $metric->crash_detected }}</td></tr>
                    <tr><th>ANR Detected</th><td>{{ (int) $metric->anr_detected }}</td></tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3>Android Prediction Result</h3>

        @if (!$android)
            <p class="muted">No Android prediction saved yet.</p>
        @else
            <p>
                <strong>Friction Level:</strong>
                <span class="badge badge-{{ strtolower($android->friction_level) }}">
                    {{ $android->friction_level }}
                </span>
            </p>

            <p><strong>Model:</strong> {{ $android->model_name }}</p>
            <p><strong>Model Type:</strong> {{ $android->model_type }}</p>
            <p><strong>Confidence:</strong> {{ number_format(($android->confidence_score ?? 0) * 100, 1) }}%</p>

            <h4>Class Probabilities</h4>
            <pre>{{ json_encode($android->class_probabilities, JSON_PRETTY_PRINT) }}</pre>

            <h4>Recommendations</h4>
            <ul>
                @foreach (($android->recommendations ?? []) as $recommendation)
                    <li>{{ $recommendation }}</li>
                @endforeach
            </ul>

            <h4>Input Features Sent to FastAPI</h4>
            <pre>{{ json_encode($android->input_features, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
</div>

<div class="card">
    <h3>Phase 8 Scope Note</h3>
    <p class="muted">
        This Android module is an experimental extension of GAgent. The Web GAgent model remains the main model.
        The Android result is based on controlled Android UX metrics and should not be overclaimed as full real-world Android generalization.
    </p>
</div>
@endsection
