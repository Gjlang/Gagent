@extends('layouts.app')

@section('title', 'Project Detail')

@section('content')
<div class="card">
    <h2>{{ $project->name }}</h2>
    <p class="muted">{{ $project->description ?? 'No description provided.' }}</p>

    <p><strong>Target Type:</strong> {{ $project->target_type }}</p>
    <p><strong>Target URL:</strong> {{ $project->target_url ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ $project->status }}</p>
</div>

<div class="card">
    <h3>Related Test Runs</h3>

    @if ($project->testRuns->isEmpty())
        <p class="muted">No test runs linked to this project yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Run Code</th>
                    <th>Flow</th>
                    <th>Scenario</th>
                    <th>Viewport</th>
                    <th>Status</th>
                    <th>Final Friction</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->testRuns as $run)
                    @php
                        $result = $run->finalFrictionResult;
                        $level = $result?->friction_level ?? 'Not predicted';
                        $badgeClass = match ($level) {
                            'Low' => 'badge-low',
                            'Medium' => 'badge-medium',
                            'High' => 'badge-high',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td>{{ $run->run_code }}</td>
                        <td>{{ $run->flow_type ?? 'N/A' }}</td>
                        <td>{{ $run->scenario_type ?? 'N/A' }}</td>
                        <td>{{ $run->viewport_type ?? 'N/A' }}</td>
                        <td>{{ $run->status }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td><a class="btn" href="{{ route('test-runs.show', $run) }}">View Run</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
