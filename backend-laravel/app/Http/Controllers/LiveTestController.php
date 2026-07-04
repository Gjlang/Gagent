<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\Report;
use App\Models\TestRun;
use App\Models\UXMetric;
use App\Services\GAgentAIService;
use App\Services\PlaywrightLiveTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LiveTestController extends Controller
{
    public function create()
    {
        $projects = Project::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('live-tests.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'target_url' => ['required', 'url', 'max:2048'],
            'flow_type' => ['required', 'in:landing_navigation,cta_click,basic_search'],
            'viewport_type' => ['required', 'in:desktop,tablet,mobile'],
            'network_condition' => ['required', 'in:normal,slow'],
            'max_duration_seconds' => ['required', 'integer', 'min:10', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $testRun = TestRun::create([
            'project_id' => $validated['project_id'],
            'run_code' => 'LIVE-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5)),
            'flow_type' => $validated['flow_type'],
            'scenario_type' => 'live_website',
            'viewport_type' => $validated['viewport_type'],
            'network_condition' => $validated['network_condition'],
            'run_mode' => 'live_website',
            'max_duration_seconds' => $validated['max_duration_seconds'],
            'page_url' => $validated['target_url'],
            'target_url' => $validated['target_url'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('live-tests.show', $testRun)
            ->with('success', 'Live website test run created. Click Run Live Test to execute Playwright.');
    }

    public function show(TestRun $testRun)
    {
        abort_unless($testRun->run_mode === 'live_website', 404);

        $testRun->load([
            'project',
            'uxMetric',
            'frictionResults',
            'finalFrictionResult',
            'mainGAgentResult',
            'report',
        ]);

        return view('live-tests.show', compact('testRun'));
    }

    public function run(
        TestRun $testRun,
        PlaywrightLiveTestService $playwrightService,
        GAgentAIService $aiService
    ) {
        abort_unless($testRun->run_mode === 'live_website', 404);

        if ($testRun->status === 'running') {
            return redirect()
                ->route('live-tests.show', $testRun)
                ->with('error', 'This live test is already running.');
        }

        $startedAt = Carbon::now();

        $testRun->update([
            'status' => 'running',
            'started_at' => $startedAt,
            'completed_at' => null,
            'duration_seconds' => null,
            'playwright_exit_code' => null,
            'error_message' => null,
        ]);

        try {
            $playwrightResult = $playwrightService->run([
                'target_url' => $testRun->target_url,
                'flow_type' => $testRun->flow_type,
                'viewport_type' => $testRun->viewport_type,
                'network_condition' => $testRun->network_condition,
                'max_duration_seconds' => $testRun->max_duration_seconds,
                'test_run_id' => $testRun->id,
            ]);

            $finishedAt = Carbon::now();
            $durationSeconds = $startedAt->diffInMilliseconds($finishedAt) / 1000;

            $testRun->update([
                'playwright_exit_code' => $playwrightResult['exit_code'],
                'duration_seconds' => $durationSeconds,
            ]);

            if (($playwrightResult['status'] ?? null) !== 'success') {
                $message = $playwrightResult['message'] ?? 'Playwright live test failed.';

                $metrics = $playwrightResult['data']['metrics'] ?? null;

                if (is_array($metrics)) {
                    $this->saveUXMetrics($testRun, $metrics);
                }

                $testRun->update([
                    'status' => 'failed',
                    'completed_at' => $finishedAt,
                    'error_message' => $message,
                    'raw_metrics_path' => $playwrightResult['data']['raw_metrics_path'] ?? null,
                ]);

                return redirect()
                    ->route('live-tests.show', $testRun)
                    ->with('error', $message);
            }

            $data = $playwrightResult['data'] ?? [];
            $metrics = $data['metrics'] ?? null;

            if (!is_array($metrics)) {
                throw new \RuntimeException('Playwright completed but no metrics were returned.');
            }

            DB::transaction(function () use ($testRun, $metrics, $data, $aiService, $finishedAt) {
                $uxMetric = $this->saveUXMetrics($testRun, $metrics);

                $payload = $uxMetric->toGAgentPayload();

                $prediction = $aiService->predictGAgent($payload);

                if (($prediction['status'] ?? null) !== 'success') {
                    $message = $prediction['message'] ?? 'FastAPI prediction failed.';
                    $details = $prediction['details'] ?? '';
                    throw new \RuntimeException(trim($message . ' ' . $details));
                }

                $predictionData = $prediction['data'] ?? [];

                FrictionResult::where('test_run_id', $testRun->id)
                    ->where('prediction_source', 'main_gagent')
                    ->delete();

                FrictionResult::where('test_run_id', $testRun->id)
                    ->update(['is_final' => false]);

                FrictionResult::create([
                    'test_run_id' => $testRun->id,
                    'model_name' => $predictionData['model_name'] ?? $predictionData['model_used'] ?? 'main_gagent_model',
                    'model_type' => $predictionData['model_type'] ?? 'main_gagent',
                    'prediction_source' => 'main_gagent',
                    'friction_level' => $predictionData['friction_level'] ?? $predictionData['prediction'] ?? null,
                    'confidence_score' => $predictionData['confidence_score'] ?? $predictionData['confidence'] ?? null,
                    'class_probabilities' => $predictionData['class_probabilities'] ?? [],
                    'recommendations' => $predictionData['recommendations'] ?? $predictionData['recommendation'] ?? [],
                    'input_features' => $payload,
                    'is_final' => true,
                ]);

                $finalLevel = $predictionData['friction_level'] ?? $predictionData['prediction'] ?? 'Unknown';
                $confidence = $predictionData['confidence_score'] ?? $predictionData['confidence'] ?? null;

                $summary = 'Live website test completed for ' . $testRun->target_url .
                    '. Final UX friction level is ' . $finalLevel .
                    ($confidence !== null ? ' with confidence score ' . number_format($confidence * 100, 1) . '%.' : '.');

                $conclusion = 'This report was generated from live Playwright UX metrics and the main GAgent machine learning model. The URL itself was stored as metadata and was not used as a model input.';

                $report = Report::updateOrCreate(
                    ['test_run_id' => $testRun->id],
                    [
                        'title' => 'Live UX Friction Report - ' . $testRun->run_code,
                        'summary' => $summary,
                        'conclusion' => $conclusion,
                        'generated_at' => Carbon::now(),
                    ]
                );

                $testRun->update([
                    'status' => 'completed',
                    'completed_at' => $finishedAt,
                    'error_message' => null,
                    'raw_metrics_path' => $data['raw_metrics_path'] ?? null,
                    'report_path' => route('reports.show', $report),
                ]);
            });

            return redirect()
                ->route('live-tests.show', $testRun)
                ->with('success', 'Live website test completed, prediction saved, and report generated.');
        } catch (Throwable $error) {
            $finishedAt = Carbon::now();

            $testRun->update([
                'status' => 'failed',
                'completed_at' => $finishedAt,
                'duration_seconds' => $startedAt->diffInMilliseconds($finishedAt) / 1000,
                'error_message' => $error->getMessage(),
            ]);

            return redirect()
                ->route('live-tests.show', $testRun)
                ->with('error', 'Live website test failed: ' . $error->getMessage());
        }
    }

    private function saveUXMetrics(TestRun $testRun, array $metrics): UXMetric
    {
        return UXMetric::updateOrCreate(
            ['test_run_id' => $testRun->id],
            [
                'flow_type' => $metrics['flow_type'] ?? $this->mapFlowTypeForModel($testRun->flow_type),
                'scenario_type' => 'live_website',
                'viewport_type' => $metrics['viewport_type'] ?? $testRun->viewport_type,

                'task_completed' => (bool) ($metrics['task_completed'] ?? 0),
                'task_failed' => (bool) ($metrics['task_failed'] ?? 1),

                'completion_time' => (float) ($metrics['completion_time'] ?? 0),
                'click_count' => (int) ($metrics['click_count'] ?? 0),
                'scroll_count' => (int) ($metrics['scroll_count'] ?? 0),
                'keyboard_count' => (int) ($metrics['keyboard_count'] ?? 0),
                'retry_count' => (int) ($metrics['retry_count'] ?? 0),
                'error_count' => (int) ($metrics['error_count'] ?? 0),
                'failed_clicks' => (int) ($metrics['failed_clicks'] ?? 0),
                'unnecessary_clicks' => (int) ($metrics['unnecessary_clicks'] ?? 0),

                'path_deviation_score' => (float) ($metrics['path_deviation_score'] ?? 0),
                'page_load_time_ms' => (float) ($metrics['page_load_time_ms'] ?? 0),
                'dom_content_loaded_ms' => (float) ($metrics['dom_content_loaded_ms'] ?? 0),
                'time_to_first_byte_ms' => (float) ($metrics['time_to_first_byte_ms'] ?? 0),
                'feedback_delay_ms' => (float) ($metrics['feedback_delay_ms'] ?? 0),
                'interaction_to_next_paint_ms' => (float) ($metrics['interaction_to_next_paint_ms'] ?? 0),
                'cumulative_layout_shift' => (float) ($metrics['cumulative_layout_shift'] ?? 0),

                'error_message_present' => (bool) ($metrics['error_message_present'] ?? 0),
                'error_message_clarity' => (int) ($metrics['error_message_clarity'] ?? -1),
                'popup_detected' => (bool) ($metrics['popup_detected'] ?? 0),
                'cookie_banner_detected' => (bool) ($metrics['cookie_banner_detected'] ?? 0),
                'overlay_blocks_cta' => (bool) ($metrics['overlay_blocks_cta'] ?? 0),
            ]
        );
    }

    private function mapFlowTypeForModel(string $flowType): string
    {
        return $flowType === 'basic_search' ? 'search' : $flowType;
    }
}
