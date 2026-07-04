@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="card">
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        <label for="project_name">Project Name</label>
        <input type="text" id="project_name" name="project_name" value="{{ old('project_name') }}" required>
        @error('project_name')
            <p class="alert-error">{{ $message }}</p>
        @enderror

        <label for="platform_type">Platform Type</label>
        <select id="platform_type" name="platform_type" required>
            <option value="Web">Web</option>
            <option value="Android">Android</option>
            <option value="Web and Android">Web and Android</option>
        </select>
        @error('platform_type')
            <p class="alert-error">{{ $message }}</p>
        @enderror

        <label for="website_url">Website URL</label>
        <input type="url" id="website_url" name="website_url" value="{{ old('website_url') }}" placeholder="https://example.com">
        @error('website_url')
            <p class="alert-error">{{ $message }}</p>
        @enderror

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="Active">Active</option>
            <option value="Paused">Paused</option>
            <option value="Completed">Completed</option>
        </select>
        @error('status')
            <p class="alert-error">{{ $message }}</p>
        @enderror

        <button class="btn" type="submit">Save Project</button>
        <a class="btn btn-secondary" href="{{ route('projects.index') }}">Cancel</a>
    </form>
</div>
@endsection
