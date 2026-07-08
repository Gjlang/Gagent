<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\TestRun;
use App\Models\UXMetric;
use App\Services\GAgentAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AndroidTestController extends Controller
{
    public function create()
    {
        $projects = Project::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('android-tests.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'flow_type' => ['required', 'in:login,signup,search,button_click,form_submit'],
            'target_app_package' => ['nullable', 'string', 'max:255'],
            'target_app_activity' => ['nullable', 'string', 'max:255'],
            'apk_path' => ['nullable', 'string', 'max:2048'],
            'device_name' => ['nullable', 'string', 'max:255'],

            'task_completed' => ['required', 'boolean'],
            'task_failed' => ['required', 'boolean'],
            'completion_time' => ['required', 'numeric', 'min:0'],
            'click_count' => ['required', 'integer', 'min:0'],
            'scroll_count' => ['required', 'integer', 'min:0'],
            'keyboard_count' => ['required', 'integer', 'min:0'],
            'retry_count' => ['required', 'integer', 'min:0'],
            'error_count' => ['required', 'integer', 'min:0'],
            'failed_clicks' => ['required', 'integer', 'min:0'],
            'unnecessary_clicks' => ['required', 'integer', 'min:0'],
            'path_deviation_score' => ['required', 'numeric', 'min:0'],

            'app_launch_time_ms' => ['required', 'numeric', 'min:0'],
            'screen_load_time_ms' => ['required', 'numeric', 'min:0'],
            'feedback_delay_ms' => ['required', 'numeric', 'min:0'],
            'interaction_response_time_ms' => ['required', 'numeric', 'min:0'],
            'finish_time_ms' => ['required', 'numeric', 'min:0'],

            'error_message_present' => ['required', 'boolean'],
            'error_message_clarity' => ['required', 'integer', 'min:-1', 'max:2'],
            'popup_detected' => ['required', 'boolean'],
            'overlay_blocks_action' => ['required', 'boolean'],
            'timeout_occurred' => ['required', 'boolean'],
            'crash_detected' => ['required', 'boolean'],
            'anr_detected' => ['required', 'boolean'],
        ]);

        $testRun = null;

        DB::transaction(function () use ($validated, &$testRun) {
            $testRun = TestRun::create([
                'project_id' => $validated['project_id'],
                'run_code' => 'ANDROID-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5)),
                'flow_type' => $validated['flow_type'],
                'scenario_type' => 'android_manual_metrics',
                'viewport_type' => 'mobile',
                'platform' => 'android',
                'target_type' => 'android_application',
                'target_app_package' => $validated['target_app_package'] ?? 'com.gagent.dummyandroid',
                'target_app_activity' => $validated['target_app_activity'] ?? 'com.gagent.dummyandroid.MainActivity',
                'apk_path' => $validated['apk_path'] ?? null,
                'device_name' => $validated['device_name'] ?? 'emulator-5554',
                'automation_driver' => 'appium',
                'run_mode' => 'android_manual_metrics',
                'status' => 'pending',
                'started_at' => Carbon::now(),
                'notes' => 'Phase 8 Android Appium experimental extension. Metrics entered through Laravel and sent to FastAPI /predict-android.',
            ]);

            UXMetric::create([
                'test_run_id' => $testRun->id,
                'flow_type' => $validated['flow_type'],
                'scenario_type' => 'android_manual_metrics',
                'viewport_type' => 'mobile',
                'device_type' => 'android_emulator',
                'platform_name' => 'Android',

                'task_completed' => (bool) $validated['task_completed'],
                'task_failed' => (bool) $validated['task_failed'],
                'completion_time' => (float) $validated['completion_time'],
                'click_count' => (int) $validated['click_count'],
                'scroll_count' => (int) $validated['scroll_count'],
                'keyboard_count' => (int) $validated['keyboard_count'],
                'retry_count' => (int) $validated['retry_count'],
                'error_count' => (int) $validated['error_count'],
                'failed_clicks' => (int) $validated['failed_clicks'],
                'unnecessary_clicks' => (int) $validated['unnecessary_clicks'],
                'path_deviation_score' => (float) $validated['path_deviation_score'],

                'app_launch_time_ms' => (float) $validated['app_launch_time_ms'],
                'screen_load_time_ms' => (float) $validated['screen_load_time_ms'],
                'feedback_delay_ms' => (float) $validated['feedback_delay_ms'],
                'interaction_response_time_ms' => (float) $validated['interaction_response_time_ms'],
                'finish_time_ms' => (float) $validated['finish_time_ms'],

                'error_message_present' => (bool) $validated['error_message_present'],
                'error_message_clarity' => (int) $validated['error_message_clarity'],
                'popup_detected' => (bool) $validated['popup_detected'],
                'overlay_blocks_action' => (bool) $validated['overlay_blocks_action'],
                'timeout_occurred' => (bool) $validated['timeout_occurred'],
                'crash_detected' => (bool) $validated['crash_detected'],
                'anr_detected' => (bool) $validated['anr_detected'],
            ]);
        });

        return redirect()
            ->route('android-tests.show', $testRun)
            ->with('success', 'Android test metrics saved. Click Run Android Prediction to call FastAPI.');
    }

    public function show(TestRun $testRun)
    {
        abort_unless($testRun->run_mode === 'android_manual_metrics', 404);

        $testRun->load([
            'project',
            'uxMetric',
            'androidResult',
        ]);

        return view('android-tests.show', compact('testRun'));
    }

    public function predict(TestRun $testRun, GAgentAIService $aiService)
    {
        abort_unless($testRun->run_mode === 'android_manual_metrics', 404);

        $testRun->load('uxMetric');

        if (!$testRun->uxMetric) {
            return redirect()
                ->route('android-tests.show', $testRun)
                ->with('error', 'No Android UX metrics found for this test run.');
        }

        $payload = $testRun->uxMetric->toAndroidPayload();
        $prediction = $aiService->predictAndroid($payload);

        if (($prediction['status'] ?? null) !== 'success') {
            return redirect()
                ->route('android-tests.show', $testRun)
                ->with('error', 'Android FastAPI prediction failed: ' . ($prediction['message'] ?? 'Unknown error.'));
        }

        $data = $prediction['data'] ?? [];

        try {
            DB::transaction(function () use ($testRun, $data, $payload) {
                FrictionResult::where('test_run_id', $testRun->id)
                    ->where('prediction_source', 'android_appium')
                    ->delete();

                FrictionResult::create([
                    'test_run_id' => $testRun->id,
                    'model_name' => 'android_appium_model',
                    'model_type' => $data['model_type'] ?? 'android_appium',
                    'prediction_source' => 'android_appium',
                    'friction_level' => $data['friction_level'] ?? $data['prediction'] ?? null,
                    'confidence_score' => $data['confidence_score'] ?? null,
                    'class_probabilities' => $data['class_probabilities'] ?? [],
                    'recommendations' => $data['recommendations'] ?? [],
                    'input_features' => $payload,
                    'is_final' => true,
                ]);

                $testRun->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'error_message' => null,
                ]);
            });
        } catch (Throwable $error) {
            return redirect()
                ->route('android-tests.show', $testRun)
                ->with('error', 'Android prediction returned but Laravel database save failed: ' . $error->getMessage());
        }

        return redirect()
            ->route('android-tests.show', $testRun)
            ->with('success', 'Android prediction saved successfully.');
    }
}
