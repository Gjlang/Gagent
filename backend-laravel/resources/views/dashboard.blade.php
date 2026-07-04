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
        <div class="muted">Total Reports</div>
        <div class="stat-value">{{ $totalReports }}</div>
    </div>

    <div class="card">
        <div class="muted">Average Final Confidence</div>
        <div class="stat-value">
            {{ $averageConfidence ? number_format($averageConfidence * 100, 1) . '%' : 'N/A' }}
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Final Friction Distribution</h3>
        <canvas id="frictionChart"></canvas>
    </div>

    <div class="card">
        <h3>Flow Type Distribution</h3>
        <canvas id="flowChart"></canvas>
    </div>
</div>

<div class="card">
    <h3>Recent Test Runs</h3>

    @if ($recentTestRuns->isEmpty())
        <p class="muted">No test runs available. Run the demo seeder first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Run Code</th>
                    <th>Project</th>
                    <th>Flow</th>
                    <th>Viewport</th>
                    <th>Final Friction</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentTestRuns as $run)
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
                        <td>{{ $run->project?->name ?? 'N/A' }}</td>
                        <td>{{ $run->flow_type ?? 'N/A' }}</td>
                        <td>{{ $run->viewport_type ?? 'N/A' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $level }}</span></td>
                        <td>{{ $result?->confidence_score !== null ? number_format($result->confidence_score * 100, 1) . '%' : 'N/A' }}</td>
                        <td><a class="btn" href="{{ route('test-runs.show', $run) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h3>Recent Reports</h3>

    @if ($recentReports->isEmpty())
        <p class="muted">No reports available.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Generated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentReports as $report)
                    <tr>
                        <td>{{ $report->title }}</td>
                        <td>{{ $report->testRun?->project?->name ?? 'N/A' }}</td>
                        <td>{{ $report->generated_at ?? 'N/A' }}</td>
                        <td><a class="btn" href="{{ route('reports.show', $report) }}">View Report</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('frictionChart'), {
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

    new Chart(document.getElementById('flowChart'), {
        type: 'bar',
        data: {
            labels: @json(array_keys($flowDistribution)),
            datasets: [{
                label: 'Test Runs',
                data: @json(array_values($flowDistribution))
            }]
        }
    });
</script>
@endpush
