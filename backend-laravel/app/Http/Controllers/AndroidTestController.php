<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\TestRun;
use App\Models\UXMetric;
use App\Services\AppiumAndroidTestService;
use App\Services\GAgentAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AndroidTestController extends Controller
{
    private const DUMMY_APK_RELATIVE_PATH =
        '../phase8_android_dummy_app/app/build/outputs/apk/debug/app-debug.apk';

    public function create()
    {
        $projects = Project::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'android-tests.create',
            compact('projects')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                'exists:projects,id',
            ],

            'test_mode' => [
                'required',
                'in:dummy_app,real_apk,installed_app',
            ],

            'flow_type' => [
                'required',
                'in:basic_navigation,button_click,form_input,search_flow',
            ],

            'apk_path' => [
                'nullable',
                'string',
                'max:2048',
                'required_if:test_mode,real_apk',
            ],

            'apk_file' => [
                'nullable',
                'file',
                'max:102400',
            ],

            'app_package' => [
                'nullable',
                'string',
                'max:255',
                'required_if:test_mode,installed_app',
            ],

            'app_activity' => [
                'nullable',
                'string',
                'max:255',
            ],

            'device_name' => [
                'required',
                'string',
                'max:255',
            ],

            'max_duration_seconds' => [
                'required',
                'integer',
                'min:10',
                'max:180',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $apkPath = trim(
            (string) (
                $validated['apk_path'] ?? ''
            )
        );

        if ($request->hasFile('apk_file')) {
            $apkFile = $request->file('apk_file');

            if (
                strtolower(
                    $apkFile->getClientOriginalExtension()
                ) !== 'apk'
            ) {
                return back()
                    ->withErrors([
                        'apk_file' => (
                            'The uploaded file must have an .apk extension.'
                        ),
                    ])
                    ->withInput();
            }

            $fileName = (
                'android-app-'
                . now()->format('Ymd-His')
                . '-'
                . Str::lower(Str::random(8))
                . '.apk'
            );

            $storedPath = $apkFile->storeAs(
                'android-apks',
                $fileName
            );

            $apkPath = Storage::path(
                $storedPath
            );
        }

        if (
            $validated['test_mode'] === 'dummy_app'
        ) {
            $apkPath = base_path(
                self::DUMMY_APK_RELATIVE_PATH
            );
        }

        if (
            in_array(
                $validated['test_mode'],
                ['dummy_app', 'real_apk'],
                true
            )
            && (
                $apkPath === ''
                || !is_file($apkPath)
            )
        ) {
            return back()
                ->withErrors([
                    'apk_path' => (
                        'APK file was not found: '
                        . $apkPath
                    ),
                ])
                ->withInput();
        }

        $packageName = trim(
            (string) (
                $validated['app_package'] ?? ''
            )
        );

        $activityName = trim(
            (string) (
                $validated['app_activity'] ?? ''
            )
        );

        if (
            $validated['test_mode'] === 'dummy_app'
        ) {
            $packageName = 'com.gagent.dummyandroid';
            $activityName = (
                'com.gagent.dummyandroid.MainActivity'
            );
        }

        $testRun = TestRun::create([
            'project_id' => (
                $validated['project_id']
            ),

            'run_code' => (
                'ANDROID-'
                . now()->format('Ymd-His')
                . '-'
                . Str::upper(Str::random(5))
            ),

            'flow_type' => (
                $validated['flow_type']
            ),

            'scenario_type' => (
                'android_'
                . $validated['test_mode']
            ),

            'viewport_type' => 'mobile',
            'platform' => 'android',
            'target_type' => 'android_application',

            'run_mode' => 'android_appium_auto',
            'test_mode' => (
                $validated['test_mode']
            ),

            'target_app_package' => (
                $packageName ?: null
            ),

            'target_app_activity' => (
                $activityName ?: null
            ),

            'apk_path' => (
                $apkPath ?: null
            ),

            'device_name' => (
                $validated['device_name']
            ),

            'max_duration_seconds' => (
                $validated['max_duration_seconds']
            ),

            'automation_driver' => 'appium',
            'status' => 'pending',

            'notes' => (
                $validated['notes']
                ?? (
                    'Generic Android Appium test. '
                    . 'No authentication or security bypass is performed.'
                )
            ),
        ]);

        return redirect()
            ->route(
                'android-tests.show',
                $testRun
            )
            ->with(
                'success',
                'Android test run created. Press Run Android Appium Test.'
            );
    }

    public function show(TestRun $testRun)
    {
        abort_unless(
            $testRun->platform === 'android',
            404
        );

        $testRun->load([
            'project',
            'uxMetric',
            'androidResult',
        ]);

        return view(
            'android-tests.show',
            compact('testRun')
        );
    }

    public function run(
        TestRun $testRun,
        AppiumAndroidTestService $appiumService,
        GAgentAIService $aiService
    ) {
        abort_unless(
            $testRun->platform === 'android',
            404
        );

        if ($testRun->status === 'running') {
            return redirect()
                ->route(
                    'android-tests.show',
                    $testRun
                )
                ->with(
                    'error',
                    'This Android test is already running.'
                );
        }

        $startedAt = Carbon::now();

        $testRun->update([
            'status' => 'running',
            'started_at' => $startedAt,
            'completed_at' => null,
            'error_message' => null,
        ]);

        try {
            $appiumResult = $appiumService->run([
                'test_run_id' => $testRun->id,
                'test_mode' => $testRun->test_mode,
                'apk_path' => $testRun->apk_path,
                'target_app_package' => (
                    $testRun->target_app_package
                ),
                'target_app_activity' => (
                    $testRun->target_app_activity
                ),
                'device_name' => (
                    $testRun->device_name
                ),
                'flow_type' => (
                    $testRun->flow_type
                ),
                'max_duration_seconds' => (
                    $testRun->max_duration_seconds
                    ?? 60
                ),
            ]);

            if (
                ($appiumResult['status'] ?? null)
                !== 'success'
            ) {
                $message = (
                    $appiumResult['message']
                    ?? 'Unknown Appium error.'
                );

                $testRun->update([
                    'status' => 'failed',
                    'completed_at' => Carbon::now(),
                    'appium_exit_code' => (
                        $appiumResult['exit_code']
                    ),
                    'error_message' => $message,
                ]);

                return redirect()
                    ->route(
                        'android-tests.show',
                        $testRun
                    )
                    ->with(
                        'error',
                        'Android Appium test failed: '
                        . $message
                    );
            }

            $metrics = $this->normaliseMetrics(
                $appiumResult['metrics'] ?? [],
                $testRun->flow_type
            );

            $uxMetric = DB::transaction(
                function () use (
                    $testRun,
                    $metrics,
                    $appiumResult
                ) {
                    $uxMetric = UXMetric::updateOrCreate(
                        [
                            'test_run_id' => (
                                $testRun->id
                            ),
                        ],
                        [
                            'flow_type' => (
                                $metrics['flow_type']
                            ),

                            'scenario_type' => (
                                'android_'
                                . $testRun->test_mode
                            ),

                            'viewport_type' => 'mobile',
                            'device_type' => (
                                $metrics['device_type']
                            ),

                            'platform_name' => (
                                $metrics['platform_name']
                            ),

                            'task_completed' => (
                                $metrics['task_completed']
                            ),

                            'task_failed' => (
                                $metrics['task_failed']
                            ),

                            'completion_time' => (
                                $metrics['completion_time']
                            ),

                            'click_count' => (
                                $metrics['click_count']
                            ),

                            'scroll_count' => (
                                $metrics['scroll_count']
                            ),

                            'keyboard_count' => (
                                $metrics['keyboard_count']
                            ),

                            'retry_count' => (
                                $metrics['retry_count']
                            ),

                            'error_count' => (
                                $metrics['error_count']
                            ),

                            'failed_clicks' => (
                                $metrics['failed_clicks']
                            ),

                            'unnecessary_clicks' => (
                                $metrics['unnecessary_clicks']
                            ),

                            'path_deviation_score' => (
                                $metrics['path_deviation_score']
                            ),

                            'app_launch_time_ms' => (
                                $metrics['app_launch_time_ms']
                            ),

                            'screen_load_time_ms' => (
                                $metrics['screen_load_time_ms']
                            ),

                            'feedback_delay_ms' => (
                                $metrics['feedback_delay_ms']
                            ),

                            'interaction_response_time_ms' => (
                                $metrics[
                                    'interaction_response_time_ms'
                                ]
                            ),

                            'finish_time_ms' => (
                                $metrics['finish_time_ms']
                            ),

                            'error_message_present' => (
                                $metrics[
                                    'error_message_present'
                                ]
                            ),

                            'error_message_clarity' => (
                                $metrics[
                                    'error_message_clarity'
                                ]
                            ),

                            'popup_detected' => (
                                $metrics['popup_detected']
                            ),

                            'overlay_blocks_action' => (
                                $metrics[
                                    'overlay_blocks_action'
                                ]
                            ),

                            'timeout_occurred' => (
                                $metrics['timeout_occurred']
                            ),

                            'crash_detected' => (
                                $metrics['crash_detected']
                            ),

                            'anr_detected' => (
                                $metrics['anr_detected']
                            ),
                        ]
                    );

                    $testRun->update([
                        'appium_exit_code' => (
                            $appiumResult['exit_code']
                        ),
                    ]);

                    return $uxMetric;
                }
            );

            $payload = $uxMetric->toAndroidPayload();

            $prediction = $aiService->predictAndroid(
                $payload
            );

            if (
                ($prediction['status'] ?? null)
                !== 'success'
            ) {
                $message = (
                    $prediction['message']
                    ?? 'Unknown FastAPI error.'
                );

                $testRun->update([
                    'status' => 'failed',
                    'completed_at' => Carbon::now(),
                    'error_message' => (
                        'Appium completed, but FastAPI failed: '
                        . $message
                    ),
                ]);

                return redirect()
                    ->route(
                        'android-tests.show',
                        $testRun
                    )
                    ->with(
                        'error',
                        'Appium collected metrics, but Android prediction failed: '
                        . $message
                    );
            }

            $predictionData = (
                $prediction['data'] ?? []
            );

            DB::transaction(
                function () use (
                    $testRun,
                    $payload,
                    $predictionData,
                    $startedAt
                ) {
                    FrictionResult::where(
                        'test_run_id',
                        $testRun->id
                    )
                        ->where(
                            'prediction_source',
                            'android_appium'
                        )
                        ->delete();

                    FrictionResult::create([
                        'test_run_id' => (
                            $testRun->id
                        ),

                        'model_name' => (
                            'android_appium_model'
                        ),

                        'model_type' => (
                            $predictionData['model_type']
                            ?? 'android_appium'
                        ),

                        'prediction_source' => (
                            'android_appium'
                        ),

                        'friction_level' => (
                            $predictionData[
                                'friction_level'
                            ]
                            ?? $predictionData[
                                'prediction'
                            ]
                            ?? null
                        ),

                        'confidence_score' => (
                            $predictionData[
                                'confidence_score'
                            ]
                            ?? null
                        ),

                        'class_probabilities' => (
                            $predictionData[
                                'class_probabilities'
                            ]
                            ?? []
                        ),

                        'recommendations' => (
                            $predictionData[
                                'recommendations'
                            ]
                            ?? []
                        ),

                        'input_features' => $payload,
                        'is_final' => true,
                    ]);

                    $completedAt = Carbon::now();

                    $testRun->update([
                        'status' => 'completed',
                        'completed_at' => (
                            $completedAt
                        ),
                        'duration_seconds' => (
                            $startedAt->diffInMilliseconds(
                                $completedAt
                            ) / 1000
                        ),
                        'error_message' => null,
                    ]);
                }
            );

            return redirect()
                ->route(
                    'android-tests.show',
                    $testRun
                )
                ->with(
                    'success',
                    'Android Appium test and AI prediction completed successfully.'
                );

        } catch (Throwable $error) {
            $testRun->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'error_message' => (
                    $error->getMessage()
                ),
            ]);

            return redirect()
                ->route(
                    'android-tests.show',
                    $testRun
                )
                ->with(
                    'error',
                    'Android test failed: '
                    . $error->getMessage()
                );
        }
    }

    private function normaliseMetrics(
        array $metrics,
        string $flowType
    ): array {
        return [
            'flow_type' => (
                (string) (
                    $metrics['flow_type']
                    ?? $flowType
                )
            ),

            'device_type' => (
                (string) (
                    $metrics['device_type']
                    ?? 'phone'
                )
            ),

            'platform_name' => (
                (string) (
                    $metrics['platform_name']
                    ?? 'Android'
                )
            ),

            'task_completed' => (
                (int) (
                    $metrics['task_completed']
                    ?? 0
                )
            ),

            'task_failed' => (
                (int) (
                    $metrics['task_failed']
                    ?? 1
                )
            ),

            'completion_time' => (
                (float) (
                    $metrics['completion_time']
                    ?? 0
                )
            ),

            'click_count' => (
                (int) (
                    $metrics['click_count']
                    ?? 0
                )
            ),

            'scroll_count' => (
                (int) (
                    $metrics['scroll_count']
                    ?? 0
                )
            ),

            'keyboard_count' => (
                (int) (
                    $metrics['keyboard_count']
                    ?? 0
                )
            ),

            'retry_count' => (
                (int) (
                    $metrics['retry_count']
                    ?? 0
                )
            ),

            'error_count' => (
                (int) (
                    $metrics['error_count']
                    ?? 0
                )
            ),

            'failed_clicks' => (
                (int) (
                    $metrics['failed_clicks']
                    ?? 0
                )
            ),

            'unnecessary_clicks' => (
                (int) (
                    $metrics['unnecessary_clicks']
                    ?? 0
                )
            ),

            'path_deviation_score' => (
                (float) (
                    $metrics['path_deviation_score']
                    ?? 0
                )
            ),

            'app_launch_time_ms' => (
                (float) (
                    $metrics['app_launch_time_ms']
                    ?? 0
                )
            ),

            'screen_load_time_ms' => (
                (float) (
                    $metrics['screen_load_time_ms']
                    ?? 0
                )
            ),

            'feedback_delay_ms' => (
                (float) (
                    $metrics['feedback_delay_ms']
                    ?? 0
                )
            ),

            'interaction_response_time_ms' => (
                (float) (
                    $metrics[
                        'interaction_response_time_ms'
                    ]
                    ?? 0
                )
            ),

            'finish_time_ms' => (
                (float) (
                    $metrics['finish_time_ms']
                    ?? 0
                )
            ),

            'error_message_present' => (
                (int) (
                    $metrics[
                        'error_message_present'
                    ]
                    ?? 0
                )
            ),

            'error_message_clarity' => (
                (int) (
                    $metrics[
                        'error_message_clarity'
                    ]
                    ?? -1
                )
            ),

            'popup_detected' => (
                (int) (
                    $metrics['popup_detected']
                    ?? 0
                )
            ),

            'overlay_blocks_action' => (
                (int) (
                    $metrics[
                        'overlay_blocks_action'
                    ]
                    ?? 0
                )
            ),

            'timeout_occurred' => (
                (int) (
                    $metrics['timeout_occurred']
                    ?? 0
                )
            ),

            'crash_detected' => (
                (int) (
                    $metrics['crash_detected']
                    ?? 0
                )
            ),

            'anr_detected' => (
                (int) (
                    $metrics['anr_detected']
                    ?? 0
                )
            ),
        ];
    }
}
