@extends('layouts.app')

@section('title', 'Test Runs')

@section('content')
<div class="card">
    <h3>All Test Runs</h3>

    @if ($testRuns->isEmpty())
        <p class="muted">No test runs found. Run the demo seeder first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Run Code</th>
                    <th>Project</th>
                    <th>Flow</th>
                    <th>Scenario</th>
                    <th>Viewport</th>
                    <th>Final Friction</th>
                    <th>Main Confidence</th>
                    <th>Baseline</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testRuns as $run)
                    @php
                        $final = $run->finalFrictionResult;
                        $baseline = $run->baselineResult;
                        $level = $final?->friction_level ?? 'Not predicted';
                        $badgeClass = match ($level) {
                            'Low' => 'badge-low',
                            'Medium' => 'badge-medium',
                            'High' => 'badge-high',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td>{{ $run->run_code }}</td>
                        <td>{{ $run->project?->name ?? 'N/A' }}</td>
                        <td>{{ $run->flow_type ?? 'N/A' }}</td>
                        <td>{{ $run->scenario_type ?? 'N/A' }}</td>
                        <td>{{ $run->viewport_type ?? 'N/A' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>{{ $final?->confidence_score !== null ? number_format($final->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                        <td>{{ $baseline?->friction_level ?? 'N/A' }}</td>
                        <td><a class="btn" href="{{ route('test-runs.show', $run) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $testRuns->links() }}
    @endif
</div>
@endsection
