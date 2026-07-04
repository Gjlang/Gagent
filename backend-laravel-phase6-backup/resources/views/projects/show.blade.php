@extends('layouts.app')

@section('title', 'Project Detail')

@section('content')
<div class="card">
    <h3>{{ $project->project_name }}</h3>
    <p><strong>Platform:</strong> {{ $project->platform_type }}</p>
    <p><strong>Website URL:</strong> {{ $project->website_url ?? 'N/A' }}</p>
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
                    <th>Run ID</th>
                    <th>Flow Type</th>
                    <th>Status</th>
                    <th>Friction</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->testRuns as $run)
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
                        <td>{{ $run->flow_type ?? 'N/A' }}</td>
                        <td>{{ $run->status }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>
                            {{ $run->frictionResult?->confidence_score !== null ? number_format($run->frictionResult->confidence_score * 100, 1) . '%' : 'N/A' }}
                        </td>
                        <td>
                            <a class="btn" href="{{ route('test-runs.show', $run) }}">View Run</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
