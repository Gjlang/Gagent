@extends('layouts.app')

@section('title', 'Create Live Website Test')

@section('content')
    <div class="card">
        <h2>Create Live Website Test</h2>
        <p class="muted">
            Use this page to test safe public/demo websites or websites you own. Avoid login, payment, CAPTCHA, and private flows.
        </p>

        @if ($errors->any())
            <div class="alert-error">
                <strong>Fix these validation errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('live-tests.store') }}">
            @csrf

            <label for="project_id">Project</label>
            <select name="project_id" id="project_id" required>
                <option value="">Select project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>

            <label for="target_url">Website URL</label>
            <input
                type="url"
                name="target_url"
                id="target_url"
                value="{{ old('target_url', 'https://example.com') }}"
                placeholder="https://example.com"
                required
            >

            <label for="flow_type">Flow Type</label>
            <select name="flow_type" id="flow_type" required>
                <option value="landing_navigation" @selected(old('flow_type') === 'landing_navigation')>
                    Landing Navigation
                </option>
                <option value="cta_click" @selected(old('flow_type') === 'cta_click')>
                    CTA Click
                </option>
                <option value="basic_search" @selected(old('flow_type') === 'basic_search')>
                    Basic Search
                </option>
            </select>

            <label for="viewport_type">Viewport Type</label>
            <select name="viewport_type" id="viewport_type" required>
                <option value="desktop" @selected(old('viewport_type') === 'desktop')>Desktop</option>
                <option value="tablet" @selected(old('viewport_type') === 'tablet')>Tablet</option>
                <option value="mobile" @selected(old('viewport_type') === 'mobile')>Mobile</option>
            </select>

            <label for="network_condition">Network Condition</label>
            <select name="network_condition" id="network_condition" required>
                <option value="normal" @selected(old('network_condition') === 'normal')>Normal</option>
                <option value="slow" @selected(old('network_condition') === 'slow')>Slow</option>
            </select>

            <label for="max_duration_seconds">Max Duration Seconds</label>
            <input
                type="number"
                name="max_duration_seconds"
                id="max_duration_seconds"
                value="{{ old('max_duration_seconds', 60) }}"
                min="10"
                max="120"
                required
            >

            <label for="notes">Notes</label>
            <textarea
                name="notes"
                id="notes"
                rows="4"
                placeholder="Optional notes for this live test"
            >{{ old('notes') }}</textarea>

            <button type="submit" class="btn">Create Live Test</button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
