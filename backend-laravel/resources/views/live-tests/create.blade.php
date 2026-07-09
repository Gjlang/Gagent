@extends('layouts.app')

@section('title', 'Live Website Testing')
@section('kicker', 'Playwright Runner')

@section('content')
<div class="g-page-header">
    <div>
        <h2>Run Live Website Test</h2>
        <p>Create a Playwright live website session, collect UX metrics, send them to FastAPI, and save the prediction/report through the existing controller.</p>
    </div>
    <a class="g-btn" href="{{ route('test-runs.index') }}">View Test Runs</a>
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

<div class="g-layout-2-1">
    <div class="g-card">
        <h3>Website Test Configuration</h3>
        <form method="POST" action="{{ route('live-tests.store') }}">
            @csrf
            <div class="g-form-grid">
                <div class="g-form-field">
                    <label>Project</label>
                    <select class="g-select" name="project_id" required>
                        <option value="">Select project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Website URL</label>
                    <input class="g-input" type="url" name="target_url" value="{{ old('target_url', 'http://127.0.0.1:3000') }}" required>
                </div>
                <div class="g-form-field">
                    <label>Flow Type</label>
                    <select class="g-select" name="flow_type" required>
                        <option value="landing_navigation" @selected(old('flow_type') === 'landing_navigation')>landing_navigation</option>
                        <option value="cta_click" @selected(old('flow_type') === 'cta_click')>cta_click</option>
                        <option value="basic_search" @selected(old('flow_type') === 'basic_search')>basic_search</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Viewport Type</label>
                    <select class="g-select" name="viewport_type" required>
                        <option value="desktop" @selected(old('viewport_type', 'desktop') === 'desktop')>desktop</option>
                        <option value="tablet" @selected(old('viewport_type') === 'tablet')>tablet</option>
                        <option value="mobile" @selected(old('viewport_type') === 'mobile')>mobile</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Network Condition</label>
                    <select class="g-select" name="network_condition" required>
                        <option value="normal" @selected(old('network_condition', 'normal') === 'normal')>normal</option>
                        <option value="slow" @selected(old('network_condition') === 'slow')>slow</option>
                    </select>
                </div>
                <div class="g-form-field">
                    <label>Max Duration Seconds</label>
                    <input class="g-input" type="number" name="max_duration_seconds" value="{{ old('max_duration_seconds', 30) }}" min="10" max="120" required>
                </div>
                <div class="g-form-field" style="grid-column: 1 / -1;">
                    <label>Notes</label>
                    <textarea class="g-textarea" name="notes" rows="4" placeholder="Optional testing notes">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="g-actions" style="margin-top: 18px;">
                <button class="g-btn g-btn-primary" type="submit">Create Live Test Run</button>
            </div>
        </form>
    </div>

    <aside class="g-panel">
        <div class="g-soft-label">Execution Pipeline</div>
        <h3 style="margin-top: 7px;">Playwright → FastAPI → Report</h3>
        <div class="g-device-stage" style="min-height: 260px; margin-top: 14px;">
            <div class="g-phone">
                <div class="g-phone-line"></div>
                <div class="g-phone-line"></div>
                <div class="g-phone-line active"></div>
                <div class="g-phone-line"></div>
            </div>
        </div>
        <p class="g-muted g-small">This page only changes the Blade UI. The form action remains <strong>live-tests.store</strong>.</p>
    </aside>
</div>
@endsection
