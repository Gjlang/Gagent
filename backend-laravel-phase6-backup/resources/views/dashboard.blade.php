@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-4">
    <div class="card">
        <div class="muted">Total Projects</div>
        <div class="stat-value">{{ $totalProjects }}</div>
    </div>

    <div class="card">
        <div class="muted">Total Test Runs</div>
        <div class="stat-value">{{ $totalTestRuns }}</div>
    </div>

    <div class="card">
        <div class="muted">Average Confidence</div>
        <div class="stat-value">
            {{ $averageConfidence ? number_format($averageConfidence * 100, 1) . '%' : 'N/A' }}
        </div>
    </div>

    <div class="card">
        <div class="muted">High Friction Runs</div>
        <div class="stat-value">{{ $severityCounts['High'] }}</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Friction Severity Distribution</h3>
        <canvas id="severityChart"></canvas>
    </div>

    <div class="card">
        <h3>Severity Counts</h3>
        <p><span class="badge badge-low">Low</span> {{ $severityCounts['Low'] }}</p>
        <p><span class="badge badge-medium">Medium</span> {{ $severityCounts['Medium'] }}</p>
        <p><span class="badge badge-high">High</span> {{ $severityCounts['High'] }}</p>
    </div>
</div>

<div class="card">
    <h3>Recent Test Runs</h3>

    @if ($recentTestRuns->isEmpty())
        <p class="muted">No test runs available yet. Run the demo seeder first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project</th>
                    <th>Flow Type</th>
                    <th>Status</th>
                    <th>Friction</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentTestRuns as $run)
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
    @endif
</div>
@endsection

@push('scripts')
<script>
    const severityCtx = document.getElementById('severityChart');

    new Chart(severityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Low', 'Medium', 'High'],
            datasets: [{
                data: [
                    {{ $severityCounts['Low'] }},
                    {{ $severityCounts['Medium'] }},
                    {{ $severityCounts['High'] }}
                ]
            }]
        }
    });
</script>
@endpush
