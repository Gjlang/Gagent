@extends('layouts.app')

@section('title', 'Project Comparison')
@section('kicker', 'Improvement Tracking')

@section('content')
@php
    $statusClasses = [
        'Improved' => 'comparison-good',
        'Partially Improved' => 'comparison-warning',
        'No Significant Change' => 'comparison-neutral',
        'Regressed' => 'comparison-bad',
    ];

    $resultClass = $comparison
        ? ($statusClasses[$comparison['overall_status']]
            ?? 'comparison-neutral')
        : 'comparison-neutral';

    $formatMetricValue = function (
        $value,
        string $type,
        string $unit = ''
    ) {
        if ($type === 'boolean') {
            return $value ? 'Yes' : 'No';
        }

        if (!is_numeric($value)) {
            return 'N/A';
        }

        $formatted = number_format(
            (float) $value,
            abs((float) $value - round((float) $value)) > 0.001
                ? 2
                : 0
        );

        return trim($formatted . ' ' . $unit);
    };

    $screenshotSource = function ($screenshot) {
        if (!$screenshot) {
            return null;
        }

        $path = $screenshot->file_path ?? '';

        if (!$path) {
            return null;
        }

        $cleanPath = ltrim($path, '/');

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    };
@endphp

<style>
    .comparison-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
    }

    .comparison-field {
        display: grid;
        gap: 8px;
    }

    .comparison-field label {
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    .comparison-select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font: inherit;
    }

    .comparison-summary {
        padding: 22px;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #ffffff;
    }

    .comparison-good {
        border-left: 5px solid #16a34a;
    }

    .comparison-warning {
        border-left: 5px solid #d97706;
    }

    .comparison-neutral {
        border-left: 5px solid #64748b;
    }

    .comparison-bad {
        border-left: 5px solid #dc2626;
    }

    .comparison-arrow {
        color: #64748b;
        font-size: 28px;
        font-weight: 700;
        text-align: center;
    }

    .comparison-delta-positive {
        color: #15803d;
        font-weight: 700;
    }

    .comparison-delta-negative {
        color: #b91c1c;
        font-weight: 700;
    }

    .comparison-delta-neutral {
        color: #64748b;
        font-weight: 700;
    }

    .comparison-list {
        display: grid;
        gap: 10px;
        margin: 0;
        padding-left: 20px;
    }

    .comparison-evidence-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 14px;
    }

    .comparison-evidence {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        background: #ffffff;
    }

    .comparison-evidence img {
        display: block;
        width: 100%;
        height: 280px;
        object-fit: contain;
        background: #f8fafc;
    }

    .comparison-evidence-body {
        padding: 14px;
    }

    .comparison-missing-image {
        display: grid;
        min-height: 280px;
        place-items: center;
        background: #f8fafc;
        color: #64748b;
    }

    @media (max-width: 900px) {
        .comparison-form {
            grid-template-columns: 1fr;
        }

        .comparison-evidence-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="g-page-header">
    <div>
        <h2>{{ $project->name }} Improvement Comparison</h2>
        <p>
            Compare two completed test runs from the same
            project to identify UX improvements and regressions.
        </p>
    </div>

    <div class="g-actions">
        <a
            class="g-btn"
            href="{{ route('projects.show', $project) }}"
        >
            Back to Project
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="g-card" style="margin-bottom: 16px; border-left: 5px solid #dc2626;">
        <strong>Comparison could not be completed.</strong>

        <ul style="margin-bottom: 0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="g-card" style="margin-bottom: 16px; border-left: 5px solid #dc2626;">
        {{ session('error') }}
    </div>
@endif

@if (!isset($savedComparison))
<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 16px;">
        <div>
            <h3>Select Test Runs</h3>
            <p class="g-muted">
                Only completed runs with UX metrics and a final
                friction prediction are available.
            </p>
        </div>

        <span class="g-badge badge-final">
            {{ $eligibleRuns->count() }} eligible runs
        </span>
    </div>

    @if ($eligibleRuns->count() < 2)
        <div class="g-empty">
            <strong>At least two eligible test runs are required.</strong>
            Complete another test run and generate its final friction
            prediction before using this comparison.
        </div>
    @else
        <form
            method="GET"
            action="{{ route('projects.comparison', $project) }}"
            class="comparison-form"
        >
            <div class="comparison-field">
                <label for="before_run">
                    Before Test Run
                </label>

                <select
                    id="before_run"
                    name="before_run"
                    class="comparison-select"
                    required
                >
                    <option value="">
                        Select the older test run
                    </option>

                    @foreach ($eligibleRuns as $run)
                        <option
                            value="{{ $run->id }}"
                            @selected(
                                (string) old(
                                    'before_run',
                                    request('before_run')
                                )
                                === (string) $run->id
                            )
                        >
                            {{ $run->run_code }}
                            —
                            {{ ucfirst($run->platform ?? 'web') }}
                            —
                            {{ $run->finalFrictionResult?->friction_level ?? 'Not predicted' }}
                            —
                            {{ optional($run->completed_at)->format('Y-m-d H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="comparison-field">
                <label for="after_run">
                    After Test Run
                </label>

                <select
                    id="after_run"
                    name="after_run"
                    class="comparison-select"
                    required
                >
                    <option value="">
                        Select the newer test run
                    </option>

                    @foreach ($eligibleRuns as $run)
                        <option
                            value="{{ $run->id }}"
                            @selected(
                                (string) old(
                                    'after_run',
                                    request('after_run')
                                )
                                === (string) $run->id
                            )
                        >
                            {{ $run->run_code }}
                            —
                            {{ ucfirst($run->platform ?? 'web') }}
                            —
                            {{ $run->finalFrictionResult?->friction_level ?? 'Not predicted' }}
                            —
                            {{ optional($run->completed_at)->format('Y-m-d H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button
                type="submit"
                class="g-btn g-btn-primary"
            >
                Compare Runs
            </button>
        </form>
    @endif
</div>
@endif

@if ($comparison)
    <div
        class="comparison-summary {{ $resultClass }}"
        style="margin-top: 16px;"
    >
        <div class="g-split-row">
            <div>
                <div class="g-soft-label">
                    Overall Comparison Result
                </div>

                <h2 style="margin: 6px 0;">
                    {{ $comparison['overall_status'] }}
                </h2>

                <p style="margin-bottom: 0;">
                    {{ $comparison['summary'] }}
                </p>
            </div>

            <span class="g-badge badge-final">
                Same Project Confirmed
            </span>
        </div>
    </div>

    @if (isset($savedComparison))
        <div
            class="g-card"
            style="margin-top: 16px;"
        >
            <div class="g-split-row">
                <div>
                    <div class="g-soft-label">
                        Explanation Layer Only
                    </div>

                    <h3 style="margin-top: 6px;">
                        AI Comparison Explanation
                    </h3>

                    <p
                        class="g-muted"
                        style="margin-top: 7px;"
                    >
                        Ollama explains the saved before-and-after
                        comparison. It does not change the machine-
                        learning predictions, metric values, or UX
                        scores.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'comparisons.generate-explanation',
                            $savedComparison
                        )
                    }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="g-btn g-btn-primary"
                    >
                        {{
                            $savedComparison->llm_generated_at
                                ? 'Regenerate AI Explanation'
                                : 'Generate AI Explanation'
                        }}
                    </button>
                </form>
            </div>

            @if ($savedComparison->llm_generated_at)
                <div
                    class="g-grid g-grid-2"
                    style="margin-top: 18px;"
                >
                    <div class="g-panel">
                        <div class="g-soft-label">
                            Overall Summary
                        </div>

                        <p style="margin-top: 8px;">
                            {{
                                $savedComparison->llm_summary
                                ?? 'No summary was returned.'
                            }}
                        </p>

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Assessment
                        </div>

                        <p style="margin-top: 8px;">
                            {{
                                $savedComparison->llm_assessment
                                ?? 'No assessment was returned.'
                            }}
                        </p>
                    </div>

                    <div class="g-panel">
                        <div class="g-soft-label">
                            Key Improvements
                        </div>

                        @forelse (
                            $savedComparison->llm_improvements ?? []
                            as $item
                        )
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li>{{ $item }}</li>
                            </ul>
                        @empty
                            <p class="g-muted">
                                No specific improvements were identified.
                            </p>
                        @endforelse

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Remaining Risks
                        </div>

                        @forelse (
                            $savedComparison->llm_regressions ?? []
                            as $item
                        )
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li>{{ $item }}</li>
                            </ul>
                        @empty
                            <p class="g-muted">
                                No measured regressions were identified.
                            </p>
                        @endforelse

                        <div
                            class="g-soft-label"
                            style="margin-top: 18px;"
                        >
                            Recommended Next Actions
                        </div>

                        @forelse (
                            $savedComparison->llm_next_actions ?? []
                            as $item
                        )
                            <ul
                                style="
                                    padding-left: 18px;
                                    line-height: 1.7;
                                "
                            >
                                <li>{{ $item }}</li>
                            </ul>
                        @empty
                            <p class="g-muted">
                                No additional next actions were returned.
                            </p>
                        @endforelse
                    </div>
                </div>

                <div
                    class="g-kv"
                    style="margin-top: 16px;"
                >
                    <div class="g-kv-row">
                        <span>Provider</span>
                        <span>
                            {{
                                $savedComparison->llm_provider
                                ?? 'N/A'
                            }}
                        </span>
                    </div>

                    <div class="g-kv-row">
                        <span>Model</span>
                        <span>
                            {{
                                $savedComparison->llm_model
                                ?? 'N/A'
                            }}
                        </span>
                    </div>

                    <div class="g-kv-row">
                        <span>Generated</span>
                        <span>
                            {{
                                optional(
                                    $savedComparison->llm_generated_at
                                )->format('Y-m-d H:i')
                                ?? 'N/A'
                            }}
                        </span>
                    </div>
                </div>
            @else
                <div
                    class="g-empty"
                    style="margin-top: 16px;"
                >
                    <strong>
                        No AI comparison explanation generated.
                    </strong>

                    Click “Generate AI Explanation” to explain
                    the existing comparison.
                </div>
            @endif
        </div>
    @endif

    <div
        class="g-grid g-grid-4"
        style="margin-top: 16px;"
    >
        <div class="g-metric-card">
            <div class="g-metric-label">
                Before Friction
            </div>

            <div
                class="g-metric-value"
                style="font-size: 28px;"
            >
                {{ $comparison['friction_comparison']['before'] }}
            </div>

            <div class="g-metric-sub">
                {{ $comparison['before']->run_code }}
            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                After Friction
            </div>

            <div
                class="g-metric-value"
                style="font-size: 28px;"
            >
                {{ $comparison['friction_comparison']['after'] }}
            </div>

            <div class="g-metric-sub">
                {{ $comparison['after']->run_code }}
            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                Before UX Score
            </div>

            <div class="g-metric-value">
                {{ $comparison['before_score'] }}
            </div>

            <div class="g-metric-sub">
                Derived score out of 100
            </div>
        </div>

        <div class="g-metric-card">
            <div class="g-metric-label">
                After UX Score
            </div>

            <div class="g-metric-value">
                {{ $comparison['after_score'] }}
            </div>

            <div class="g-metric-sub">
                @if ($comparison['score_difference'] > 0)
                    +{{ $comparison['score_difference'] }}
                    points improvement
                @elseif ($comparison['score_difference'] < 0)
                    {{ $comparison['score_difference'] }}
                    points
                @else
                    No score change
                @endif
            </div>
        </div>
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <div class="g-split-row">
            <div>
                <h3>Metric Comparison</h3>
                <p class="g-muted">
                    Green indicates improvement, red indicates
                    regression, and grey indicates no change.
                </p>
            </div>

            <div class="g-actions">
                <span class="g-badge badge-low">
                    {{ count($comparison['improved_metrics']) }}
                    improved
                </span>

                <span class="g-badge badge-high">
                    {{ count($comparison['worsened_metrics']) }}
                    worsened
                </span>
            </div>
        </div>

        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Difference</th>
                        <th>Result</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($comparison['metrics'] as $metric)
                        @php
                            $deltaClass = match ($metric['status']) {
                                'improved' => 'comparison-delta-positive',
                                'worsened' => 'comparison-delta-negative',
                                default => 'comparison-delta-neutral',
                            };

                            $statusText = ucfirst(
                                $metric['status']
                            );
                        @endphp

                        <tr>
                            <td>
                                <strong>
                                    {{ $metric['label'] }}
                                </strong>
                            </td>

                            <td>
                                {{
                                    $formatMetricValue(
                                        $metric['before'],
                                        $metric['type'],
                                        $metric['unit']
                                    )
                                }}
                            </td>

                            <td>
                                {{
                                    $formatMetricValue(
                                        $metric['after'],
                                        $metric['type'],
                                        $metric['unit']
                                    )
                                }}
                            </td>

                            <td>
                                @if (
                                    $metric['type'] === 'number'
                                    && $metric['difference'] !== null
                                )
                                    {{
                                        $metric['difference'] > 0
                                            ? '+'
                                            : ''
                                    }}{{
                                        number_format(
                                            $metric['difference'],
                                            2
                                        )
                                    }}
                                    {{ $metric['unit'] }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="{{ $deltaClass }}">
                                {{ $statusText }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div
        class="g-layout-2-1"
        style="margin-top: 16px;"
    >
        <div class="g-card">
            <h3>Recommendation Changes</h3>

            <div
                class="g-grid"
                style="grid-template-columns: repeat(3, minmax(0, 1fr));"
            >
                <div>
                    <h4>Resolved Recommendations</h4>

                    @if (
                        empty(
                            $comparison['resolved_recommendations']
                        )
                    )
                        <p class="g-muted">
                            No previous recommendations were removed.
                        </p>
                    @else
                        <ul class="comparison-list">
                            @foreach (
                                $comparison['resolved_recommendations']
                                as $recommendation
                            )
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div>
                    <h4>Remaining Recommendations</h4>

                    @if (
                        empty(
                            $comparison['remaining_recommendations']
                        )
                    )
                        <p class="g-muted">
                            No recommendations remained unchanged.
                        </p>
                    @else
                        <ul class="comparison-list">
                            @foreach (
                                $comparison['remaining_recommendations']
                                as $recommendation
                            )
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div>
                    <h4>New Recommendations</h4>

                    @if (
                        empty(
                            $comparison['new_recommendations']
                        )
                    )
                        <p class="g-muted">
                            No new recommendations were introduced.
                        </p>
                    @else
                        <ul class="comparison-list">
                            @foreach (
                                $comparison['new_recommendations']
                                as $recommendation
                            )
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <h3>Run Information</h3>

            <div class="g-kv">
                <div class="g-kv-row">
                    <span>Project</span>
                    <span>{{ $project->name }}</span>
                </div>

                <div class="g-kv-row">
                    <span>Platform</span>
                    <span>
                        {{ ucfirst($comparison['before']->platform ?? 'web') }}
                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Before</span>
                    <span>
                        {{ $comparison['before']->run_code }}
                    </span>
                </div>

                <div class="g-kv-row">
                    <span>After</span>
                    <span>
                        {{ $comparison['after']->run_code }}
                    </span>
                </div>

                <div class="g-kv-row">
                    <span>Score Difference</span>
                    <span>
                        {{
                            $comparison['score_difference'] > 0
                                ? '+'
                                : ''
                        }}{{ $comparison['score_difference'] }}
                    </span>
                </div>
            </div>
        </aside>
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <h3>Before and After Screenshot Evidence</h3>

        @if (
            empty(
                $comparison['screenshot_pairs']
            )
        )
            <div class="g-empty">
                <strong>No screenshot evidence available.</strong>
                Screenshots will appear when either test run contains
                saved Playwright or Appium evidence.
            </div>
        @else
            @foreach (
                $comparison['screenshot_pairs']
                as $pair
            )
                @php
                    $beforeSource = $screenshotSource(
                        $pair['before']
                    );

                    $afterSource = $screenshotSource(
                        $pair['after']
                    );
                @endphp

                <div style="margin-top: 20px;">
                    <h4>
                        {{ $pair['key'] }}
                    </h4>

                    <div class="comparison-evidence-row">
                        <div class="comparison-evidence">
                            @if ($beforeSource)
                                <img
                                    src="{{ $beforeSource }}"
                                    alt="Before screenshot"
                                >
                            @else
                                <div class="comparison-missing-image">
                                    No matching before screenshot
                                </div>
                            @endif

                            <div class="comparison-evidence-body">
                                <strong>Before</strong>

                                <div class="g-muted g-small">
                                    {{ $comparison['before']->run_code }}
                                </div>
                            </div>
                        </div>

                        <div class="comparison-evidence">
                            @if ($afterSource)
                                <img
                                    src="{{ $afterSource }}"
                                    alt="After screenshot"
                                >
                            @else
                                <div class="comparison-missing-image">
                                    No matching after screenshot
                                </div>
                            @endif

                            <div class="comparison-evidence-body">
                                <strong>After</strong>

                                <div class="g-muted g-small">
                                    {{ $comparison['after']->run_code }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div
        class="g-card"
        style="margin-top: 16px;"
    >
        <h3>Score Explanation</h3>

        <p class="g-muted">
            The displayed UX score is a derived comparison score
            calculated from task completion, errors, failed clicks,
            retries, delays, blocking overlays, timeouts, crashes,
            and related UX metrics. It is separate from the machine
            learning confidence score and does not modify the AI
            prediction.
        </p>
    </div>
@endif
@endsection
