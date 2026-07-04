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
use Illuminate\Support\Str;

class GAgentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::create([
            'name' => 'GAgent Dummy Website Demo',
            'description' => 'Demo project using stored sample UX metrics for Phase 6 dashboard testing.',
            'target_type' => 'dummy_website',
            'target_url' => 'http://127.0.0.1:3000',
            'status' => 'active',
        ]);

        $demoRuns = [
            [
                'run_code' => 'DEMO-LOW-' . Str::upper(Str::random(5)),
                'flow_type' => 'signup',
                'scenario_type' => 'good',
                'viewport_type' => 'desktop',
                'page_url' => '/saas/signup-good',
                'friction_level' => 'Low',
                'confidence_score' => 0.94,
                'metrics' => [
                    'task_completed' => true,
                    'task_failed' => false,
                    'completion_time' => 3.8,
                    'click_count' => 4,
                    'scroll_count' => 1,
                    'keyboard_count' => 3,
                    'retry_count' => 0,
                    'error_count' => 0,
                    'failed_clicks' => 0,
                    'unnecessary_clicks' => 0,
                    'path_deviation_score' => 0.05,
                    'page_load_time_ms' => 850,
                    'dom_content_loaded_ms' => 620,
                    'time_to_first_byte_ms' => 140,
                    'feedback_delay_ms' => 110,
                    'interaction_to_next_paint_ms' => 80,
                    'cumulative_layout_shift' => 0.01,
                    'error_message_present' => false,
                    'error_message_clarity' => 2,
                    'popup_detected' => false,
                    'cookie_banner_detected' => false,
                    'overlay_blocks_cta' => false,
                ],
            ],
            [
                'run_code' => 'DEMO-MED-' . Str::upper(Str::random(5)),
                'flow_type' => 'search',
                'scenario_type' => 'medium',
                'viewport_type' => 'tablet',
                'page_url' => '/ecommerce/search-medium',
                'friction_level' => 'Medium',
                'confidence_score' => 0.86,
                'metrics' => [
                    'task_completed' => true,
                    'task_failed' => false,
                    'completion_time' => 9.7,
                    'click_count' => 9,
                    'scroll_count' => 4,
                    'keyboard_count' => 5,
                    'retry_count' => 2,
                    'error_count' => 1,
                    'failed_clicks' => 2,
                    'unnecessary_clicks' => 3,
                    'path_deviation_score' => 0.42,
                    'page_load_time_ms' => 2300,
                    'dom_content_loaded_ms' => 1600,
                    'time_to_first_byte_ms' => 420,
                    'feedback_delay_ms' => 850,
                    'interaction_to_next_paint_ms' => 420,
                    'cumulative_layout_shift' => 0.12,
                    'error_message_present' => true,
                    'error_message_clarity' => 2,
                    'popup_detected' => true,
                    'cookie_banner_detected' => false,
                    'overlay_blocks_cta' => false,
                ],
            ],
            [
                'run_code' => 'DEMO-HIGH-' . Str::upper(Str::random(5)),
                'flow_type' => 'login',
                'scenario_type' => 'bad',
                'viewport_type' => 'mobile',
                'page_url' => '/banking/login-bad',
                'friction_level' => 'High',
                'confidence_score' => 0.91,
                'metrics' => [
                    'task_completed' => false,
                    'task_failed' => true,
                    'completion_time' => 18.9,
                    'click_count' => 17,
                    'scroll_count' => 8,
                    'keyboard_count' => 7,
                    'retry_count' => 5,
                    'error_count' => 4,
                    'failed_clicks' => 6,
                    'unnecessary_clicks' => 8,
                    'path_deviation_score' => 0.88,
                    'page_load_time_ms' => 5200,
                    'dom_content_loaded_ms' => 3900,
                    'time_to_first_byte_ms' => 1200,
                    'feedback_delay_ms' => 2600,
                    'interaction_to_next_paint_ms' => 1250,
                    'cumulative_layout_shift' => 0.35,
                    'error_message_present' => true,
                    'error_message_clarity' => 1,
                    'popup_detected' => true,
                    'cookie_banner_detected' => true,
                    'overlay_blocks_cta' => true,
                ],
            ],
        ];

        foreach ($demoRuns as $demoRun) {
            $testRun = TestRun::create([
                'project_id' => $project->id,
                'run_code' => $demoRun['run_code'],
                'flow_type' => $demoRun['flow_type'],
                'scenario_type' => $demoRun['scenario_type'],
                'viewport_type' => $demoRun['viewport_type'],
                'page_url' => $demoRun['page_url'],
                'status' => 'completed',
                'started_at' => Carbon::now()->subMinutes(20),
                'completed_at' => Carbon::now()->subMinutes(10),
                'notes' => 'Seeder-generated Phase 6 demo test run.',
            ]);

            UXMetric::create(array_merge(
                [
                    'test_run_id' => $testRun->id,
                    'flow_type' => $demoRun['flow_type'],
                    'scenario_type' => $demoRun['scenario_type'],
                    'viewport_type' => $demoRun['viewport_type'],
                ],
                $demoRun['metrics']
            ));

            $payload = UXMetric::where('test_run_id', $testRun->id)->first()->toGAgentPayload();

            FrictionResult::create([
                'test_run_id' => $testRun->id,
                'model_name' => 'main_gagent_model',
                'model_type' => 'Decision Tree',
                'prediction_source' => 'main_gagent',
                'friction_level' => $demoRun['friction_level'],
                'confidence_score' => $demoRun['confidence_score'],
                'class_probabilities' => [
                    'Low' => $demoRun['friction_level'] === 'Low' ? 0.94 : 0.04,
                    'Medium' => $demoRun['friction_level'] === 'Medium' ? 0.86 : 0.05,
                    'High' => $demoRun['friction_level'] === 'High' ? 0.91 : 0.03,
                ],
                'recommendations' => $this->recommendationsFor($demoRun['friction_level']),
                'input_features' => $payload,
                'is_final' => true,
            ]);

            FrictionResult::create([
                'test_run_id' => $testRun->id,
                'model_name' => 'baseline_model',
                'model_type' => 'Baseline Comparison',
                'prediction_source' => 'baseline',
                'friction_level' => $demoRun['friction_level'],
                'confidence_score' => max(0.60, $demoRun['confidence_score'] - 0.12),
                'class_probabilities' => [
                    'Low' => $demoRun['friction_level'] === 'Low' ? 0.78 : 0.12,
                    'Medium' => $demoRun['friction_level'] === 'Medium' ? 0.74 : 0.13,
                    'High' => $demoRun['friction_level'] === 'High' ? 0.76 : 0.10,
                ],
                'recommendations' => [
                    'Baseline result is shown only for comparison.',
                    'Use the main GAgent model as the final system decision.',
                ],
                'input_features' => UXMetric::where('test_run_id', $testRun->id)->first()->toBaselinePayload(),
                'is_final' => false,
            ]);

            InteractionLog::create([
                'test_run_id' => $testRun->id,
                'event_type' => 'click',
                'event_label' => 'primary_cta',
                'event_value' => 'submit',
                'event_time' => 1.2,
                'metadata' => ['source' => 'demo_seeder'],
            ]);

            InteractionLog::create([
                'test_run_id' => $testRun->id,
                'event_type' => 'navigation',
                'event_label' => $demoRun['page_url'],
                'event_value' => $demoRun['scenario_type'],
                'event_time' => 2.4,
                'metadata' => ['viewport' => $demoRun['viewport_type']],
            ]);

            Screenshot::create([
                'test_run_id' => $testRun->id,
                'file_path' => 'https://placehold.co/800x450?text=' . urlencode($demoRun['flow_type'] . ' ' . $demoRun['friction_level']),
                'label' => 'Demo screenshot evidence',
                'captured_at' => Carbon::now(),
            ]);

            Report::create([
                'test_run_id' => $testRun->id,
                'title' => 'UX Friction Report - ' . $testRun->run_code,
                'summary' => 'Seeder-generated report for ' . $demoRun['friction_level'] . ' friction scenario.',
                'conclusion' => 'Main GAgent prediction is treated as the final result. Baseline prediction is stored for comparison only.',
                'generated_at' => Carbon::now(),
            ]);
        }
    }

    private function recommendationsFor(string $level): array
    {
        return match ($level) {
            'Low' => [
                'Maintain current UX flow because task completion is smooth.',
                'Continue monitoring performance and interaction delay.',
            ],
            'Medium' => [
                'Reduce unnecessary clicks and retries in this flow.',
                'Improve feedback speed after user interaction.',
                'Review popup or layout behavior that may interrupt the task.',
            ],
            'High' => [
                'Fix blocking overlays, popup interruptions, and cookie banner placement.',
                'Reduce page load delay and interaction response time.',
                'Improve error message clarity and prevent repeated failed clicks.',
                'Review the navigation path because users are likely deviating from the expected flow.',
            ],
            default => [
                'Review UX metrics manually.',
            ],
        };
    }
}
