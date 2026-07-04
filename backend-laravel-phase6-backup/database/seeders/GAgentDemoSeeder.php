<?php

namespace Database\Seeders;

use App\Models\FrictionResult;
use App\Models\InteractionLog;
use App\Models\Project;
use App\Models\Report;
use App\Models\Screenshot;
use App\Models\TestRun;
use App\Models\UXMetric;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GAgentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::firstOrCreate(
            ['project_name' => 'GAgent Demo Website'],
            [
                'platform_type' => 'Web',
                'website_url' => 'http://127.0.0.1:3000',
                'status' => 'Active',
            ]
        );

        $demoRuns = [
            [
                'run_code' => 'DEMO-LOW-001',
                'flow_type' => 'signup_good',
                'page_url' => 'http://127.0.0.1:3000/saas/signup-good',
                'status' => 'Completed',
                'metrics' => [
                    'completion_time' => 1200,
                    'click_count' => 3,
                    'scroll_count' => 0,
                    'keyboard_count' => 2,
                    'retry_count' => 0,
                    'error_count' => 0,
                    'failed_clicks' => 0,
                    'feedback_delay' => 300,
                    'task_completed' => 1,
                    'screenshot_count' => 2,
                    'error_message_clarity' => 2,
                ],
                'result' => [
                    'friction_level' => 'Low',
                    'confidence_score' => 0.96,
                    'model_used' => 'RandomForestClassifier',
                    'recommendation' => [
                        'No major UX friction indicator detected from the submitted metrics.',
                    ],
                ],
            ],
            [
                'run_code' => 'DEMO-MEDIUM-001',
                'flow_type' => 'signup_medium',
                'page_url' => 'http://127.0.0.1:3000/saas/signup-medium',
                'status' => 'Completed',
                'metrics' => [
                    'completion_time' => 4200,
                    'click_count' => 7,
                    'scroll_count' => 2,
                    'keyboard_count' => 4,
                    'retry_count' => 1,
                    'error_count' => 1,
                    'failed_clicks' => 0,
                    'feedback_delay' => 1400,
                    'task_completed' => 1,
                    'screenshot_count' => 3,
                    'error_message_clarity' => 1,
                ],
                'result' => [
                    'friction_level' => 'Medium',
                    'confidence_score' => 0.88,
                    'model_used' => 'RandomForestClassifier',
                    'recommendation' => [
                        'High feedback delay detected. Improve response time or add a loading indicator.',
                        'Errors detected. Improve form validation and error handling.',
                    ],
                ],
            ],
            [
                'run_code' => 'DEMO-HIGH-001',
                'flow_type' => 'signup_bad',
                'page_url' => 'http://127.0.0.1:3000/saas/signup-bad',
                'status' => 'Completed',
                'metrics' => [
                    'completion_time' => 15000,
                    'click_count' => 15,
                    'scroll_count' => 8,
                    'keyboard_count' => 6,
                    'retry_count' => 4,
                    'error_count' => 3,
                    'failed_clicks' => 2,
                    'feedback_delay' => 3200,
                    'task_completed' => 0,
                    'screenshot_count' => 4,
                    'error_message_clarity' => 0,
                ],
                'result' => [
                    'friction_level' => 'High',
                    'confidence_score' => 0.93,
                    'model_used' => 'RandomForestClassifier',
                    'recommendation' => [
                        'Task failure detected. Review the user flow.',
                        'High retry count detected. Review button labels or form instructions.',
                        'Failed clicks detected. Check whether buttons are visible, clickable, and correctly positioned.',
                        'Low error message clarity detected. Improve error message wording and guidance.',
                    ],
                ],
            ],
        ];

        foreach ($demoRuns as $demoRun) {
            $testRun = TestRun::updateOrCreate(
                ['run_code' => $demoRun['run_code']],
                [
                    'project_id' => $project->id,
                    'flow_type' => $demoRun['flow_type'],
                    'page_url' => $demoRun['page_url'],
                    'status' => $demoRun['status'],
                    'started_at' => Carbon::now()->subMinutes(20),
                    'completed_at' => Carbon::now()->subMinutes(15),
                    'notes' => 'Demo test run for Phase 6 dashboard verification.',
                ]
            );

            UXMetric::updateOrCreate(
                ['test_run_id' => $testRun->id],
                $demoRun['metrics']
            );

            FrictionResult::updateOrCreate(
                ['test_run_id' => $testRun->id],
                [
                    'friction_level' => $demoRun['result']['friction_level'],
                    'confidence_score' => $demoRun['result']['confidence_score'],
                    'model_used' => $demoRun['result']['model_used'],
                    'class_probabilities' => [
                        'Low' => $demoRun['result']['friction_level'] === 'Low' ? 0.96 : 0.03,
                        'Medium' => $demoRun['result']['friction_level'] === 'Medium' ? 0.88 : 0.04,
                        'High' => $demoRun['result']['friction_level'] === 'High' ? 0.93 : 0.01,
                    ],
                    'recommendation' => $demoRun['result']['recommendation'],
                    'predicted_at' => Carbon::now(),
                ]
            );

            Report::updateOrCreate(
                ['test_run_id' => $testRun->id],
                [
                    'title' => 'UX Friction Report - ' . $demoRun['flow_type'],
                    'summary' => 'Demo AI-assisted UX friction report generated for Phase 6.',
                    'conclusion' => 'The test run shows ' . $demoRun['result']['friction_level'] . ' UX friction based on collected interaction metrics.',
                    'generated_at' => Carbon::now(),
                ]
            );

            InteractionLog::firstOrCreate(
                [
                    'test_run_id' => $testRun->id,
                    'event_type' => 'page_load',
                    'event_label' => 'Page loaded',
                ],
                [
                    'event_value' => $demoRun['page_url'],
                    'event_time' => 0,
                    'metadata' => ['source' => 'demo_seeder'],
                ]
            );

            InteractionLog::firstOrCreate(
                [
                    'test_run_id' => $testRun->id,
                    'event_type' => 'click',
                    'event_label' => 'Submit button clicked',
                ],
                [
                    'event_value' => 'submit',
                    'event_time' => 1200,
                    'metadata' => ['source' => 'demo_seeder'],
                ]
            );

            Screenshot::firstOrCreate(
                [
                    'test_run_id' => $testRun->id,
                    'file_path' => 'https://placehold.co/800x450?text=' . urlencode($demoRun['flow_type']),
                ],
                [
                    'label' => 'Demo screenshot evidence',
                    'captured_at' => Carbon::now(),
                ]
            );
        }
    }
}
