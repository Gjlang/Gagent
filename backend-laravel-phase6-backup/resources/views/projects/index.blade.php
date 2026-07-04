@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="card">
    <a class="btn" href="{{ route('projects.create') }}">Create Project</a>
</div>

<div class="card">
    <h3>Project List</h3>

    @if ($projects->isEmpty())
        <p class="muted">No projects found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project Name</th>
                    <th>Platform</th>
                    <th>Website URL</th>
                    <th>Status</th>
                    <th>Test Runs</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->project_name }}</td>
                        <td>{{ $project->platform_type }}</td>
                        <td>{{ $project->website_url ?? 'N/A' }}</td>
                        <td>{{ $project->status }}</td>
                        <td>{{ $project->test_runs_count }}</td>
                        <td>
                            <a class="btn" href="{{ route('projects.show', $project) }}">View</a>

                            <form action="{{ route('projects.destroy', $project) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this project?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $projects->links() }}
    @endif
</div>
@endsection
