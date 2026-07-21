@extends('layouts.app')

@section('title', 'Project Detail')
@section('kicker', 'Project Suite')

@section('content')
@php
    $runs = $project->testRuns ?? collect();
    $latestRun = $runs->sortByDesc('created_at')->first();
    $latestResult = $latestRun?->finalFrictionResult;
    $level = $latestResult?->friction_level ?? 'Not predicted';
    $levelClass = match ($level) {
        'Low' => 'badge-low',
        'Medium' => 'badge-medium',
        'High' => 'badge-high',
        default => 'badge-neutral',
    };
    $status = strtolower($project->status ?? 'unknown');
@endphp

<div class="g-page-header">
    <div>
        <h2>{{ $project->name ?? 'Project Detail' }}</h2>
        <p>{{ $project->description ?? 'No description provided.' }}</p>
    </div>
   <div class="g-actions">
    <a
        class="g-btn"
        href="{{ route('projects.index') }}"
    >
        Back to Projects
    </a>

    @if ($runs->count() >= 2)
        <a
            class="g-btn"
            href="{{ route('projects.comparison', $project) }}"
        >
            Compare Test Runs
        </a>
    @endif

    <a
        class="g-btn g-btn-primary"
        {{-- href="{{ route('live-tests.create') }}" --}}
    >
        New Live Test
    </a>
</div>
</div>

<div class="g-grid g-grid-4">
    <div class="g-metric-card">
        <div class="g-metric-label">Target Type</div>
        <div class="g-metric-value" style="font-size: 24px;">{{ $project->target_type ?? 'N/A' }}</div>
        <div class="g-metric-sub">Configured source</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">Status</div>
        <div style="margin-top: 16px;"><span class="g-status-badge g-status-{{ preg_replace('/[^a-z0-9]+/', '-', $status) }}">{{ $project->status ?? 'N/A' }}</span></div>
        <div class="g-metric-sub">Project state</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">Linked Test Runs</div>
        <div class="g-metric-value">{{ $runs->count() }}</div>
        <div class="g-metric-sub">Saved in database</div>
    </div>
    <div class="g-metric-card">
        <div class="g-metric-label">Latest Friction</div>
        <div style="margin-top: 16px;"><span class="g-badge {{ $levelClass }}">{{ $level }}</span></div>
        <div class="g-metric-sub">Most recent final prediction</div>
    </div>
</div>

<div class="g-layout-2-1" style="margin-top: 16px;">
    <div class="g-card">
        <h3>Related Test Runs</h3>
        @if ($runs->isEmpty())
            <div class="g-empty"><strong>No test runs linked yet.</strong>Create a live website test or Android test for this project.</div>
        @else
            <div class="g-table-wrap">
                <table class="g-table">
                    <thead>
                        <tr>
                            <th>Run Code</th>
                            <th>Flow</th>
                            <th>Scenario</th>
                            <th>Status</th>
                            <th>Final Friction</th>
                            <th>Report</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs->sortByDesc('created_at') as $run)
                            @php
                                $result = $run->finalFrictionResult;
                                $runLevel = $result?->friction_level ?? 'Not predicted';
                                $runBadge = match ($runLevel) {
                                    'Low' => 'badge-low',
                                    'Medium' => 'badge-medium',
                                    'High' => 'badge-high',
                                    default => 'badge-neutral',
                                };
                                $runStatus = strtolower($run->status ?? 'unknown');
                            @endphp
                            <tr>
                                <td><strong>{{ $run->run_code ?? 'N/A' }}</strong></td>
                                <td>{{ $run->flow_type ?? 'N/A' }}</td>
                                <td>{{ $run->scenario_type ?? $run->run_mode ?? 'N/A' }}</td>
                                <td><span class="g-status-badge g-status-{{ preg_replace('/[^a-z0-9]+/', '-', $runStatus) }}">{{ $run->status ?? 'N/A' }}</span></td>
                                <td><span class="g-badge {{ $runBadge }}">{{ $runLevel }}</span></td>
                                <td>
                                    @if ($run->report)
                                        <a class="g-btn g-btn-ghost" href="{{ route('reports.show', $run->report) }}">Report</a>
                                    @else
                                        <span class="g-muted">Not generated</span>
                                    @endif
                                </td>
                                <td><a class="g-btn g-btn-primary" href="{{ route('test-runs.show', $run) }}">View Run</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <aside class="g-panel">
        <h3>Project Configuration</h3>
        <div class="g-kv">
            <div class="g-kv-row"><span>Project</span><span>{{ $project->name ?? 'N/A' }}</span></div>
            <div class="g-kv-row"><span>Target URL</span><span>{{ $project->target_url ?? 'N/A' }}</span></div>
            <div class="g-kv-row"><span>Created</span><span>{{ optional($project->created_at)->format('Y-m-d') ?? 'N/A' }}</span></div>
            <div class="g-kv-row"><span>Updated</span><span>{{ optional($project->updated_at)->format('Y-m-d') ?? 'N/A' }}</span></div>
        </div>
        <div style="margin-top: 16px; display: grid; gap: 10px;">
            <a class="g-btn g-btn-primary g-btn-block" href="{{ route('live-tests.create') }}">Run Live Website Test</a>
            <a class="g-btn g-btn-block" href="{{ route('android-tests.create') }}">Run Android Test</a>
        </div>
    </aside>
</div>
@endsection
