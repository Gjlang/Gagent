@extends('layouts.app')

@section('title', 'Create Project')
@section('kicker', 'Project Setup')

@section('content')
<div class="g-page-header">
    <div>
        <h2>Create UX Test Project</h2>
        <p>Register a target application before running Playwright or Appium test flows.</p>
    </div>
    <a class="g-btn" href="{{ route('projects.index') }}">Back</a>
</div>

@if ($errors->any())
    <div class="g-alert-error">
        <strong>Validation error:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="g-card">
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        <div class="g-form-grid">
            <div class="g-form-field">
                <label>Project Name</label>
                <input class="g-input" type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="g-form-field">
                <label>Target Type</label>
                <select class="g-select" name="target_type" required>
                    <option value="dummy_website" @selected(old('target_type') === 'dummy_website')>Dummy Website</option>
                    <option value="web_application" @selected(old('target_type') === 'web_application')>Web Application</option>
                    <option value="android_application" @selected(old('target_type') === 'android_application')>Android Application</option>
                </select>
            </div>
            <div class="g-form-field" style="grid-column: 1 / -1;">
                <label>Description</label>
                <textarea class="g-textarea" name="description" rows="4">{{ old('description') }}</textarea>
            </div>
            <div class="g-form-field">
                <label>Target URL</label>
                <input class="g-input" type="url" name="target_url" value="{{ old('target_url') }}" placeholder="http://127.0.0.1:3000">
            </div>
            <div class="g-form-field">
                <label>Status</label>
                <select class="g-select" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="paused" @selected(old('status') === 'paused')>Paused</option>
                    <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                </select>
            </div>
        </div>
        <div class="g-actions" style="margin-top: 18px;">
            <button class="g-btn g-btn-primary" type="submit">Create Project</button>
        </div>
    </form>
</div>
@endsection
