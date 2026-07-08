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

<form method="POST" action="{{ route('android-tests.store') }}">
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

        <label>APK Path</label>
        <input name="apk_path" value="D:\FYP\GAgent\GAgent\phase8_android_dummy_app\app\build\outputs\apk\debug\app-debug.apk">

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
@endsection
