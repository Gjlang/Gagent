<?php

namespace App\Services;

use App\Models\TestRun;
use Illuminate\Support\Collection;

class TestRunComparisonService
{
    /**
     * Compare two test runs belonging to the same project.
     */
    public function compare(
        TestRun $before,
        TestRun $after
    ): array {
        $beforeMetric = $before->uxMetric;
        $afterMetric = $after->uxMetric;

        $beforeResult = $before->finalFrictionResult;
        $afterResult = $after->finalFrictionResult;

        $metricDefinitions = $this->metricDefinitions(
            $before->platform ?? 'web'
        );

        $metricComparisons = [];

        foreach ($metricDefinitions as $definition) {
            $field = $definition['field'];

            $beforeValue = $beforeMetric?->{$field};
            $afterValue = $afterMetric?->{$field};

            if (
                $beforeValue === null
                && $afterValue === null
            ) {
                continue;
            }

            $metricComparisons[] = $this->compareMetric(
                $definition,
                $beforeValue,
                $afterValue
            );
        }

        $beforeScore = $this->calculateDerivedUxScore(
            $before
        );

        $afterScore = $this->calculateDerivedUxScore(
            $after
        );

        $scoreDifference = $afterScore - $beforeScore;

        $beforeRecommendations = $this->normaliseRecommendations(
            $beforeResult?->recommendations
        );

        $afterRecommendations = $this->normaliseRecommendations(
            $afterResult?->recommendations
        );

        $resolvedRecommendations = array_values(
            array_diff(
                $beforeRecommendations,
                $afterRecommendations
            )
        );

        $newRecommendations = array_values(
            array_diff(
                $afterRecommendations,
                $beforeRecommendations
            )
        );

        $remainingRecommendations = array_values(
            array_intersect(
                $beforeRecommendations,
                $afterRecommendations
            )
        );

        $frictionComparison = $this->compareFrictionLevels(
            $beforeResult?->friction_level,
            $afterResult?->friction_level
        );

        $improvedMetrics = collect($metricComparisons)
            ->where('status', 'improved')
            ->values()
            ->all();

        $worsenedMetrics = collect($metricComparisons)
            ->where('status', 'worsened')
            ->values()
            ->all();

        $unchangedMetrics = collect($metricComparisons)
            ->where('status', 'unchanged')
            ->values()
            ->all();

        return [
            'before' => $before,
            'after' => $after,

            'before_result' => $beforeResult,
            'after_result' => $afterResult,

            'before_score' => $beforeScore,
            'after_score' => $afterScore,
            'score_difference' => $scoreDifference,

            'friction_comparison' => $frictionComparison,
            'metrics' => $metricComparisons,

            'improved_metrics' => $improvedMetrics,
            'worsened_metrics' => $worsenedMetrics,
            'unchanged_metrics' => $unchangedMetrics,

            'before_recommendations' => $beforeRecommendations,
            'after_recommendations' => $afterRecommendations,
            'resolved_recommendations' => $resolvedRecommendations,
            'new_recommendations' => $newRecommendations,
            'remaining_recommendations' => $remainingRecommendations,

            'screenshot_pairs' => $this->pairScreenshots(
                $before->screenshots,
                $after->screenshots
            ),

            'overall_status' => $this->determineOverallStatus(
                $frictionComparison['status'],
                $scoreDifference,
                count($improvedMetrics),
                count($worsenedMetrics)
            ),

            'summary' => $this->buildSummary(
                $before,
                $after,
                $frictionComparison,
                $beforeScore,
                $afterScore,
                $improvedMetrics,
                $worsenedMetrics
            ),
        ];
    }

    /**
     * Define which metrics are compared.
     *
     * "lower" means a smaller value is better.
     * "higher" means a larger value is better.
     * "false" means false/No is better.
     */
    private function metricDefinitions(
        string $platform
    ): array {
        $commonMetrics = [
            [
                'field' => 'task_completed',
                'label' => 'Task Completed',
                'type' => 'boolean',
                'better' => 'true',
                'unit' => '',
            ],
            [
                'field' => 'completion_time',
                'label' => 'Completion Time',
                'type' => 'number',
                'better' => 'lower',
                'unit' => 's',
            ],
            [
                'field' => 'click_count',
                'label' => 'Click Count',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'scroll_count',
                'label' => 'Scroll Count',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'retry_count',
                'label' => 'Retry Count',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'error_count',
                'label' => 'Error Count',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'failed_clicks',
                'label' => 'Failed Clicks',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'unnecessary_clicks',
                'label' => 'Unnecessary Clicks',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'path_deviation_score',
                'label' => 'Path Deviation',
                'type' => 'number',
                'better' => 'lower',
                'unit' => '',
            ],
            [
                'field' => 'feedback_delay_ms',
                'label' => 'Feedback Delay',
                'type' => 'number',
                'better' => 'lower',
                'unit' => 'ms',
            ],
            [
                'field' => 'error_message_clarity',
                'label' => 'Error Message Clarity',
                'type' => 'number',
                'better' => 'higher',
                'unit' => '',
            ],
            [
                'field' => 'popup_detected',
                'label' => 'Popup Detected',
                'type' => 'boolean',
                'better' => 'false',
                'unit' => '',
            ],
        ];

        if ($platform === 'android') {
            return array_merge(
                $commonMetrics,
                [
                    [
                        'field' => 'app_launch_time_ms',
                        'label' => 'App Launch Time',
                        'type' => 'number',
                        'better' => 'lower',
                        'unit' => 'ms',
                    ],
                    [
                        'field' => 'screen_load_time_ms',
                        'label' => 'Screen Load Time',
                        'type' => 'number',
                        'better' => 'lower',
                        'unit' => 'ms',
                    ],
                    [
                        'field' => 'interaction_response_time_ms',
                        'label' => 'Interaction Response Time',
                        'type' => 'number',
                        'better' => 'lower',
                        'unit' => 'ms',
                    ],
                    [
                        'field' => 'overlay_blocks_action',
                        'label' => 'Overlay Blocks Action',
                        'type' => 'boolean',
                        'better' => 'false',
                        'unit' => '',
                    ],
                    [
                        'field' => 'timeout_occurred',
                        'label' => 'Timeout Occurred',
                        'type' => 'boolean',
                        'better' => 'false',
                        'unit' => '',
                    ],
                    [
                        'field' => 'crash_detected',
                        'label' => 'Crash Detected',
                        'type' => 'boolean',
                        'better' => 'false',
                        'unit' => '',
                    ],
                    [
                        'field' => 'anr_detected',
                        'label' => 'ANR Detected',
                        'type' => 'boolean',
                        'better' => 'false',
                        'unit' => '',
                    ],
                ]
            );
        }

        return array_merge(
            $commonMetrics,
            [
                [
                    'field' => 'page_load_time_ms',
                    'label' => 'Page Load Time',
                    'type' => 'number',
                    'better' => 'lower',
                    'unit' => 'ms',
                ],
                [
                    'field' => 'dom_content_loaded_ms',
                    'label' => 'DOM Content Loaded',
                    'type' => 'number',
                    'better' => 'lower',
                    'unit' => 'ms',
                ],
                [
                    'field' => 'time_to_first_byte_ms',
                    'label' => 'Time to First Byte',
                    'type' => 'number',
                    'better' => 'lower',
                    'unit' => 'ms',
                ],
                [
                    'field' => 'interaction_to_next_paint_ms',
                    'label' => 'Interaction to Next Paint',
                    'type' => 'number',
                    'better' => 'lower',
                    'unit' => 'ms',
                ],
                [
                    'field' => 'cumulative_layout_shift',
                    'label' => 'Cumulative Layout Shift',
                    'type' => 'number',
                    'better' => 'lower',
                    'unit' => '',
                ],
                [
                    'field' => 'cookie_banner_detected',
                    'label' => 'Cookie Banner Detected',
                    'type' => 'boolean',
                    'better' => 'false',
                    'unit' => '',
                ],
                [
                    'field' => 'overlay_blocks_cta',
                    'label' => 'Overlay Blocks CTA',
                    'type' => 'boolean',
                    'better' => 'false',
                    'unit' => '',
                ],
            ]
        );
    }

    private function compareMetric(
        array $definition,
        mixed $beforeValue,
        mixed $afterValue
    ): array {
        if ($definition['type'] === 'boolean') {
            return $this->compareBooleanMetric(
                $definition,
                (bool) $beforeValue,
                (bool) $afterValue
            );
        }

        $beforeNumber = (float) $beforeValue;
        $afterNumber = (float) $afterValue;

        $difference = $afterNumber - $beforeNumber;

        if (abs($difference) < 0.0001) {
            $status = 'unchanged';
        } elseif ($definition['better'] === 'lower') {
            $status = $afterNumber < $beforeNumber
                ? 'improved'
                : 'worsened';
        } else {
            $status = $afterNumber > $beforeNumber
                ? 'improved'
                : 'worsened';
        }

        $percentageChange = null;

        if (abs($beforeNumber) > 0.0001) {
            $percentageChange = (
                ($afterNumber - $beforeNumber)
                / abs($beforeNumber)
            ) * 100;
        }

        return [
            'field' => $definition['field'],
            'label' => $definition['label'],
            'type' => $definition['type'],
            'unit' => $definition['unit'],
            'before' => $beforeNumber,
            'after' => $afterNumber,
            'difference' => $difference,
            'percentage_change' => $percentageChange,
            'status' => $status,
        ];
    }

    private function compareBooleanMetric(
        array $definition,
        bool $beforeValue,
        bool $afterValue
    ): array {
        if ($beforeValue === $afterValue) {
            $status = 'unchanged';
        } elseif ($definition['better'] === 'true') {
            $status = $afterValue
                ? 'improved'
                : 'worsened';
        } else {
            $status = !$afterValue
                ? 'improved'
                : 'worsened';
        }

        return [
            'field' => $definition['field'],
            'label' => $definition['label'],
            'type' => 'boolean',
            'unit' => '',
            'before' => $beforeValue,
            'after' => $afterValue,
            'difference' => null,
            'percentage_change' => null,
            'status' => $status,
        ];
    }

    /**
     * This is a derived display score, not the AI confidence score.
     *
     * The AI model and prediction are not modified.
     */
    private function calculateDerivedUxScore(
        TestRun $run
    ): int {
        $metric = $run->uxMetric;

        if (!$metric) {
            return 0;
        }

        $penalty = 0;

        if (!$metric->task_completed) {
            $penalty += 15;
        }

        if ($metric->task_failed) {
            $penalty += 20;
        }

        $penalty += min(
            20,
            ((int) $metric->error_count) * 5
        );

        $penalty += min(
            16,
            ((int) $metric->failed_clicks) * 4
        );

        $penalty += min(
            12,
            ((int) $metric->retry_count) * 3
        );

        $penalty += min(
            8,
            ((int) $metric->unnecessary_clicks) * 2
        );

        $penalty += min(
            10,
            max(
                0,
                ((float) $metric->feedback_delay_ms - 300)
                / 300
            )
        );

        $penalty += min(
            10,
            ((float) $metric->path_deviation_score) * 10
        );

        if ($metric->popup_detected) {
            $penalty += 4;
        }

        if ($metric->cookie_banner_detected) {
            $penalty += 2;
        }

        if ($metric->overlay_blocks_cta) {
            $penalty += 8;
        }

        if ($metric->overlay_blocks_action) {
            $penalty += 8;
        }

        if ($metric->timeout_occurred) {
            $penalty += 10;
        }

        if ($metric->crash_detected) {
            $penalty += 20;
        }

        if ($metric->anr_detected) {
            $penalty += 20;
        }

        if (($run->platform ?? 'web') === 'android') {
            $penalty += min(
                8,
                max(
                    0,
                    ((float) $metric->screen_load_time_ms - 1000)
                    / 500
                )
            );
        } else {
            $penalty += min(
                8,
                max(
                    0,
                    ((float) $metric->page_load_time_ms - 1500)
                    / 500
                )
            );
        }

        return (int) round(
            max(
                0,
                min(
                    100,
                    100 - $penalty
                )
            )
        );
    }

    private function compareFrictionLevels(
        ?string $beforeLevel,
        ?string $afterLevel
    ): array {
        $ranks = [
            'Low' => 1,
            'Medium' => 2,
            'High' => 3,
        ];

        if (
            !isset($ranks[$beforeLevel])
            || !isset($ranks[$afterLevel])
        ) {
            return [
                'before' => $beforeLevel ?? 'Not predicted',
                'after' => $afterLevel ?? 'Not predicted',
                'status' => 'unavailable',
                'label' => 'Comparison unavailable',
            ];
        }

        if ($ranks[$afterLevel] < $ranks[$beforeLevel]) {
            $status = 'improved';
            $label = 'Friction decreased';
        } elseif ($ranks[$afterLevel] > $ranks[$beforeLevel]) {
            $status = 'worsened';
            $label = 'Friction increased';
        } else {
            $status = 'unchanged';
            $label = 'Friction level unchanged';
        }

        return [
            'before' => $beforeLevel,
            'after' => $afterLevel,
            'status' => $status,
            'label' => $label,
        ];
    }

    private function normaliseRecommendations(
        mixed $recommendations
    ): array {
        if (empty($recommendations)) {
            return [];
        }

        if (is_string($recommendations)) {
            $decoded = json_decode(
                $recommendations,
                true
            );

            if (is_array($decoded)) {
                $recommendations = $decoded;
            } else {
                $recommendations = [
                    $recommendations,
                ];
            }
        }

        if (!is_array($recommendations)) {
            return [];
        }

        return collect($recommendations)
            ->flatten()
            ->filter(
                fn ($item) => is_string($item)
                    && trim($item) !== ''
            )
            ->map(
                fn ($item) => trim($item)
            )
            ->unique()
            ->values()
            ->all();
    }

    private function pairScreenshots(
        Collection $beforeScreenshots,
        Collection $afterScreenshots
    ): array {
        $beforeByKey = $this->keyScreenshots(
            $beforeScreenshots
        );

        $afterByKey = $this->keyScreenshots(
            $afterScreenshots
        );

        $keys = $beforeByKey
            ->keys()
            ->merge($afterByKey->keys())
            ->unique()
            ->values();

        return $keys
            ->map(function ($key) use (
                $beforeByKey,
                $afterByKey
            ) {
                return [
                    'key' => $key,
                    'before' => $beforeByKey->get($key),
                    'after' => $afterByKey->get($key),
                ];
            })
            ->values()
            ->all();
    }

    private function keyScreenshots(
        Collection $screenshots
    ): Collection {
        return $screenshots
            ->values()
            ->mapWithKeys(function ($screenshot, $index) {
                $key = $screenshot->flow_key
                    ?: $screenshot->label
                    ?: 'Screenshot ' . ($index + 1);

                return [
                    $key => $screenshot,
                ];
            });
    }

    private function determineOverallStatus(
        string $frictionStatus,
        int $scoreDifference,
        int $improvedCount,
        int $worsenedCount
    ): string {
        if (
            $frictionStatus === 'improved'
            && $scoreDifference > 0
        ) {
            return 'Improved';
        }

        if (
            $frictionStatus === 'worsened'
            || (
                $scoreDifference < 0
                && $worsenedCount > $improvedCount
            )
        ) {
            return 'Regressed';
        }

        if (
            $improvedCount > 0
            && $worsenedCount > 0
        ) {
            return 'Partially Improved';
        }

        if (
            $scoreDifference > 0
            || $improvedCount > $worsenedCount
        ) {
            return 'Improved';
        }

        return 'No Significant Change';
    }

    private function buildSummary(
        TestRun $before,
        TestRun $after,
        array $frictionComparison,
        int $beforeScore,
        int $afterScore,
        array $improvedMetrics,
        array $worsenedMetrics
    ): string {
        $parts = [];

        $parts[] = sprintf(
            'The project was compared using test run %s as the before result and %s as the after result.',
            $before->run_code,
            $after->run_code
        );

        if (
            $frictionComparison['status']
            !== 'unavailable'
        ) {
            $parts[] = sprintf(
                'The friction level changed from %s to %s.',
                $frictionComparison['before'],
                $frictionComparison['after']
            );
        }

        $scoreDifference = $afterScore - $beforeScore;

        if ($scoreDifference > 0) {
            $parts[] = sprintf(
                'The derived UX score increased from %d to %d, an improvement of %d points.',
                $beforeScore,
                $afterScore,
                $scoreDifference
            );
        } elseif ($scoreDifference < 0) {
            $parts[] = sprintf(
                'The derived UX score decreased from %d to %d, a reduction of %d points.',
                $beforeScore,
                $afterScore,
                abs($scoreDifference)
            );
        } else {
            $parts[] = sprintf(
                'The derived UX score remained unchanged at %d.',
                $beforeScore
            );
        }

        if (count($improvedMetrics) > 0) {
            $parts[] = sprintf(
                '%d measured UX metrics improved.',
                count($improvedMetrics)
            );
        }

        if (count($worsenedMetrics) > 0) {
            $parts[] = sprintf(
                '%d measured UX metrics worsened and should be reviewed.',
                count($worsenedMetrics)
            );
        }

        return implode(' ', $parts);
    }
}
