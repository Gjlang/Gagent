@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="card">
    <h3>Create UX Test Project</h3>

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        <label>Project Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>

        <label>Description</label>
        <textarea name="description" rows="4">{{ old('description') }}</textarea>

        <label>Target Type</label>
        <select name="target_type" required>
            <option value="dummy_website">Dummy Website</option>
            <option value="web_application">Web Application</option>
            <option value="android_application">Android Application</option>
        </select>

        <label>Target URL</label>
        <input type="url" name="target_url" value="{{ old('target_url') }}" placeholder="http://127.0.0.1:3000">

        <label>Status</label>
        <select name="status" required>
            <option value="active">Active</option>
            <option value="paused">Paused</option>
            <option value="completed">Completed</option>
        </select>

        <button class="btn" type="submit">Create Project</button>
    </form>
</div>
@endsection
