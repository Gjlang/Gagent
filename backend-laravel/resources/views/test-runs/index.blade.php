@extends('layouts.app')

@section('title', 'Test Runs')
@section('kicker', 'Automation History')

@section('content')
@php $runCollection = $testRuns ?? collect(); @endphp

<div class="g-page-header">
    <div>
        <h2>Test Run Registry</h2>
        <p>Review all saved UX test executions, prediction status, platform, confidence, and generated report actions.</p>
    </div>
    <div class="g-actions">
        {{-- <a class="g-btn g-btn-primary" href="{{ route('live-tests.create') }}">Run Live Test</a> --}}
        <a class="g-btn" href="{{ route('android-tests.create') }}">Run Android Test</a>
    </div>
</div>

<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 12px;">
        <h3>All Test Runs</h3>
        <span class="g-badge badge-final">{{ method_exists($runCollection, 'total') ? $runCollection->total() : $runCollection->count() }} records</span>
    </div>

    @if ($runCollection->isEmpty())
        <div class="g-empty"><strong>No test runs found.</strong>Run a live website test or save Android metrics first.</div>
    @else
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Run Code</th>
                        <th>Project</th>
                        <th>Flow / Platform</th>
                        <th>Status</th>
                        <th>Friction Level</th>
                        <th>Confidence</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runCollection as $run)
                        @php
                            $result = $run->finalFrictionResult;
                            $level = $result?->friction_level ?? 'Not predicted';
                            $badgeClass = match ($level) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };
                            $status = strtolower($run->status ?? 'unknown');
                            $statusClass = 'g-status-' . preg_replace('/[^a-z0-9]+/', '-', $status);
                        @endphp
                        <tr>
                            <td><strong>{{ $run->run_code ?? 'N/A' }}</strong><div class="g-table-meta">{{ $run->run_mode ?? $run->scenario_type ?? 'standard' }}</div></td>
                            <td>{{ $run->project?->name ?? 'N/A' }}</td>
                            <td>{{ $run->flow_type ?? 'N/A' }}<div class="g-table-meta">{{ $run->platform ?? $run->viewport_type ?? 'web' }}</div></td>
                            <td><span class="g-status-badge {{ $statusClass }}">{{ $run->status ?? 'N/A' }}</span></td>
                            <td><span class="g-badge {{ $badgeClass }}">{{ $level }}</span></td>
                            <td>{{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                            <td>{{ optional($run->created_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                            <td><a class="g-btn g-btn-primary" href="{{ route('test-runs.show', $run) }}">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="g-pager">{{ $runCollection->links() }}</div>
    @endif
</div>
@endsection
