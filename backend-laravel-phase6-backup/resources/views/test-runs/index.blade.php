@extends('layouts.app')

@section('title', 'Test Runs')

@section('content')
<div class="card">
    <h3>Test Run History</h3>

    @if ($testRuns->isEmpty())
        <p class="muted">No test runs found. Run the demo seeder first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project</th>
                    <th>Flow Type</th>
                    <th>Status</th>
                    <th>Completion Time</th>
                    <th>Friction</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testRuns as $run)
                    @php
                        $level = $run->frictionResult?->friction_level ?? 'Not predicted';
                        $badgeClass = match ($level) {
                            'Low' => 'badge-low',
                            'Medium' => 'badge-medium',
                            'High' => 'badge-high',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td>{{ $run->id }}</td>
                        <td>{{ $run->project?->project_name ?? 'N/A' }}</td>
                        <td>{{ $run->flow_type ?? 'N/A' }}</td>
                        <td>{{ $run->status }}</td>
                        <td>{{ $run->uxMetric?->completion_time ?? 'N/A' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>
                            {{ $run->frictionResult?->confidence_score !== null ? number_format($run->frictionResult->confidence_score * 100, 1) . '%' : 'N/A' }}
                        </td>
                        <td>
                            <a class="btn" href="{{ route('test-runs.show', $run) }}">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $testRuns->links() }}
    @endif
</div>
@endsection
