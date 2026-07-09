@extends('layouts.app')

@section('title', 'Create Android Test')

@section('content')
<div class="card">
    <h3>Phase 8 Android Appium Experimental Test</h3>
    <p class="muted">
        This page sends Android UX metrics to FastAPI POST /predict-android.
        It is an experimental Android extension. The Web GAgent model remains the main model.
    </p>
</div>

@if ($errors->any())
    <div class="alert-error">
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

    <div class="card">
        <h3>Android Test Information</h3>

        <label>Project</label>
        <select name="project_id" required>
            <option value="">Select project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <label>Flow Type</label>
        <select name="flow_type" required>
            <option value="login">login</option>
            <option value="signup">signup</option>
            <option value="search">search</option>
            <option value="button_click">button_click</option>
            <option value="form_submit">form_submit</option>
        </select>

        <label>Target App Package</label>
        <input name="target_app_package" value="com.gagent.dummyandroid">

        <label>Target App Activity</label>
        <input name="target_app_activity" value="com.gagent.dummyandroid.MainActivity">

        <label>Upload Android APK</label>

        <div
            id="apk-drop-zone"
            style="
                border: 2px dashed #cbd5e1;
                border-radius: 12px;
                padding: 24px;
                text-align: center;
                background: #f8fafc;
                cursor: pointer;
                margin-bottom: 12px;
            "
        >
            <p style="margin: 0; font-weight: 600;">Drag and drop APK file here</p>
            <p style="margin: 6px 0 0; color: #64748b;">or click to select APK</p>

            <input
                id="apk-file-input"
                type="file"
                name="apk_file"
                accept=".apk"
                style="display: none;"
            >

            <p id="apk-file-name" style="margin-top: 12px; color: #334155;"></p>
        </div>

        <label>APK Path / Installed App Path</label>
        <input
            name="apk_path"
            placeholder="Optional. Leave empty if uploading APK above."
            value="{{ old('apk_path') }}"
        >

        <label>Device Name</label>
        <input name="device_name" value="emulator-5554">
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h3>Task Metrics</h3>

            <label>Task Completed</label>
            <select name="task_completed">
                <option value="1">1 - Yes</option>
                <option value="0">0 - No</option>
            </select>

            <label>Task Failed</label>
            <select name="task_failed">
                <option value="0">0 - No</option>
                <option value="1">1 - Yes</option>
            </select>

            <label>Completion Time</label>
            <input type="number" step="0.01" name="completion_time" value="12">

            <label>Click Count</label>
            <input type="number" name="click_count" value="7">

            <label>Scroll Count</label>
            <input type="number" name="scroll_count" value="2">

            <label>Keyboard Count</label>
            <input type="number" name="keyboard_count" value="4">

            <label>Retry Count</label>
            <input type="number" name="retry_count" value="1">

            <label>Error Count</label>
            <input type="number" name="error_count" value="1">

            <label>Failed Clicks</label>
            <input type="number" name="failed_clicks" value="1">

            <label>Unnecessary Clicks</label>
            <input type="number" name="unnecessary_clicks" value="2">

            <label>Path Deviation Score</label>
            <input type="number" step="0.01" name="path_deviation_score" value="0.35">
        </div>

        <div class="card">
            <h3>Android Performance and Friction Metrics</h3>

            <label>App Launch Time MS</label>
            <input type="number" step="0.01" name="app_launch_time_ms" value="1300">

            <label>Screen Load Time MS</label>
            <input type="number" step="0.01" name="screen_load_time_ms" value="1700">

            <label>Feedback Delay MS</label>
            <input type="number" step="0.01" name="feedback_delay_ms" value="1200">

            <label>Interaction Response Time MS</label>
            <input type="number" step="0.01" name="interaction_response_time_ms" value="1500">

            <label>Finish Time MS</label>
            <input type="number" step="0.01" name="finish_time_ms" value="12500">

            <label>Error Message Present</label>
            <select name="error_message_present">
                <option value="1">1 - Yes</option>
                <option value="0">0 - No</option>
            </select>

            <label>Error Message Clarity</label>
            <select name="error_message_clarity">
                <option value="-1">-1 - No error message</option>
                <option value="0">0 - Vague</option>
                <option value="1" selected>1 - Acceptable</option>
                <option value="2">2 - Clear</option>
            </select>

            <label>Popup Detected</label>
            <select name="popup_detected">
                <option value="1">1 - Yes</option>
                <option value="0">0 - No</option>
            </select>

            <label>Overlay Blocks Action</label>
            <select name="overlay_blocks_action">
                <option value="0">0 - No</option>
                <option value="1">1 - Yes</option>
            </select>

            <label>Timeout Occurred</label>
            <select name="timeout_occurred">
                <option value="0">0 - No</option>
                <option value="1">1 - Yes</option>
            </select>

            <label>Crash Detected</label>
            <select name="crash_detected">
                <option value="0">0 - No</option>
                <option value="1">1 - Yes</option>
            </select>

            <label>ANR Detected</label>
            <select name="anr_detected">
                <option value="0">0 - No</option>
                <option value="1">1 - Yes</option>
            </select>
        </div>
    </div>

    <button class="btn" type="submit">Save Android Metrics</button>
</form>

<script>
    const dropZone = document.getElementById('apk-drop-zone');
    const fileInput = document.getElementById('apk-file-input');
    const fileName = document.getElementById('apk-file-name');

    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.style.background = '#e0f2fe';
        dropZone.style.borderColor = '#0284c7';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.style.background = '#f8fafc';
        dropZone.style.borderColor = '#cbd5e1';
    });

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.style.background = '#f8fafc';
        dropZone.style.borderColor = '#cbd5e1';

        if (event.dataTransfer.files.length > 0) {
            fileInput.files = event.dataTransfer.files;
            fileName.textContent = event.dataTransfer.files[0].name;
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileName.textContent = fileInput.files[0].name;
        }
    });
</script>
@endsection
