@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="card">
    <a class="btn" href="{{ route('projects.create') }}">Create Project</a>
</div>

<div class="card">
    <h3>UX Test Projects</h3>

    @if ($projects->isEmpty())
        <p class="muted">No projects found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Target Type</th>
                    <th>Target URL</th>
                    <th>Status</th>
                    <th>Test Runs</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->target_type }}</td>
                        <td>{{ $project->target_url ?? 'N/A' }}</td>
                        <td>{{ $project->status }}</td>
                        <td>{{ $project->test_runs_count }}</td>
                        <td><a class="btn" href="{{ route('projects.show', $project) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $projects->links() }}
    @endif
</div>
@endsection
