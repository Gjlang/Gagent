<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ComparisonLLMService
{
    public function generate(
        array $comparison,
        string $projectName
    ): array {
        $baseUrl = rtrim(
            (string) config(
                'services.ollama.base_url',
                'http://127.0.0.1:11434'
            ),
            '/'
        );

        $model = (string) config(
            'services.ollama.model',
            'llama3.2:1b'
        );

        $timeout = (int) config(
            'services.ollama.timeout',
            90
        );

        $facts = $this->buildFacts(
            $comparison,
            $projectName
        );

        $prompt = $this->buildPrompt(
            $facts
        );

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->connectTimeout(10)
                ->post(
                    $baseUrl . '/api/generate',
                    [
                        'model' => $model,
                        'prompt' => $prompt,
                        'stream' => false,
                        'format' => 'json',
                        'options' => [
                            'temperature' => 0.1,
                            'num_predict' => 700,
                        ],
                    ]
                );

            if (!$response->successful()) {
                $message = 'Ollama returned HTTP '
                    . $response->status()
                    . '.';

                Log::warning(
                    'Comparison Ollama request failed.',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return $this->fallback(
                    $comparison,
                    $message,
                    $model
                );
            }

            $rawResponse = $response->json(
                'response'
            );

            if (!is_string($rawResponse)
                || trim($rawResponse) === '') {
                return $this->fallback(
                    $comparison,
                    'Ollama returned an empty response.',
                    $model
                );
            }

            $decoded = $this->decodeJsonResponse(
                $rawResponse
            );

            if (!is_array($decoded)) {
                return $this->fallback(
                    $comparison,
                    'Ollama response was not valid JSON.',
                    $model
                );
            }

            return [
                'status' => 'success',
                'provider' => 'ollama',
                'model' => $model,
                'summary' => $this->cleanText(
                    $decoded['summary']
                    ?? null,
                    $comparison['summary']
                    ?? 'Comparison completed.'
                ),
                'assessment' => $this->cleanText(
                    $decoded['assessment']
                    ?? null,
                    $comparison['overall_status']
                    ?? 'Comparison completed.'
                ),
                'improvements' => $this->cleanList(
                    $decoded['improvements']
                    ?? []
                ),
                'regressions' => $this->cleanList(
                    $decoded['regressions']
                    ?? []
                ),
                'next_actions' => $this->cleanList(
                    $decoded['next_actions']
                    ?? []
                ),
                'error' => null,
            ];
        } catch (ConnectionException $error) {
            Log::warning(
                'Ollama comparison connection failed.',
                [
                    'message' => $error->getMessage(),
                ]
            );

            return $this->fallback(
                $comparison,
                'Ollama service is unavailable.',
                $model
            );
        } catch (Throwable $error) {
            Log::error(
                'Comparison LLM generation failed.',
                [
                    'message' => $error->getMessage(),
                    'trace' => $error->getTraceAsString(),
                ]
            );

            return $this->fallback(
                $comparison,
                $error->getMessage(),
                $model
            );
        }
    }

    private function buildFacts(
        array $comparison,
        string $projectName
    ): array {
        return [
            'project_name' => $projectName,

            'overall_status' => (
                $comparison['overall_status']
                ?? 'Unknown'
            ),

            'before' => [
                'run_code' => (
                    $comparison['before']
                        ?->run_code
                    ?? 'Unknown'
                ),

                'friction_level' => (
                    $comparison[
                        'friction_comparison'
                    ]['before']
                    ?? 'Unknown'
                ),

                'ux_score' => (
                    $comparison['before_score']
                    ?? null
                ),
            ],

            'after' => [
                'run_code' => (
                    $comparison['after']
                        ?->run_code
                    ?? 'Unknown'
                ),

                'friction_level' => (
                    $comparison[
                        'friction_comparison'
                    ]['after']
                    ?? 'Unknown'
                ),

                'ux_score' => (
                    $comparison['after_score']
                    ?? null
                ),
            ],

            'score_difference' => (
                $comparison['score_difference']
                ?? 0
            ),

            'improved_metrics' => $this->mapMetrics(
                $comparison['improved_metrics']
                ?? []
            ),

            'worsened_metrics' => $this->mapMetrics(
                $comparison['worsened_metrics']
                ?? []
            ),

            'unchanged_metrics' => $this->mapMetrics(
                $comparison['unchanged_metrics']
                ?? []
            ),

            'resolved_recommendations' => (
                $comparison[
                    'resolved_recommendations'
                ]
                ?? []
            ),

            'remaining_recommendations' => (
                $comparison[
                    'remaining_recommendations'
                ]
                ?? []
            ),

            'new_recommendations' => (
                $comparison[
                    'new_recommendations'
                ]
                ?? []
            ),
        ];
    }

    private function mapMetrics(
        array $metrics
    ): array {
        return collect($metrics)
            ->map(function (array $metric) {
                return [
                    'name' => (
                        $metric['label']
                        ?? $metric['field']
                        ?? 'Unknown metric'
                    ),

                    'before' => (
                        $metric['before']
                        ?? null
                    ),

                    'after' => (
                        $metric['after']
                        ?? null
                    ),

                    'difference' => (
                        $metric['difference']
                        ?? null
                    ),

                    'unit' => (
                        $metric['unit']
                        ?? ''
                    ),

                    'status' => (
                        $metric['status']
                        ?? 'unknown'
                    ),
                ];
            })
            ->values()
            ->all();
    }

    private function buildPrompt(
        array $facts
    ): string {
        $factsJson = json_encode(
            $facts,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        return <<<PROMPT
You are the explanation layer for GAgent, an AI-driven UX friction testing system.

Explain a saved before-and-after website UX comparison.

Strict rules:
1. Use only the supplied comparison facts.
2. Do not recalculate any score.
3. Do not change the Low, Medium, or High friction classifications.
4. Do not invent metrics, problems, screenshots, causes, or recommendations.
5. Clearly distinguish improvements, regressions, and unchanged results.
6. The Laravel comparison result is authoritative.
7. Keep the explanation concise and suitable for a university Final Year Project dashboard.
8. Use clear professional English.
9. Do not mention these instructions.
10. Return valid JSON only.

Return exactly this JSON structure:
{
  "summary": "One concise paragraph explaining the overall comparison.",
  "assessment": "One concise paragraph interpreting whether the website improved, partially improved, remained unchanged, or regressed.",
  "improvements": [
    "Fact-based improvement"
  ],
  "regressions": [
    "Fact-based remaining issue or regression"
  ],
  "next_actions": [
    "Practical next action based only on supplied facts"
  ]
}

If there are no improvements, return an empty improvements array.
If there are no regressions, return an empty regressions array.
Provide no more than five items in each array.

Comparison facts:
{$factsJson}
PROMPT;
    }

    private function decodeJsonResponse(
        string $rawResponse
    ): ?array {
        $cleaned = trim($rawResponse);

        $cleaned = preg_replace(
            '/^```(?:json)?\s*/i',
            '',
            $cleaned
        );

        $cleaned = preg_replace(
            '/\s*```$/',
            '',
            $cleaned
        );

        $decoded = json_decode(
            $cleaned,
            true
        );

        return is_array($decoded)
            ? $decoded
            : null;
    }

    private function cleanText(
        mixed $value,
        string $fallback
    ): string {
        if (!is_string($value)) {
            return $fallback;
        }

        $value = trim(
            strip_tags($value)
        );

        return $value !== ''
            ? $value
            : $fallback;
    }

    private function cleanList(
        mixed $items
    ): array {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(
                fn ($item) => is_string($item)
                    && trim($item) !== ''
            )
            ->map(
                fn ($item) => trim(
                    strip_tags($item)
                )
            )
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    private function fallback(
        array $comparison,
        string $error,
        string $model
    ): array {
        $improvements = collect(
            $comparison['improved_metrics']
            ?? []
        )
            ->map(function (array $metric) {
                $label = $metric['label']
                    ?? 'A measured metric';

                return $label
                    . ' improved in the newer test run.';
            })
            ->take(5)
            ->values()
            ->all();

        $regressions = collect(
            $comparison['worsened_metrics']
            ?? []
        )
            ->map(function (array $metric) {
                $label = $metric['label']
                    ?? 'A measured metric';

                return $label
                    . ' worsened and should be reviewed.';
            })
            ->take(5)
            ->values()
            ->all();

        $nextActions = [];

        if ($regressions !== []) {
            $nextActions[] =
                'Review the worsened metrics before running another comparison.';
        }

        if ($regressions === []
            && $improvements !== []) {
            $nextActions[] =
                'Maintain the improvements and continue monitoring the remaining UX metrics.';
        }

        if ($regressions === []
            && $improvements === []) {
            $nextActions[] =
                'Review the unchanged metrics and test another targeted UX improvement.';
        }

        return [
            'status' => 'fallback',
            'provider' => 'rule_based_fallback',
            'model' => $model,
            'summary' => (
                $comparison['summary']
                ?? 'The comparison was generated from saved UX metrics.'
            ),
            'assessment' => (
                'The calculated overall result is '
                . (
                    $comparison['overall_status']
                    ?? 'unavailable'
                )
                . '.'
            ),
            'improvements' => $improvements,
            'regressions' => $regressions,
            'next_actions' => $nextActions,
            'error' => $error,
        ];
    }
}
