<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\Report;
use App\Models\TestRun;
use App\Models\UXMetric;
use App\Services\AppiumAndroidTestService;
use App\Services\GAgentAIService;
use App\Services\PlaywrightLiveTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Screenshot;
use Throwable;
use App\Models\TestRunComparison;
use Illuminate\Support\Facades\Auth;
use App\Models\InteractionLog;

class UnifiedTestController extends Controller
{
   public function create(Request $request)
{
    $comparisonMode = false;
    $comparisonProject = null;
    $beforeRun = null;

    $projectId = $request->integer(
        'comparison_project'
    );

    $beforeRunId = $request->integer(
        'compare_from'
    );

    if ($projectId && $beforeRunId) {
        $comparisonProject = Project::query()
            ->ownedBy((int) Auth::id())
            ->findOrFail($projectId);

        $beforeRun = $comparisonProject
            ->testRuns()
            ->whereKey($beforeRunId)
            ->where('platform', 'web')
            ->firstOrFail();

        $comparisonMode = true;
    }

    return view(
        'unified-tests.create',
        [
            'comparisonMode' => $comparisonMode,
            'comparisonProject' => $comparisonProject,
            'beforeRun' => $beforeRun,
        ]
    );
}

    public function store(
        Request $request,
        PlaywrightLiveTestService $playwrightService,
        AppiumAndroidTestService $appiumService,
        GAgentAIService $aiService
    ) {
        $request->validate([
            'test_type' => ['required', 'in:website,android'],
        ]);

        if ($request->input('test_type') === 'website') {
            return $this->runWebsiteTest($request, $playwrightService, $aiService);
        }

        return $this->runAndroidTest($request, $appiumService, $aiService);
    }

    private function runWebsiteTest(
        Request $request,
        PlaywrightLiveTestService $playwrightService,
        GAgentAIService $aiService
    ) {
     $validated = $request->validate([
    'target_url' => [
        'required',
        'url',
        'max:2048',
    ],

    'web_flow_type' => [
        'required',
        'in:full_audit,auto,landing_navigation,cta_click,basic_search',
    ],

    'viewport_type' => [
        'required',
        'in:desktop,tablet,mobile',
    ],

    'network_condition' => [
        'required',
        'in:normal,slow',
    ],

    'max_duration_seconds' => [
        'required',
        'integer',
        'min:10',
        'max:300',
    ],

    'show_browser' => [
        'nullable',
        'in:0,1',
    ],

    'slow_mo_ms' => [
        'nullable',
        'integer',
        'min:0',
        'max:1000',
    ],

    'notes' => [
        'nullable',
        'string',
        'max:2000',
    ],

    'comparison_project_id' => [
        'nullable',
        'integer',
        'required_with:compare_from',
    ],

    'compare_from' => [
        'nullable',
        'integer',
        'required_with:comparison_project_id',
    ],
]);


        $webFlowType = $validated['web_flow_type'] ?? 'auto';

        $comparisonMode = filled(
    $validated['comparison_project_id'] ?? null
) && filled(
    $validated['compare_from'] ?? null
);

$beforeRun = null;

if ($comparisonMode) {
    $project = Project::query()
        ->ownedBy((int) Auth::id())
        ->findOrFail(
            (int) $validated['comparison_project_id']
        );

    $beforeRun = $project
        ->testRuns()
        ->whereKey(
            (int) $validated['compare_from']
        )
        ->where('platform', 'web')
        ->firstOrFail();

    /*
     * Force the new test to use the same website URL.
     * This prevents a manipulated request from comparing
     * two unrelated websites.
     */
    $validated['target_url'] = (
        $beforeRun->target_url
        ?: $beforeRun->page_url
        ?: $project->target_url
    );
} else {
    $project = $this->findOrCreateProject(
        targetType: 'web_application',
        targetUrl: $validated['target_url'],
        name: 'Website Test - ' . parse_url(
            $validated['target_url'],
            PHP_URL_HOST
        ),
        description: 'Auto-created project from unified website UX test.'
    );
}
        $startedAt = Carbon::now();

        $testRun = TestRun::create([
            'project_id' => $project->id,
            'run_code' => 'WEB-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5)),
            'flow_type' => $webFlowType,
            'scenario_type' => 'live_website',
            'viewport_type' => $validated['viewport_type'],
            'network_condition' => $validated['network_condition'],
            'run_mode' => 'live_website',
            'max_duration_seconds' => $validated['max_duration_seconds'],
            'page_url' => $validated['target_url'],
            'target_url' => $validated['target_url'],
            'platform' => 'web',
            'target_type' => 'web_application',
            'automation_driver' => 'playwright',
            'status' => 'running',
            'started_at' => $startedAt,
            'notes' => $comparisonMode
    ? (
        $validated['notes']
        ?? 'Comparison retest from test run ID '
            . $beforeRun->id
            . '.'
    )
    : (
        $validated['notes']
        ?? 'Created from unified Run UX Test page.'
    ),
        ]);

        try {
           $playwrightResult = $playwrightService->run([
    'target_url' =>
        $testRun->target_url,

    'flow_type' =>
        $testRun->flow_type,

    'viewport_type' =>
        $testRun->viewport_type,

    'network_condition' =>
        $testRun->network_condition,

    'max_duration_seconds' =>
        $testRun->max_duration_seconds,

    'test_run_id' =>
        $testRun->id,

    'show_browser' =>
        (bool) (
            (int) (
                $validated['show_browser'] ?? 0
            )
        ),

    'slow_mo_ms' =>
        (int) (
            $validated['slow_mo_ms'] ?? 0
        ),
]);

            $finishedAt = Carbon::now();

            $testRun->update([
                'playwright_exit_code' => $playwrightResult['exit_code'],
                'duration_seconds' => $startedAt->diffInMilliseconds($finishedAt) / 1000,
            ]);

            if (($playwrightResult['status'] ?? null) !== 'success') {
                $errorData = $playwrightResult['data'] ?? [];

                $testRun->update([
                    'status' => 'failed',
                    'completed_at' => $finishedAt,
                    'error_message' => $playwrightResult['message'] ?? 'Playwright failed.',
                    'raw_metrics_path' => $errorData['raw_metrics_path'] ?? null,
                ]);

                return redirect()
                    ->route('test-runs.show', $testRun)
                    ->with('error', 'Website test failed: ' . ($playwrightResult['message'] ?? 'Unknown Playwright error.'));
            }

            $data = $playwrightResult['data'] ?? [];
            $this->saveScreenshots($testRun, $data['screenshots'] ?? []);

            $report = null;

            if ($webFlowType === 'full_audit') {
                // Full Audit: setiap flow diprediksi terpisah, lalu class_probabilities dirata-ratakan.
                $flowResults = $data['flow_results'] ?? null;

                if (!is_array($flowResults) || $flowResults === []) {
                    throw new \RuntimeException('Full audit completed but no flow results were returned.');
                }

                DB::transaction(function () use ($testRun, $flowResults, $data, $aiService, $finishedAt, &$report) {
                    $auditPrediction = $this->averageFullWebsiteAudit($flowResults, $aiService);

                    $metrics = $auditPrediction['metrics'];
                    $predictionData = $auditPrediction['prediction_data'];
                    $inputFeatures = $auditPrediction['input_features'];
                    $flowPredictions = $auditPrediction['flow_predictions'];
                    $averageSeverityScore = $auditPrediction['average_severity_score'];

                    $this->saveWebsiteUXMetrics($testRun, $metrics);

                    FrictionResult::where('test_run_id', $testRun->id)->update(['is_final' => false]);

                    FrictionResult::create([
                        'test_run_id' => $testRun->id,
                        'model_name' => $predictionData['model_name'] ?? 'full_audit_average_ensemble',
                        'model_type' => $predictionData['model_type'] ?? 'main_gagent_average_ensemble',
                        'prediction_source' => 'main_gagent',
                        'friction_level' => $predictionData['friction_level'] ?? null,
                        'confidence_score' => $predictionData['confidence_score'] ?? null,
                        'class_probabilities' => $predictionData['class_probabilities'] ?? [],
                        'recommendations' => $predictionData['recommendations'] ?? [],
                        'input_features' => $inputFeatures,
                        'is_final' => true,
                    ]);

                    $level = $predictionData['friction_level'] ?? 'Unknown';

                    $this->saveWebsiteFlowResults($testRun, $flowResults, $flowPredictions);
                    $this->attachScreenshotPredictions($testRun, $flowPredictions);

                    $testedCount = 0;
                    $passedCount = 0;
                    $failedCount = 0;
                    $skippedCount = 0;

                    foreach ($flowResults as $flowResult) {
                        $status = (string) ($flowResult['status'] ?? 'unknown');

                        if ($status === 'passed') {
                            $testedCount++;
                            $passedCount++;
                        } elseif ($status === 'failed') {
                            $testedCount++;
                            $failedCount++;
                        } elseif ($status === 'skipped') {
                            $skippedCount++;
                        }
                    }

                    $summary = 'Full website audit completed for '
                        . $testRun->target_url
                        . ". {$testedCount} safe flows were tested: "
                        . "{$passedCount} passed, "
                        . "{$failedCount} failed and "
                        . "{$skippedCount} skipped. "
                        . 'The average AI severity score is '
                        . number_format((float) $averageSeverityScore, 2)
                        . '/3.00, producing an overall UX friction level of '
                        . $level
                        . '.';

                    $conclusion = 'GAgent detected available safe website feature categories, '
                        . 'tested each category separately, predicted each tested flow '
                        . 'with the existing web model, and averaged the class probabilities '
                        . 'to calculate the final result.';

                    $report = Report::updateOrCreate(
                        ['test_run_id' => $testRun->id],
                        [
                            'title' => 'Website UX Friction Report - ' . $testRun->run_code,
                            'summary' => $summary,
                            'conclusion' => $conclusion,
                            'generated_at' => Carbon::now(),
                        ]
                    );

                    $testRun->update([
                        'status' => 'completed',
                        'flow_type' => $data['flow_type'] ?? $testRun->flow_type,
                        'completed_at' => $finishedAt,
                        'error_message' => null,
                        'raw_metrics_path' => $data['raw_metrics_path'] ?? null,
                        'report_path' => route('reports.show', $report),
                    ]);
                });
            } else {
                // Non full-audit: single-flow prediction, tidak berubah dari perilaku sebelumnya.
                $metrics = $data['metrics'] ?? null;

                if (!is_array($metrics)) {
                    throw new \RuntimeException('Playwright completed but no metrics were returned.');
                }

                DB::transaction(function () use ($testRun, $metrics, $data, $aiService, $finishedAt, &$report) {
                    $uxMetric = $this->saveWebsiteUXMetrics($testRun, $metrics);
                    $payload = $uxMetric->toGAgentPayload();

                    $prediction = $aiService->predictGAgent($payload);

                    if (($prediction['status'] ?? null) !== 'success') {
                        throw new \RuntimeException($prediction['message'] ?? 'FastAPI web prediction failed.');
                    }

                    $predictionData = $prediction['data'] ?? [];
                    $inputFeatures = $payload;
                    $averageSeverityScore = null;

                    FrictionResult::where('test_run_id', $testRun->id)->update(['is_final' => false]);

                    FrictionResult::create([
                        'test_run_id' => $testRun->id,
                        'model_name' => $predictionData['model_name'] ?? 'main_gagent_model',
                        'model_type' => $predictionData['model_type'] ?? 'main_gagent',
                        'prediction_source' => 'main_gagent',
                        'friction_level' => $predictionData['friction_level'] ?? $predictionData['prediction'] ?? null,
                        'confidence_score' => $predictionData['confidence_score'] ?? $predictionData['confidence'] ?? null,
                        'class_probabilities' => $predictionData['class_probabilities'] ?? [],
                        'recommendations' => $predictionData['recommendations'] ?? $predictionData['recommendation'] ?? [],
                        'input_features' => $inputFeatures,
                        'is_final' => true,
                    ]);

                    $level = $predictionData['friction_level'] ?? $predictionData['prediction'] ?? 'Unknown';

                    $report = Report::updateOrCreate(
                        ['test_run_id' => $testRun->id],
                        [
                            'title' => 'Website UX Friction Report - ' . $testRun->run_code,
                            'summary' => 'Website test completed for ' . $testRun->target_url . '. Final UX friction level is ' . $level . '.',
                            'conclusion' => 'This report was generated from Playwright UX metrics and the GAgent web machine learning model.',
                            'generated_at' => Carbon::now(),
                        ]
                    );

                    $testRun->update([
                        'status' => 'completed',
                        'flow_type' => $data['flow_type'] ?? $testRun->flow_type,
                        'completed_at' => $finishedAt,
                        'error_message' => null,
                        'raw_metrics_path' => $data['raw_metrics_path'] ?? null,
                        'report_path' => route('reports.show', $report),
                    ]);
                });
            }

     if ($comparisonMode && $beforeRun) {
        $savedComparison = TestRunComparison::updateOrCreate(
            [
                'before_test_run_id' => $beforeRun->id,
                'after_test_run_id' => $testRun->id,
            ],
            [
                'project_id' => $project->id,
                'user_id' => (int) Auth::id(),
            ]
        );

            return redirect()
                ->route(
                    'comparisons.show',
                    $savedComparison
                )
                ->with(
                    'success',
                    'The new website test was completed and the comparison was saved.'
                );
        }

return redirect()
    ->route('reports.show', $report)
    ->with(
        'success',
        'Website test completed. Project, test run, prediction, and report were created automatically.'
    );
        } catch (Throwable $error) {
            $testRun->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'error_message' => $error->getMessage(),
            ]);

            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Website test failed: ' . $error->getMessage());
        }
    }

    private function runAndroidTest(
        Request $request,
        AppiumAndroidTestService $appiumService,
        GAgentAIService $aiService
    ) {
       $validated = $request->validate([
    'android_flow_type' => [
        'required',
        'in:login,signup,search,button_click,form_submit',
    ],

    'target_app_package' => [
        'required',
        'string',
        'max:255',
        'regex:/^[A-Za-z0-9_.]+$/',
    ],

    'target_app_activity' => [
        'required',
        'string',
        'max:255',
    ],

    'apk_path' => [
        'nullable',
        'string',
        'max:2048',
        'required_without:apk_file',
    ],

    'apk_file' => [
        'nullable',
        'file',
        'max:102400',
        'required_without:apk_path',
    ],

    'device_name' => [
        'nullable',
        'string',
        'max:255',
    ],

    'notes' => [
        'nullable',
        'string',
        'max:2000',
    ],
]);

       $package = trim(
    $validated['target_app_package']
);

$activity = trim(
    $validated['target_app_activity']
);

$deviceName = filled(
    $validated['device_name'] ?? null
)
    ? trim($validated['device_name'])
    : 'emulator-5554';

$scenarioType = 'real_app';

$apkPath = filled(
    $validated['apk_path'] ?? null
)
    ? $validated['apk_path']
    : null;

        if ($request->hasFile('apk_file')) {
            $apkFile = $request->file('apk_file');

            if (strtolower($apkFile->getClientOriginalExtension()) !== 'apk') {
                return back()
                    ->withErrors(['apk_file' => 'The uploaded file must be an APK file.'])
                    ->withInput();
            }

            $safeName = 'android-app-' . now()->format('Ymd-His') . '-' . Str::random(8) . '.apk';
            $storedPath = $apkFile->storeAs('android-apks', $safeName);
            $apkPath = Storage::path($storedPath);
        }

        $project = $this->findOrCreateProject(
            targetType: 'android_application',
            targetUrl: 'android://' . $package,
            name: 'Android Test - ' . $package,
            description: 'Auto-created project from unified Android Appium UX test.'
        );

        $startedAt = Carbon::now();

        $testRun = TestRun::create([
            'project_id' => $project->id,
            'run_code' => 'ANDROID-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5)),
            'flow_type' => $validated['android_flow_type'],
            'scenario_type' => $scenarioType,
            'viewport_type' => 'mobile',
            'platform' => 'android',
            'target_type' => 'android_application',
            'target_app_package' => $package,
            'target_app_activity' => $activity,
            'apk_path' => $apkPath,
            'device_name' => $deviceName,
            'automation_driver' => 'appium',
            'run_mode' => 'android_appium_auto',
            'status' => 'running',
            'started_at' => $startedAt,
            'notes' => $validated['notes'] ?? 'Created from unified Run UX Test page.',
        ]);

        try {
            $appiumResult = $appiumService->run([
                'test_run_id' => $testRun->id,
                'apk_path' => $apkPath,
                'flow_type' => $validated['android_flow_type'],

                'target_app_package' => $package,
                'target_app_activity' => $activity,
                'device_name' => $deviceName,
            ]);

            $finishedAt = Carbon::now();

            $testRun->update([
                'appium_exit_code' => $appiumResult['exit_code'],
                'duration_seconds' => $startedAt->diffInMilliseconds($finishedAt) / 1000,
                'raw_metrics_path' => $appiumResult['raw_metrics_path'] ?? null,
            ]);

            if (($appiumResult['status'] ?? null) !== 'success') {
                $testRun->update([
                    'status' => 'failed',
                    'completed_at' => $finishedAt,
                    'error_message' => $appiumResult['message'] ?? 'Appium failed.',
                ]);

                return redirect()
                    ->route('test-runs.show', $testRun)
                    ->with('error', 'Android Appium test failed: ' . ($appiumResult['message'] ?? 'Unknown Appium error.'));
            }

            $metrics = $appiumResult['metrics'] ?? null;

            if (!is_array($metrics)) {
                throw new \RuntimeException('Appium completed but no selected metrics row was returned.');
            }

            $report = null;

            DB::transaction(function () use ($testRun, $metrics, $aiService, $finishedAt, &$report) {
                $uxMetric = $this->saveAndroidUXMetrics($testRun, $metrics);
                $payload = $uxMetric->toAndroidPayload();

                $prediction = $aiService->predictAndroid($payload);

                if (($prediction['status'] ?? null) !== 'success') {
                    throw new \RuntimeException($prediction['message'] ?? 'FastAPI Android prediction failed.');
                }

                $predictionData = $prediction['data'] ?? [];

                FrictionResult::where('test_run_id', $testRun->id)->update(['is_final' => false]);

                FrictionResult::create([
                    'test_run_id' => $testRun->id,
                    'model_name' => 'android_appium_model',
                    'model_type' => $predictionData['model_type'] ?? 'android_appium',
                    'prediction_source' => 'android_appium',
                    'friction_level' => $predictionData['friction_level'] ?? $predictionData['prediction'] ?? null,
                    'confidence_score' => $predictionData['confidence_score'] ?? null,
                    'class_probabilities' => $predictionData['class_probabilities'] ?? [],
                    'recommendations' => $predictionData['recommendations'] ?? [],
                    'input_features' => $payload,
                    'is_final' => true,
                ]);

                $level = $predictionData['friction_level'] ?? $predictionData['prediction'] ?? 'Unknown';

                $report = Report::updateOrCreate(
                    ['test_run_id' => $testRun->id],
                    [
                        'title' => 'Android UX Friction Report - ' . $testRun->run_code,
                        'summary' => 'Android Appium test completed for ' . $testRun->flow_type . ' / ' . $testRun->scenario_type . '. Final UX friction level is ' . $level . '.',
                        'conclusion' => 'This report was generated from Appium Android UX metrics and the GAgent Android machine learning model.',
                        'generated_at' => Carbon::now(),
                    ]
                );

                $testRun->update([
                    'status' => 'completed',
                    'completed_at' => $finishedAt,
                    'error_message' => null,
                    'report_path' => route('reports.show', $report),
                ]);
            });
return redirect()
    ->route('reports.show', $report)
    ->with(
        'success',
        'Android test completed. Project, test run, prediction, and report were created automatically.'
    );
        } catch (Throwable $error) {
            $testRun->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'error_message' => $error->getMessage(),
            ]);

            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Android test failed: ' . $error->getMessage());
        }
    }

   private function findOrCreateProject(
    string $targetType,
    ?string $targetUrl,
    string $name,
    string $description
): Project {
    return Project::firstOrCreate(
        [
            'user_id' => (int) Auth::id(),
            'target_type' => $targetType,
            'target_url' => $targetUrl,
        ],
        [
            'name' => $name,
            'description' => $description,
            'status' => 'active',
        ]
    );
}


    private function buildWebsitePayload(array $metrics): array
{
    $rawFlowType = (string) (
        $metrics['flow_type']
        ?? 'landing_navigation'
    );

    $flowTypeMap = [
        'landing_navigation' => 'landing_navigation',
        'header_navigation' => 'landing_navigation',
        'category_navigation' => 'landing_navigation',
        'product_navigation' => 'landing_navigation',

        'basic_search' => 'search',
        'search' => 'search',

        'cta_click' => 'cta_click',
        'menu_toggle' => 'cta_click',

        'login' => 'login',
        'signup' => 'signup',
    ];

    $flowType = $flowTypeMap[$rawFlowType]
        ?? 'landing_navigation';

    return [
        'flow_type' => $flowType,

        'viewport_type' => (string) (
            $metrics['viewport_type']
            ?? 'desktop'
        ),

        'task_completed' => (int) (
            $metrics['task_completed']
            ?? 0
        ),

        'task_failed' => (int) (
            $metrics['task_failed']
            ?? 1
        ),

        'completion_time' => (float) (
            $metrics['completion_time']
            ?? 0
        ),

        'click_count' => (int) (
            $metrics['click_count']
            ?? 0
        ),

        'scroll_count' => (int) (
            $metrics['scroll_count']
            ?? 0
        ),

        'keyboard_count' => (int) (
            $metrics['keyboard_count']
            ?? 0
        ),

        'retry_count' => (int) (
            $metrics['retry_count']
            ?? 0
        ),

        'error_count' => (int) (
            $metrics['error_count']
            ?? 0
        ),

        'failed_clicks' => (int) (
            $metrics['failed_clicks']
            ?? 0
        ),

        'unnecessary_clicks' => (int) (
            $metrics['unnecessary_clicks']
            ?? 0
        ),

        'path_deviation_score' => (float) (
            $metrics['path_deviation_score']
            ?? 0
        ),

        'page_load_time_ms' => (float) (
            $metrics['page_load_time_ms']
            ?? 0
        ),

        'dom_content_loaded_ms' => (float) (
            $metrics['dom_content_loaded_ms']
            ?? 0
        ),

        'time_to_first_byte_ms' => (float) (
            $metrics['time_to_first_byte_ms']
            ?? 0
        ),

        'feedback_delay_ms' => (float) (
            $metrics['feedback_delay_ms']
            ?? 0
        ),

        'interaction_to_next_paint_ms' => (float) (
            $metrics['interaction_to_next_paint_ms']
            ?? 0
        ),

        'cumulative_layout_shift' => (float) (
            $metrics['cumulative_layout_shift']
            ?? 0
        ),

        'error_message_present' => (int) (
            $metrics['error_message_present']
            ?? 0
        ),

        'error_message_clarity' => (int) (
            $metrics['error_message_clarity']
            ?? -1
        ),

        'popup_detected' => (int) (
            $metrics['popup_detected']
            ?? 0
        ),

        'cookie_banner_detected' => (int) (
            $metrics['cookie_banner_detected']
            ?? 0
        ),

        'overlay_blocks_cta' => (int) (
            $metrics['overlay_blocks_cta']
            ?? 0
        ),
    ];
}
    private function averageFullWebsiteAudit(
        array $flowResults,
        GAgentAIService $aiService
    ): array {
        $flowPredictions = [];

        $probabilityTotals = [
            'Low' => 0.0,
            'Medium' => 0.0,
            'High' => 0.0,
        ];

        $predictedFlowCount = 0;
        $allRecommendations = [];

        foreach ($flowResults as $flowResult) {
            $flowKey = (string) (
                $flowResult['audit_flow']
                ?? $flowResult['flow_type']
                ?? 'unknown_flow'
            );

            $metrics = $flowResult['metrics'] ?? null;
            $status = (string) ($flowResult['status'] ?? 'unknown');

            if (!is_array($metrics) || $status === 'skipped') {
                $flowPredictions[$flowKey] = [
                    'status' => 'skipped',
                    'audit_flow' => $flowKey,
                    'label' => $flowResult['label'] ?? $flowKey,
                    'reason' => $flowResult['reason'] ?? 'Flow was skipped.',
                ];

                continue;
            }

            $payload = $this->buildWebsitePayload($metrics);

            $prediction = $aiService->predictGAgent($payload);

            if (($prediction['status'] ?? null) !== 'success') {
                throw new \RuntimeException(
                    'AI prediction failed for flow '
                    . ($flowResult['label'] ?? $flowKey)
                    . ': '
                    . ($prediction['message'] ?? 'Unknown FastAPI error.')
                );
            }

            $predictionData = $prediction['data'] ?? [];

            $frictionLevel = (string) (
                $predictionData['friction_level']
                ?? $predictionData['prediction']
                ?? 'Low'
            );

            $probabilities = $this->normaliseFlowProbabilities(
                $predictionData['class_probabilities'] ?? [],
                $frictionLevel
            );

            $severityScore =
                ($probabilities['Low'] * 1)
                + ($probabilities['Medium'] * 2)
                + ($probabilities['High'] * 3);

            foreach (['Low', 'Medium', 'High'] as $className) {
                $probabilityTotals[$className] += $probabilities[$className];
            }

            $predictedFlowCount++;

            $recommendations = $predictionData['recommendations'] ?? [];

            if (is_string($recommendations)) {
                $recommendations = [$recommendations];
            }

            if (is_array($recommendations)) {
                foreach ($recommendations as $recommendation) {
                    if (is_string($recommendation) && trim($recommendation) !== '') {
                        $allRecommendations[] = trim($recommendation);
                    }
                }
            }

            $flowPredictions[$flowKey] = [
                'status' => 'predicted',
                'audit_flow' => $flowKey,
                'model_flow' => $payload['flow_type'],
                'label' => $flowResult['label'] ?? $flowKey,
                'friction_level' => $frictionLevel,
                'confidence_score' => (float) (
                    $predictionData['confidence_score']
                    ?? $predictionData['confidence']
                    ?? $probabilities[$frictionLevel]
                    ?? 0
                ),
                'class_probabilities' => $probabilities,
                'severity_score' => round($severityScore, 3),
                'recommendations' => $recommendations,
            ];
        }

        if ($predictedFlowCount === 0) {
            throw new \RuntimeException(
                'Full audit completed, but no tested flow produced a valid AI prediction.'
            );
        }

        $averageProbabilities = [];

        foreach (['Low', 'Medium', 'High'] as $className) {
            $averageProbabilities[$className] = round(
                $probabilityTotals[$className] / $predictedFlowCount,
                4
            );
        }

        $averageSeverityScore =
            ($averageProbabilities['Low'] * 1)
            + ($averageProbabilities['Medium'] * 2)
            + ($averageProbabilities['High'] * 3);

        $finalLevel = match (true) {
            $averageSeverityScore < 1.5 => 'Low',
            $averageSeverityScore < 2.5 => 'Medium',
            default => 'High',
        };

        $aggregateMetrics = $this->aggregateFullAuditMetrics($flowResults);

        $allRecommendations = array_values(array_unique($allRecommendations));

        return [
            'metrics' => $aggregateMetrics,
            'flow_predictions' => $flowPredictions,
            'average_severity_score' => round($averageSeverityScore, 3),

            'prediction_data' => [
                'model_name' => 'full_audit_average_ensemble',
                'model_type' => 'main_gagent_average_ensemble',
                'friction_level' => $finalLevel,
                'confidence_score' => $averageProbabilities[$finalLevel],
                'class_probabilities' => $averageProbabilities,
                'recommendations' => $allRecommendations,
            ],

            'input_features' => [
                'audit_mode' => 'full_audit',
                'aggregation_method' => 'mean_class_probabilities',
                'predicted_flow_count' => $predictedFlowCount,
                'average_severity_score' => round($averageSeverityScore, 3),
                'aggregate_metrics' => $aggregateMetrics,
                'flow_predictions' => $flowPredictions,
            ],
        ];
    }

    private function normaliseFlowProbabilities(
        array $probabilities,
        string $fallbackLevel
    ): array {
        $normalised = [
            'Low' => max(0, (float) ($probabilities['Low'] ?? 0)),
            'Medium' => max(0, (float) ($probabilities['Medium'] ?? 0)),
            'High' => max(0, (float) ($probabilities['High'] ?? 0)),
        ];

        $total = array_sum($normalised);

        if ($total <= 0) {
            $normalised = [
                'Low' => 0.0,
                'Medium' => 0.0,
                'High' => 0.0,
            ];

            if (array_key_exists($fallbackLevel, $normalised)) {
                $normalised[$fallbackLevel] = 1.0;
            } else {
                $normalised['Low'] = 1.0;
            }

            return $normalised;
        }

        foreach ($normalised as $className => $value) {
            $normalised[$className] = round($value / $total, 4);
        }

        return $normalised;
    }

    private function aggregateFullAuditMetrics(array $flowResults): array
    {
        $metricRows = [];

        foreach ($flowResults as $flowResult) {
            if (is_array($flowResult['metrics'] ?? null)) {
                $metricRows[] = $flowResult['metrics'];
            }
        }

        if ($metricRows === []) {
            throw new \RuntimeException(
                'No flow metrics were available for aggregation.'
            );
        }

        $sumFields = [
            'completion_time',
            'click_count',
            'scroll_count',
            'keyboard_count',
            'retry_count',
            'error_count',
            'failed_clicks',
            'unnecessary_clicks',
        ];

        $averageFields = [
            'path_deviation_score',
            'page_load_time_ms',
            'dom_content_loaded_ms',
            'time_to_first_byte_ms',
            'feedback_delay_ms',
        ];

        $maximumFields = [
            'interaction_to_next_paint_ms',
            'cumulative_layout_shift',
            'error_message_present',
            'popup_detected',
            'cookie_banner_detected',
            'overlay_blocks_cta',
        ];

        $aggregate = [
            'flow_type' => 'full_audit',
            'viewport_type' => $metricRows[0]['viewport_type'] ?? 'desktop',
            'task_completed' => 1,
            'task_failed' => 0,
            'error_message_clarity' => -1,
        ];

        foreach ($sumFields as $field) {
            $aggregate[$field] = array_sum(
                array_map(
                    fn (array $row) => (float) ($row[$field] ?? 0),
                    $metricRows
                )
            );
        }

        foreach ($averageFields as $field) {
            $aggregate[$field] = round(
                array_sum(
                    array_map(
                        fn (array $row) => (float) ($row[$field] ?? 0),
                        $metricRows
                    )
                ) / count($metricRows),
                3
            );
        }

        foreach ($maximumFields as $field) {
            $aggregate[$field] = max(
                array_map(
                    fn (array $row) => (float) ($row[$field] ?? 0),
                    $metricRows
                )
            );
        }

        foreach ($metricRows as $row) {
            if ((int) ($row['task_failed'] ?? 0) === 1) {
                $aggregate['task_completed'] = 0;
                $aggregate['task_failed'] = 1;
            }

            $aggregate['error_message_clarity'] = max(
                (int) $aggregate['error_message_clarity'],
                (int) ($row['error_message_clarity'] ?? -1)
            );
        }

        foreach (
            [
                'click_count',
                'scroll_count',
                'keyboard_count',
                'retry_count',
                'error_count',
                'failed_clicks',
                'unnecessary_clicks',
            ] as $integerField
        ) {
            $aggregate[$integerField] = (int) round($aggregate[$integerField]);
        }

        $aggregate['completion_time'] = round((float) $aggregate['completion_time'], 3);

        return $aggregate;
    }

    private function saveWebsiteFlowResults(
        TestRun $testRun,
        array $flowResults,
        array $flowPredictions
    ): void {
        foreach ($flowResults as $flowResult) {
            $flowKey = (string) (
                $flowResult['audit_flow']
                ?? $flowResult['flow_type']
                ?? 'unknown_flow'
            );

            $metrics = is_array($flowResult['metrics'] ?? null)
                ? $flowResult['metrics']
                : [];

            InteractionLog::create([
                'test_run_id' => $testRun->id,
                'event_type' => 'audit_flow',
                'event_label' => $flowResult['label'] ?? $flowKey,
                'event_value' => $flowResult['status'] ?? 'unknown',
                'event_time' => (float) ($metrics['completion_time'] ?? 0),

                'metadata' => [
                    'audit_flow' => $flowKey,
                    'model_flow' => $flowResult['model_flow'] ?? $metrics['flow_type'] ?? null,
                    'detected' => (bool) ($flowResult['detected'] ?? false),
                    'status' => $flowResult['status'] ?? 'unknown',
                    'reason' => $flowResult['reason'] ?? null,
                    'metrics' => $metrics,
                    'prediction' => $flowPredictions[$flowKey] ?? null,
                ],
            ]);
        }
    }

    private function attachScreenshotPredictions(
        TestRun $testRun,
        array $flowPredictions
    ): void {
        foreach ($testRun->screenshots()->get() as $screenshot) {
            $flowKey = (string) ($screenshot->flow_key ?? '');

            if ($flowKey === '' || !isset($flowPredictions[$flowKey])) {
                continue;
            }

            $prediction = $flowPredictions[$flowKey];

            if (($prediction['status'] ?? null) !== 'predicted') {
                continue;
            }

            $screenshot->update([
                'friction_level' => $prediction['friction_level'] ?? null,
                'confidence_score' => $prediction['confidence_score'] ?? null,
            ]);
        }
    }

    private function saveWebsiteUXMetrics(TestRun $testRun, array $metrics): UXMetric
    {
        return UXMetric::updateOrCreate(
            ['test_run_id' => $testRun->id],
            [
                'flow_type' => ($metrics['flow_type'] ?? $testRun->flow_type) === 'basic_search'
                    ? 'search'
                    : ($metrics['flow_type'] ?? $testRun->flow_type),
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

    private function saveAndroidUXMetrics(TestRun $testRun, array $metrics): UXMetric
    {
        return UXMetric::updateOrCreate(
            ['test_run_id' => $testRun->id],
            [
                'flow_type' => $metrics['flow_type'] ?? $testRun->flow_type,
                'scenario_type' => $metrics['scenario_type'] ?? $testRun->scenario_type,
                'viewport_type' => 'mobile',
                'device_type' => $metrics['device_type'] ?? 'android_emulator',
                'platform_name' => $metrics['platform_name'] ?? 'Android',

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

                'app_launch_time_ms' => (float) ($metrics['app_launch_time_ms'] ?? 0),
                'screen_load_time_ms' => (float) ($metrics['screen_load_time_ms'] ?? 0),
                'feedback_delay_ms' => (float) ($metrics['feedback_delay_ms'] ?? 0),
                'interaction_response_time_ms' => (float) ($metrics['interaction_response_time_ms'] ?? 0),
                'finish_time_ms' => (float) ($metrics['finish_time_ms'] ?? 0),

                'error_message_present' => (bool) ($metrics['error_message_present'] ?? 0),
                'error_message_clarity' => $this->mapAndroidErrorClarity($metrics['error_message_clarity'] ?? 'none'),
                'popup_detected' => (bool) ($metrics['popup_detected'] ?? 0),
                'overlay_blocks_action' => (bool) ($metrics['overlay_blocks_action'] ?? 0),
                'timeout_occurred' => (bool) ($metrics['timeout_occurred'] ?? 0),
                'crash_detected' => (bool) ($metrics['crash_detected'] ?? 0),
                'anr_detected' => (bool) ($metrics['anr_detected'] ?? 0),
            ]
        );
    }

    private function mapAndroidErrorClarity(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return match (strtolower((string) $value)) {
            'none' => -1,
            'vague' => 0,
            'medium' => 1,
            'clear' => 2,
            default => -1,
        };
    }

    private function saveScreenshots(TestRun $testRun, array $screenshots): void
    {
        foreach ($screenshots as $index => $screenshot) {
            $sourcePath = $screenshot['file_path'] ?? null;

            if (!$sourcePath || !file_exists($sourcePath)) {
                continue;
            }

            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'png';

            $fileName = 'test-run-' . $testRun->id . '-' . ($index + 1) . '-' . time() . '.' . $extension;

            $storageFolder = storage_path('app/public/test-run-screenshots');

            if (!is_dir($storageFolder)) {
                mkdir($storageFolder, 0775, true);
            }

            $destinationPath = $storageFolder . DIRECTORY_SEPARATOR . $fileName;

            copy($sourcePath, $destinationPath);

            Screenshot::create([
                'test_run_id' => $testRun->id,
                'file_path' => 'test-run-screenshots/' . $fileName,
                'label' => $screenshot['label'] ?? 'Screenshot Evidence',
                'flow_key' => $screenshot['flow_key'] ?? null,
                'captured_at' => now(),
            ]);
        }
    }
}
