@extends('layouts.app')

@section('title', 'Live Website Test Details')

@section('content')
    <div class="card">
        <h2>{{ $testRun->run_code }}</h2>

        <p class="muted">
            Live website test run for UX friction detection.
        </p>

        <table>
            <tr>
                <th>Project</th>
                <td>{{ $testRun->project->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Target URL</th>
                <td>
                    <a href="{{ $testRun->target_url }}" target="_blank">
                        {{ $testRun->target_url }}
                    </a>
                </td>
            </tr>
            <tr>
                <th>Flow Type</th>
                <td>{{ $testRun->flow_type }}</td>
            </tr>
            <tr>
                <th>Viewport</th>
                <td>{{ $testRun->viewport_type }}</td>
            </tr>
            <tr>
                <th>Network</th>
                <td>{{ $testRun->network_condition }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge badge-neutral">{{ $testRun->status }}</span>
                </td>
            </tr>
            <tr>
                <th>Started At</th>
                <td>{{ optional($testRun->started_at)->format('Y-m-d H:i:s') ?? 'Not started' }}</td>
            </tr>
            <tr>
                <th>Completed At</th>
                <td>{{ optional($testRun->completed_at)->format('Y-m-d H:i:s') ?? 'Not completed' }}</td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ $testRun->duration_seconds ? number_format($testRun->duration_seconds, 2) . ' seconds' : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Playwright Exit Code</th>
                <td>{{ $testRun->playwright_exit_code ?? 'N/A' }}</td>
            </tr>
        </table>

        <br>

        @if ($testRun->status !== 'running')
            <form method="POST" action="{{ route('live-tests.run', $testRun) }}">
                @csrf
                <button type="submit" class="btn">
                    Run Live Test
                </button>
            </form>
        @else
            <p class="muted">Test is currently running.</p>
        @endif

        @if ($testRun->error_message)
            <div class="alert-error" style="margin-top: 16px;">
                <strong>Error:</strong> {{ $testRun->error_message }}
            </div>
        @endif
    </div>

    @if ($testRun->uxMetric)
        <div class="card">
            <h3>Collected UX Metrics</h3>

            <table>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>

                @foreach ($testRun->uxMetric->toArray() as $key => $value)
                    @if (!in_array($key, ['id', 'test_run_id', 'created_at', 'updated_at']))
                        <tr>
                            <td>{{ $key }}</td>
                            <td>
                                @if (is_bool($value))
                                    {{ $value ? '1' : '0' }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    @endif

    @if ($testRun->mainGAgentResult)
        <div class="card">
            <h3>Final GAgent Prediction</h3>

            @php
                $level = $testRun->mainGAgentResult->friction_level;
                $badgeClass = match ($level) {
                    'Low' => 'badge-low',
                    'Medium' => 'badge-medium',
                    'High' => 'badge-high',
                    default => 'badge-neutral',
                };
            @endphp

            <p>
                <strong>Friction Level:</strong>
                <span class="badge {{ $badgeClass }}">{{ $level ?? 'Unknown' }}</span>
            </p>

            <p>
                <strong>Confidence:</strong>
                {{ $testRun->mainGAgentResult->confidence_score !== null
                    ? number_format($testRun->mainGAgentResult->confidence_score * 100, 1) . '%'
                    : 'N/A' }}
            </p>

            @if ($testRun->mainGAgentResult->class_probabilities)
                <h4>Class Probabilities</h4>
                <pre>{{ json_encode($testRun->mainGAgentResult->class_probabilities, JSON_PRETTY_PRINT) }}</pre>
            @endif

            @if ($testRun->mainGAgentResult->recommendations)
                <h4>Recommendations</h4>
                <ul>
                    @foreach ($testRun->mainGAgentResult->recommendations as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if ($testRun->report)
        <div class="card">
            <h3>Generated Report</h3>

            <p>
                <strong>{{ $testRun->report->title }}</strong>
            </p>

            <p>{{ $testRun->report->summary }}</p>
            <p>{{ $testRun->report->conclusion }}</p>

            <a href="{{ route('reports.show', $testRun->report) }}" class="btn">
                Open Full Report
            </a>
        </div>
    @endif

    @if ($testRun->raw_metrics_path)
        <div class="card">
            <h3>Raw Metrics Path</h3>
            <pre>{{ $testRun->raw_metrics_path }}</pre>
        </div>
    @endif
@endsection
