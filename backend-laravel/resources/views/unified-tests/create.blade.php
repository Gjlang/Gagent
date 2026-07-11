@extends('layouts.app')

@section('title', 'Run UX Test')
@section('kicker', 'Unified Website and Android Runner')

@section('content')
<div class="g-page-header">
    <div>
        <h2>Run UX Test</h2>
        <p>Run Website or Android UX testing from one page. The system will auto-create the project, test run, prediction, and report.</p>
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

<form
    id="ux-test-form"
    method="POST"
    action="{{ route('unified-tests.store') }}"
    enctype="multipart/form-data"
>
    @csrf

    <div class="g-layout-2-1">
        <div class="g-stack">
           <div class="g-card">
    <h3>Choose Test Type</h3>

    <div class="g-form-grid">
        <div class="g-form-field">
            <label>Test Type</label>

            <select
                class="g-select"
                name="test_type"
                id="test_type"
                required
            >
                <option
                    value="website"
                    @selected(old('test_type', 'website') === 'website')
                >
                    Website — Playwright
                </option>

                <option
                    value="android"
                    @selected(old('test_type') === 'android')
                >
                    Android — Appium
                </option>
            </select>
        </div>

        <div class="g-form-field">
            <label>Show Live Browser</label>

            <select
                class="g-select"
                name="show_browser"
                id="show_browser"
            >
                <option
                    value="1"
                    @selected(old('show_browser', '1') === '1')
                >
                    Yes — show browser testing
                </option>

                <option
                    value="0"
                    @selected(old('show_browser') === '0')
                >
                    No — run browser in background
                </option>
            </select>
        </div>

        <div class="g-form-field">
            <label>Playwright Action Delay</label>

            <input
                class="g-input"
                type="number"
                name="slow_mo_ms"
                value="{{ old('slow_mo_ms', 350) }}"
                min="0"
                max="1000"
                step="50"
            >

            <span class="g-muted g-small">
                Recommended for demonstration: 300–500 milliseconds.
            </span>
        </div>
    </div>
</div>

            <div class="g-card" id="website-section">
                <h3>Website Test Configuration</h3>

                <div class="g-form-grid">
                    <div class="g-form-field">
                        <label>Website URL</label>
                        <input class="g-input" type="url" name="target_url" value="{{ old('target_url', 'http://127.0.0.1:3000/landing-good') }}">
                    </div>

                    <div class="g-form-field">
    <label>Website Audit Mode</label>

    <select
        class="g-select"
        name="web_flow_type"
        required
    >
        <option
            value="full_audit"
            @selected(old('web_flow_type', 'full_audit') === 'full_audit')
        >
            Full Website Audit — test all detected safe features
        </option>

        <option
            value="auto"
            @selected(old('web_flow_type') === 'auto')
        >
            Quick Auto Test — test one detected flow
        </option>

        <option
            value="landing_navigation"
            @selected(old('web_flow_type') === 'landing_navigation')
        >
            Page Loading and Navigation Only
        </option>

        <option
            value="basic_search"
            @selected(old('web_flow_type') === 'basic_search')
        >
            Search Only
        </option>

        <option
            value="cta_click"
            @selected(old('web_flow_type') === 'cta_click')
        >
            CTA Only
        </option>
    </select>
</div>

                    <div class="g-form-field">
                        <label>Viewport Type</label>
                        <select class="g-select" name="viewport_type">
                            <option value="desktop" @selected(old('viewport_type', 'desktop') === 'desktop')>desktop</option>
                            <option value="tablet" @selected(old('viewport_type') === 'tablet')>tablet</option>
                            <option value="mobile" @selected(old('viewport_type') === 'mobile')>mobile</option>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Network Condition</label>
                        <select class="g-select" name="network_condition">
                            <option value="normal" @selected(old('network_condition', 'normal') === 'normal')>normal</option>
                            <option value="slow" @selected(old('network_condition') === 'slow')>slow</option>
                        </select>
                    </div>

                    <div class="g-form-field">
                        <label>Max Duration Seconds</label>

                        <input
                            class="g-input"
                            type="number"
                            name="max_duration_seconds"
                            value="{{ old('max_duration_seconds', 180) }}"
                            min="30"
                            max="300"
                        >

                        <span class="g-muted g-small">
                            Recommended for Full Website Audit: 180 seconds.
                        </span>
                    </div>
                </div>
            </div>

         <div
    class="g-card"
    id="android-section"
    style="display: none;"
>
    <h3>Real Android APK Configuration</h3>

    <div class="g-form-grid">
        <div class="g-form-field">
            <label>Android Flow</label>

            <select
                class="g-select"
                name="android_flow_type"
            >
                <option
                    value="login"
                    @selected(
                        old(
                            'android_flow_type',
                            'login'
                        ) === 'login'
                    )
                >
                    login
                </option>
            </select>
        </div>

        <div class="g-form-field">
            <label>Target App Package</label>

            <input
                class="g-input"
                name="target_app_package"
                value="{{ old(
                    'target_app_package',
                    'com.example.rantau_mate'
                ) }}"
                required
            >
        </div>

        <div class="g-form-field">
            <label>Target App Activity</label>

            <input
                class="g-input"
                name="target_app_activity"
                value="{{ old(
                    'target_app_activity',
                    'com.example.rantau_mate.MainActivity'
                ) }}"
                required
            >
        </div>

        <div class="g-form-field">
            <label>Device Name</label>

            <input
                class="g-input"
                name="device_name"
                value="{{ old(
                    'device_name',
                    'emulator-5554'
                ) }}"
            >
        </div>

        <div class="g-form-field">
            <label>APK Path</label>

            <input
                class="g-input"
                name="apk_path"
                value="{{ old('apk_path') }}"
                placeholder="Optional when uploading an APK"
            >
        </div>

        <div
            class="g-form-field"
            style="grid-column: 1 / -1;"
        >
            <label>Upload Real APK</label>

            <input
                class="g-input"
                type="file"
                name="apk_file"
                accept=".apk"
            >
        </div>
    </div>

    <p
        class="g-muted g-small"
        style="margin-top: 12px;"
    >
        Laravel will automatically use the Appium
        profile matching the entered package name.
        Emulator, Appium and FastAPI must already
        be running.
    </p>
</div>

            <div class="g-card">
                <h3>Notes</h3>
                <textarea class="g-textarea" name="notes" rows="4" placeholder="Optional testing notes">{{ old('notes') }}</textarea>

                <div class="g-actions" style="margin-top: 18px;">
                    <button
    class="g-btn g-btn-primary"
    type="submit"
    id="run-ux-test-button"
>
    Run UX Test Now
</button>
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <div class="g-soft-label">Unified Pipeline</div>
            <h3 style="margin-top: 7px;">Auto Project → Test Run → AI → Report</h3>

            <div class="g-kv" style="margin-top: 14px;">
                <div class="g-kv-row"><span>Website Runner</span><span>Playwright</span></div>
                <div class="g-kv-row"><span>Android Runner</span><span>Appium</span></div>
                <div class="g-kv-row"><span>AI Service</span><span>FastAPI</span></div>
                <div class="g-kv-row"><span>Output</span><span>Low / Medium / High</span></div>
            </div>

            <div class="g-device-stage" style="min-height: 260px; margin-top: 18px;">
                <div class="g-phone">
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line active"></div>
                    <div class="g-phone-line"></div>
                </div>
            </div>

            <p class="g-muted g-small">
                Old pages are still kept as backup. This page is the new simplified flow.
            </p>
        </aside>
    </div>
</form>
<div id="g-test-running-overlay" class="g-test-running-overlay" hidden>
    <div class="g-test-running-card">
        <div class="g-ai-scan-screen" aria-hidden="true">
            <div class="g-ai-scan-grid"></div>
            <div class="g-ai-scan-target g-ai-scan-target-one"></div>
            <div class="g-ai-scan-target g-ai-scan-target-two"></div>
            <div class="g-ai-scan-line"></div>
        </div>

        <div class="g-soft-label">GAgent Autonomous Testing</div>

        <h3 id="g-test-running-title">
            Starting UX Test
        </h3>

        <p id="g-test-running-message" class="g-muted">
            Preparing the automation runner...
        </p>

        <div class="g-running-progress">
            <span></span>
        </div>

        <p class="g-muted g-small">
            Do not close this page while the test is running.
        </p>
    </div>
</div>

<script>
    const testType = document.getElementById('test_type');
    const websiteSection = document.getElementById('website-section');
    const androidSection = document.getElementById('android-section');

    const uxTestForm = document.getElementById('ux-test-form');
    const runButton = document.getElementById('run-ux-test-button');

    const runningOverlay = document.getElementById('g-test-running-overlay');
    const runningTitle = document.getElementById('g-test-running-title');
    const runningMessage = document.getElementById('g-test-running-message');

    function toggleSections() {
        if (testType.value === 'android') {
            websiteSection.style.display = 'none';
            androidSection.style.display = 'block';
        } else {
            websiteSection.style.display = 'block';
            androidSection.style.display = 'none';
        }
    }

    function startRunningAnimation() {
        const isWebsite = testType.value === 'website';

        const websiteMessages = [
            'Launching the Playwright browser...',
            'Opening the target website...',
            'Inspecting page structure and interactive elements...',
            'Testing navigation, scrolling, and user actions...',
            'Collecting UX performance metrics...',
            'Capturing screenshot evidence...',
            'Sending metrics to the AI prediction service...',
            'Generating the final UX friction report...'
        ];

        const androidMessages = [
            'Connecting to the Appium server...',
            'Preparing the Android device...',
            'Launching the target application...',
            'Testing the selected Android flow...',
            'Collecting interaction and performance metrics...',
            'Capturing screenshot evidence...',
            'Sending metrics to the AI prediction service...',
            'Generating the final UX friction report...'
        ];

        const messages = isWebsite ? websiteMessages : androidMessages;

        runningTitle.textContent = isWebsite
            ? 'Website UX Test Running'
            : 'Android UX Test Running';

        runningMessage.textContent = messages[0];
        runningOverlay.hidden = false;

        runButton.disabled = true;
        runButton.textContent = isWebsite
            ? 'Running Website Test...'
            : 'Running Android Test...';

        let messageIndex = 0;

        window.setInterval(() => {
            messageIndex = (messageIndex + 1) % messages.length;
            runningMessage.textContent = messages[messageIndex];
        }, 2600);
    }

    testType.addEventListener('change', toggleSections);

    uxTestForm.addEventListener('submit', function () {
        startRunningAnimation();
    });

    toggleSections();
</script>
@endsection
