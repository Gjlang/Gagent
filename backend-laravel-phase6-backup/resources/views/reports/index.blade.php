@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="card">
    <h3>UX Friction Reports</h3>

    @if ($reports->isEmpty())
        <p class="muted">No reports found. Run the demo seeder first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Flow Type</th>
                    <th>Friction</th>
                    <th>Confidence</th>
                    <th>Generated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reports as $report)
                    @php
                        $run = $report->testRun;
                        $result = $run?->frictionResult;
                        $level = $result?->friction_level ?? 'Not predicted';
                        $badgeClass = match ($level) {
                            'Low' => 'badge-low',
                            'Medium' => 'badge-medium',
                            'High' => 'badge-high',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->title }}</td>
                        <td>{{ $run?->project?->project_name ?? 'N/A' }}</td>
                        <td>{{ $run?->flow_type ?? 'N/A' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>
                            {{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}
                        </td>
                        <td>{{ $report->generated_at ?? 'N/A' }}</td>
                        <td>
                            <a class="btn" href="{{ route('reports.show', $report) }}">View Report</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $reports->links() }}
    @endif
</div>
@endsection
