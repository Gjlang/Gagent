<?php

namespace App\Http\Controllers;

use App\Services\GAgentAIService;
use Illuminate\Http\JsonResponse;

class AIServiceTestController extends Controller
{
    public function __invoke(GAgentAIService $aiService): JsonResponse
    {
        $sampleMetrics = [
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
        ];

        $healthResult = $aiService->health();
        $predictionResult = $aiService->predict($sampleMetrics);

        return response()->json([
            'phase' => 'Phase 5 Laravel to FastAPI Test',
            'fastapi_health' => $healthResult,
            'sample_metrics_sent' => $sampleMetrics,
            'prediction_result' => $predictionResult,
        ]);
    }
}
