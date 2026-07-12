<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GAgentAIService
{
    private string $baseUrl;

    private array $gagentFeatureKeys = [
        'flow_type',
        'viewport_type',
        'task_completed',
        'task_failed',
        'completion_time',
        'click_count',
        'scroll_count',
        'keyboard_count',
        'retry_count',
        'error_count',
        'failed_clicks',
        'unnecessary_clicks',
        'path_deviation_score',
        'page_load_time_ms',
        'dom_content_loaded_ms',
        'time_to_first_byte_ms',
        'feedback_delay_ms',
        'interaction_to_next_paint_ms',
        'cumulative_layout_shift',
        'error_message_present',
        'error_message_clarity',
        'popup_detected',
        'cookie_banner_detected',
        'overlay_blocks_cta',
    ];

  private array $baselineFeatureKeys = [
    'completion_time',
    'click_count',
    'scroll_count',
    'keyboard_count',
    'retry_count',
    'error_count',
    'failed_clicks',
    'task_completed',
];

private array $androidFeatureKeys = [
    'flow_type',
    'device_type',
    'platform_name',
    'task_completed',
    'task_failed',
    'completion_time',
    'click_count',
    'scroll_count',
    'keyboard_count',
    'retry_count',
    'error_count',
    'failed_clicks',
    'unnecessary_clicks',
    'path_deviation_score',
    'app_launch_time_ms',
    'screen_load_time_ms',
    'feedback_delay_ms',
    'interaction_response_time_ms',
    'finish_time_ms',
    'error_message_present',
    'error_message_clarity',
    'popup_detected',
    'overlay_blocks_action',
    'timeout_occurred',
    'crash_detected',
    'anr_detected',
];

    public function __construct()
    {
        $this->baseUrl = rtrim(env('GAGENT_AI_SERVICE_URL', 'http://127.0.0.1:8001'), '/');
    }

    public function health(): array
    {
        return $this->get('/health', 5);
    }

    public function modelInfo(): array
    {
        return $this->get('/model-info', 5);
    }

    public function predictGAgent(array $features): array
    {
        $payload = Arr::only($features, $this->gagentFeatureKeys);

        return $this->post('/predict-gagent', $payload);
    }

    public function predictBaseline(array $features): array
    {
        $payload = Arr::only($features, $this->baselineFeatureKeys);

        return $this->post('/predict-baseline', $payload);
    }
public function predictAndroid(array $features): array
{
    $payload = Arr::only(
        $features,
        $this->androidFeatureKeys
    );

    Log::info('Android prediction payload', [
        'endpoint' => '/predict-android',
        'payload' => $payload,
    ]);

    return $this->post(
        '/predict-android',
        $payload
    );
}
    public function batchPredictGAgent(array $items): array
    {
        $payload = [
            'items' => collect($items)
                ->map(fn ($item) => Arr::only($item, $this->gagentFeatureKeys))
                ->values()
                ->all(),
        ];

        return $this->post('/batch-predict-gagent', $payload, 30);
    }

    public function batchPredictBaseline(array $items): array
    {
        $payload = [
            'items' => collect($items)
                ->map(fn ($item) => Arr::only($item, $this->baselineFeatureKeys))
                ->values()
                ->all(),
        ];

        return $this->post('/batch-predict-baseline', $payload, 30);
    }

    private function get(string $endpoint, int $timeout = 10): array
    {
        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($this->baseUrl . $endpoint);

            return $this->formatResponse($response->successful(), $response->status(), $response->json());
        } catch (Throwable $error) {
            Log::error('GAgent FastAPI GET request failed', [
                'endpoint' => $endpoint,
                'error' => $error->getMessage(),
            ]);

            return $this->formatError('Laravel could not connect to the FastAPI AI service.', $error);
        }
    }

    private function post(string $endpoint, array $payload, int $timeout = 15): array
    {
        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . $endpoint, $payload);

            if (!$response->successful()) {
                Log::warning('GAgent FastAPI returned an error response', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'payload' => $payload,
                    'response' => $response->json(),
                ]);
            }

            return $this->formatResponse($response->successful(), $response->status(), $response->json(), $payload);
        } catch (Throwable $error) {
            Log::error('GAgent FastAPI POST request failed', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'error' => $error->getMessage(),
            ]);

            return $this->formatError('Laravel request to FastAPI failed.', $error, $payload);
        }
    }

   private function formatResponse(
    bool $successful,
    int $httpStatus,
    mixed $data,
    array $payload = []
): array {
    $message = 'Request completed successfully.';

    if (!$successful) {
        $message = 'FastAPI returned an error response.';

        if (is_array($data)) {
            if (isset($data['detail'])) {
                if (is_string($data['detail'])) {
                    $message .= ' ' . $data['detail'];
                } else {
                    $message .= ' ' . json_encode(
                        $data['detail'],
                        JSON_UNESCAPED_SLASHES
                    );
                }
            } elseif (isset($data['message'])) {
                $message .= ' ' . $data['message'];
            }
        }
    }

    return [
        'status' => $successful ? 'success' : 'error',
        'http_status' => $httpStatus,
        'data' => $data,
        'payload_sent' => $payload,
        'message' => $message,
    ];
}

    private function formatError(string $message, Throwable $error, array $payload = []): array
    {
        return [
            'status' => 'error',
            'http_status' => null,
            'data' => null,
            'payload_sent' => $payload,
            'message' => $message,
            'details' => $error->getMessage(),
        ];
    }
}
