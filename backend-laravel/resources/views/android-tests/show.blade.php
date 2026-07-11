@extends('layouts.app')

@section('title', 'Android Test Result')

@section('content')
@php
    $metric = $testRun->uxMetric;
    $android = $testRun->androidResult;
@endphp

<div class="g-page-header">
    <div>
        <div class="g-soft-label">
            Android Automation Run
        </div>

        <h2>{{ $testRun->run_code }}</h2>

        <p>
            Review Android Appium metrics and
            AI friction prediction.
        </p>
    </div>

    <div class="g-actions">
        <a
            class="g-btn"
            href="{{ route('android-tests.create') }}"
        >
            New Android Test
        </a>
    </div>
</div>

@if (session('success'))
    <div class="g-alert g-alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="g-alert g-alert-error">
        {{ session('error') }}
    </div>
@endif

@if ($testRun->error_message)
    <div class="g-alert g-alert-error">
        <strong>Last error:</strong>
        {{ $testRun->error_message }}
    </div>
@endif

<div class="g-card">
    <h3>Test Configuration</h3>

    <div class="g-kv">
        <div class="g-kv-row">
            <span>Status</span>
            <span>{{ strtoupper($testRun->status) }}</span>
        </div>

        <div class="g-kv-row">
            <span>Test Mode</span>
            <span>{{ $testRun->test_mode ?? '-' }}</span>
        </div>

        <div class="g-kv-row">
            <span>Flow</span>
            <span>{{ $testRun->flow_type }}</span>
        </div>

        <div class="g-kv-row">
            <span>Device</span>
            <span>{{ $testRun->device_name }}</span>
        </div>

        <div class="g-kv-row">
            <span>Package</span>
            <span>
                {{ $testRun->target_app_package ?? '-' }}
            </span>
        </div>

        <div class="g-kv-row">
            <span>Activity</span>
            <span>
                {{ $testRun->target_app_activity ?? '-' }}
            </span>
        </div>

        <div class="g-kv-row">
            <span>APK</span>
            <span>{{ $testRun->apk_path ?? '-' }}</span>
        </div>

        <div class="g-kv-row">
            <span>Maximum Duration</span>
            <span>
                {{ $testRun->max_duration_seconds }} seconds
            </span>
        </div>
    </div>

    @if (
        in_array(
            $testRun->status,
            ['pending', 'failed', 'completed'],
            true
        )
    )
        <form
            method="POST"
            action="{{ route(
                'android-tests.run',
                $testRun
            ) }}"
            style="margin-top: 18px;"
        >
            @csrf

            <button
                class="g-btn g-btn-primary"
                type="submit"
            >
                {{ $testRun->status === 'completed'
                    ? 'Run Android Test Again'
                    : 'Run Android Appium Test'
                }}
            </button>
        </form>
    @elseif ($testRun->status === 'running')
        <div
            class="g-alert"
            style="margin-top: 18px;"
        >
            Android Appium test is currently running.
        </div>
    @endif
</div>

<div class="g-grid g-grid-2">
    <div class="g-card">
        <h3>Collected Android Metrics</h3>

        @if (!$metric)
            <p class="muted">
                No Android metrics have been collected yet.
            </p>
        @else
            <table>
                <tbody>
                    @foreach ([
                        'task_completed' => 'Task Completed',
                        'task_failed' => 'Task Failed',
                        'completion_time' => 'Completion Time',
                        'click_count' => 'Click Count',
                        'scroll_count' => 'Scroll Count',
                        'keyboard_count' => 'Keyboard Count',
                        'retry_count' => 'Retry Count',
                        'error_count' => 'Error Count',
                        'failed_clicks' => 'Failed Clicks',
                        'unnecessary_clicks' => 'Unnecessary Clicks',
                        'path_deviation_score' => 'Path Deviation Score',
                        'app_launch_time_ms' => 'App Launch Time MS',
                        'screen_load_time_ms' => 'Screen Load Time MS',
                        'feedback_delay_ms' => 'Feedback Delay MS',
                        'interaction_response_time_ms' => 'Interaction Response Time MS',
                        'finish_time_ms' => 'Finish Time MS',
                        'error_message_present' => 'Error Message Present',
                        'error_message_clarity' => 'Error Message Clarity',
                        'popup_detected' => 'Popup Detected',
                        'overlay_blocks_action' => 'Overlay Blocks Action',
                        'timeout_occurred' => 'Timeout Occurred',
                        'crash_detected' => 'Crash Detected',
                        'anr_detected' => 'ANR Detected',
                    ] as $field => $label)
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $metric->{$field} }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="g-card">
        <h3>Android AI Prediction</h3>

        @if (!$android)
            <p class="muted">
                No Android prediction has been saved.
            </p>
        @else
            <p>
                <strong>Friction Level:</strong>

                <span class="g-badge">
                    {{ $android->friction_level }}
                </span>
            </p>

            <p>
                <strong>Confidence:</strong>

                {{ number_format(
                    ($android->confidence_score ?? 0) * 100,
                    1
                ) }}%
            </p>

            <p>
                <strong>Model:</strong>
                {{ $android->model_name }}
            </p>

            <h4>Class Probabilities</h4>

            <pre>{{ json_encode(
                $android->class_probabilities,
                JSON_PRETTY_PRINT
            ) }}</pre>

            <h4>Recommendations</h4>

            <ul>
                @forelse (
                    ($android->recommendations ?? [])
                    as $recommendation
                )
                    <li>{{ $recommendation }}</li>
                @empty
                    <li>No recommendations returned.</li>
                @endforelse
            </ul>
        @endif
    </div>
</div>

<div class="g-card">
    <h3>Android Testing Limitation</h3>

    <p class="muted">
        Real Android app testing uses generic Appium
        exploration. It supports simple flows only and
        does not bypass authentication, CAPTCHA, payment,
        two-factor authentication, or private application
        protections. The controlled dummy Android app
        remains the main reliable test target for the FYP.
    </p>
</div>
@endsection
