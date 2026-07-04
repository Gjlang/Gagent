<?php

namespace App\Http\Controllers;

use App\Services\GAgentAIService;

class AIServiceTestController extends Controller
{
    public function __invoke(GAgentAIService $aiService)
    {
        $sampleGAgentFeatures = [
            'flow_type' => 'signup',
            'viewport_type' => 'desktop',
            'task_completed' => 1,
            'task_failed' => 0,
            'completion_time' => 4.2,
            'click_count' => 5,
            'scroll_count' => 2,
            'keyboard_count' => 3,
            'retry_count' => 0,
            'error_count' => 0,
            'failed_clicks' => 0,
            'unnecessary_clicks' => 1,
            'path_deviation_score' => 0.1,
            'page_load_time_ms' => 900,
            'dom_content_loaded_ms' => 700,
            'time_to_first_byte_ms' => 180,
            'feedback_delay_ms' => 120,
            'interaction_to_next_paint_ms' => 90,
            'cumulative_layout_shift' => 0.02,
            'error_message_present' => 0,
            'error_message_clarity' => 2,
            'popup_detected' => 0,
            'cookie_banner_detected' => 0,
            'overlay_blocks_cta' => 0,
        ];

        $baselineFeatures = [
            'completion_time' => 4.2,
            'click_count' => 5,
            'scroll_count' => 2,
            'keyboard_count' => 3,
            'retry_count' => 0,
            'error_count' => 0,
            'failed_clicks' => 0,
            'task_completed' => 1,
        ];

        $health = $aiService->health();
        $modelInfo = $aiService->modelInfo();
        $mainPrediction = $aiService->predictGAgent($sampleGAgentFeatures);
        $baselinePrediction = $aiService->predictBaseline($baselineFeatures);

        return view('ai-service-test', compact(
            'health',
            'modelInfo',
            'mainPrediction',
            'baselinePrediction',
            'sampleGAgentFeatures',
            'baselineFeatures'
        ));
    }
}
