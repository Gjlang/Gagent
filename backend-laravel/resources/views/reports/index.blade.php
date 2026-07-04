@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="card">
    <h3>UX Friction Reports</h3>

    @if ($reports->isEmpty())
        <p class="muted">No reports found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Run Code</th>
                    <th>Final Friction</th>
                    <th>Confidence</th>
                    <th>Generated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reports as $report)
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
                        <td>{{ $report->title }}</td>
                        <td>{{ $run?->project?->name ?? 'N/A' }}</td>
                        <td>{{ $run?->run_code ?? 'N/A' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>{{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                        <td>{{ $report->generated_at ?? 'N/A' }}</td>
                        <td><a class="btn" href="{{ route('reports.show', $report) }}">View Report</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $reports->links() }}
    @endif
</div>
@endsection
