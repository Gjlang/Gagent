@extends('layouts.app')

@section('title', 'Reports')
@section('kicker', 'UX Audit Output')

@section('content')
@php $reportCollection = $reports ?? collect(); @endphp

<div class="g-page-header">
    <div>
        <h2>UX Friction Reports</h2>
        <p>Open generated AI-assisted reports with executive summaries, predictions, evidence, metrics, and recommendations.</p>
    </div>
    <div class="g-actions">
        <a class="g-btn g-btn-primary" href="{{ route('test-runs.index') }}">Generate From Test Run</a>
    </div>
</div>

<div class="g-card">
    <div class="g-split-row" style="margin-bottom: 12px;">
        <h3>Report Library</h3>
        <span class="g-badge badge-final">{{ method_exists($reportCollection, 'total') ? $reportCollection->total() : $reportCollection->count() }} reports</span>
    </div>

    @if ($reportCollection->isEmpty())
        <div class="g-empty"><strong>No reports yet.</strong>Run a test and generate a report to see results here.</div>
    @else
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Project</th>
                        <th>Test Run</th>
                        <th>Friction Level</th>
                        <th>Confidence</th>
                        <th>Generated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportCollection as $report)
                        @php
                            $run = $report->testRun;
                            $result = $run?->finalFrictionResult;
                            $level = $result?->friction_level ?? 'Not predicted';
                            $badgeClass = match ($level) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $report->title ?? 'Untitled Report' }}</strong><div class="g-table-meta">{{ \Illuminate\Support\Str::limit($report->summary ?? 'No summary', 76) }}</div></td>
                            <td>{{ $run?->project?->name ?? 'N/A' }}</td>
                            <td>{{ $run?->run_code ?? 'N/A' }}</td>
                            <td><span class="g-badge {{ $badgeClass }}">{{ $level }}</span></td>
                            <td>{{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                            <td>{{ optional($report->generated_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                            <td><a class="g-btn g-btn-primary" href="{{ route('reports.show', $report) }}">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="g-pager">{{ $reportCollection->links() }}</div>
    @endif
</div>
@endsection
