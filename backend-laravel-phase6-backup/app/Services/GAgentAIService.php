<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class GAgentAIService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('GAGENT_AI_SERVICE_URL', 'http://127.0.0.1:8001'), '/');
    }

    public function predict(array $metrics): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post($this->baseUrl . '/predict', $metrics);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'http_status' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => 'error',
                'http_status' => $response->status(),
                'message' => 'FastAPI returned an error response.',
                'details' => $response->json(),
            ];
        } catch (ConnectionException $error) {
            return [
                'status' => 'error',
                'http_status' => null,
                'message' => 'Laravel could not connect to the FastAPI AI service.',
                'details' => $error->getMessage(),
            ];
        } catch (RequestException $error) {
            return [
                'status' => 'error',
                'http_status' => null,
                'message' => 'Laravel request to FastAPI failed.',
                'details' => $error->getMessage(),
            ];
        } catch (\Throwable $error) {
            return [
                'status' => 'error',
                'http_status' => null,
                'message' => 'Unexpected Laravel AI service error.',
                'details' => $error->getMessage(),
            ];
        }
    }

    public function health(): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get($this->baseUrl . '/health');

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $error) {
            return [
                'status' => 'error',
                'http_status' => null,
                'message' => 'Could not reach FastAPI health endpoint.',
                'details' => $error->getMessage(),
            ];
        }
    }
}
