@extends('layouts.app')

@section('title', 'Android Testing')
@section('kicker', 'Appium Metrics')

@section('content')
<div class="g-page-header">
    <div>
        <h2>Create Android Test</h2>
        <p>Save Android UX metrics, APK/app details, and friction signals before sending the payload to FastAPI /predict-android.</p>
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

<form method="POST" action="{{ route('android-tests.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="g-layout-2-1">
        <div class="g-stack">
            <div class="g-card">
                <h3>Android App Configuration</h3>
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
                        <label>Flow Type</label>
                        <select class="g-select" name="flow_type" required>
                            @foreach (['login', 'signup', 'search', 'button_click', 'form_submit'] as $flow)
                                <option value="{{ $flow }}" @selected(old('flow_type', 'login') === $flow)>{{ $flow }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="g-form-field">
                        <label>Target App Package</label>
                        <input class="g-input" name="target_app_package" value="{{ old('target_app_package', 'com.gagent.dummyandroid') }}">
                    </div>
                    <div class="g-form-field">
                        <label>Target App Activity</label>
                        <input class="g-input" name="target_app_activity" value="{{ old('target_app_activity', 'com.gagent.dummyandroid.MainActivity') }}">
                    </div>
                    <div class="g-form-field">
                        <label>Device Name</label>
                        <input class="g-input" name="device_name" value="{{ old('device_name', 'emulator-5554') }}">
                    </div>
                    <div class="g-form-field">
                        <label>APK Path / Installed App Path</label>
                        <input class="g-input" name="apk_path" placeholder="Optional when uploading APK" value="{{ old('apk_path') }}">
                    </div>
                    <div class="g-form-field" style="grid-column: 1 / -1;">
                        <label>Upload Android APK</label>
                        <div id="apk-drop-zone" class="g-upload-zone">
                            <p style="margin: 0; font-weight: 850;">Drop APK file here</p>
                            <p class="g-muted" style="margin: 6px 0 0;">or click to select APK</p>
                            <input id="apk-file-input" type="file" name="apk_file" accept=".apk" style="display: none;">
                            <p id="apk-file-name" style="margin-top: 12px; color: #334155;"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="g-grid g-grid-2">
                <div class="g-card">
                    <h3>Task Metrics</h3>
                    <div class="g-form-grid" style="grid-template-columns: 1fr;">
                        @foreach ([
                            'completion_time' => ['Completion Time', '12', '0.01'],
                            'click_count' => ['Click Count', '7', '1'],
                            'scroll_count' => ['Scroll Count', '2', '1'],
                            'keyboard_count' => ['Keyboard Count', '4', '1'],
                            'retry_count' => ['Retry Count', '1', '1'],
                            'error_count' => ['Error Count', '1', '1'],
                            'failed_clicks' => ['Failed Clicks', '1', '1'],
                            'unnecessary_clicks' => ['Unnecessary Clicks', '2', '1'],
                            'path_deviation_score' => ['Path Deviation Score', '0.35', '0.01'],
                        ] as $name => $meta)
                            <div class="g-form-field">
                                <label>{{ $meta[0] }}</label>
                                <input class="g-input" type="number" step="{{ $meta[2] }}" name="{{ $name }}" value="{{ old($name, $meta[1]) }}">
                            </div>
                        @endforeach

                        <div class="g-form-field">
                            <label>Task Completed</label>
                            <select class="g-select" name="task_completed">
                                <option value="1" @selected(old('task_completed', '1') === '1')>1 - Yes</option>
                                <option value="0" @selected(old('task_completed') === '0')>0 - No</option>
                            </select>
                        </div>

                        <div class="g-form-field">
                            <label>Task Failed</label>
                            <select class="g-select" name="task_failed">
                                <option value="0" @selected(old('task_failed', '0') === '0')>0 - No</option>
                                <option value="1" @selected(old('task_failed') === '1')>1 - Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="g-card">
                    <h3>Android Performance and Friction</h3>
                    <div class="g-form-grid" style="grid-template-columns: 1fr;">
                        @foreach ([
                            'app_launch_time_ms' => ['App Launch Time MS', '1300'],
                            'screen_load_time_ms' => ['Screen Load Time MS', '1700'],
                            'feedback_delay_ms' => ['Feedback Delay MS', '1200'],
                            'interaction_response_time_ms' => ['Interaction Response Time MS', '1500'],
                            'finish_time_ms' => ['Finish Time MS', '12500'],
                        ] as $name => $meta)
                            <div class="g-form-field">
                                <label>{{ $meta[0] }}</label>
                                <input class="g-input" type="number" step="0.01" name="{{ $name }}" value="{{ old($name, $meta[1]) }}">
                            </div>
                        @endforeach

                        <div class="g-form-field">
                            <label>Error Message Present</label>
                            <select class="g-select" name="error_message_present">
                                <option value="1" @selected(old('error_message_present', '1') === '1')>1 - Yes</option>
                                <option value="0" @selected(old('error_message_present') === '0')>0 - No</option>
                            </select>
                        </div>

                        <div class="g-form-field">
                            <label>Error Message Clarity</label>
                            <select class="g-select" name="error_message_clarity">
                                <option value="-1">-1 - No error message</option>
                                <option value="0">0 - Vague</option>
                                <option value="1" selected>1 - Acceptable</option>
                                <option value="2">2 - Clear</option>
                            </select>
                        </div>

                        @foreach (['popup_detected' => 'Popup Detected', 'overlay_blocks_action' => 'Overlay Blocks Action', 'timeout_occurred' => 'Timeout Occurred', 'crash_detected' => 'Crash Detected', 'anr_detected' => 'ANR Detected'] as $name => $label)
                            <div class="g-form-field">
                                <label>{{ $label }}</label>
                                <select class="g-select" name="{{ $name }}">
                                    <option value="0" @selected(old($name, '0') === '0')>0 - No</option>
                                    <option value="1" @selected(old($name) === '1')>1 - Yes</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <aside class="g-panel">
            <div class="g-soft-label">Device / Emulator Status</div>
            <h3 style="margin-top: 7px;">Android Test Console</h3>
            <div class="g-device-stage" style="margin-top: 14px;">
                <div class="g-phone">
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line"></div>
                    <div class="g-phone-line active"></div>
                    <div class="g-phone-line"></div>
                </div>
            </div>
            <div class="g-kv" style="margin-top: 14px;">
                <div class="g-kv-row"><span>Driver</span><span>Appium</span></div>
                <div class="g-kv-row"><span>Model Endpoint</span><span>/predict-android</span></div>
                <div class="g-kv-row"><span>Result</span><span>Low / Medium / High</span></div>
            </div>
            <button class="g-btn g-btn-primary g-btn-block" type="submit" style="margin-top: 16px;">Save Android Metrics</button>
        </aside>
    </div>
</form>

<script>
    const dropZone = document.getElementById('apk-drop-zone');
    const fileInput = document.getElementById('apk-file-input');
    const fileName = document.getElementById('apk-file-name');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.classList.add('is-dragging');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('is-dragging');
    });

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.classList.remove('is-dragging');

        if (event.dataTransfer.files.length > 0) {
            fileInput.files = event.dataTransfer.files;
            fileName.textContent = event.dataTransfer.files[0].name;
        }
    });

    fileInput.addEventListener('change', () => {
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : '';
    });
</script>
@endsection
