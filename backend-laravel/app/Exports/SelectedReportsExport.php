<?php

namespace App\Exports;

use App\Models\Report;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SelectedReportsExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithTitle
{
    public function __construct(
        private readonly Collection $reports
    ) {
    }

    public function headings(): array
    {
        return [
            'Report ID',
            'Report Title',
            'Generated At',
            'Project ID',
            'Project Name',
            'Project Description',
            'Run ID',
            'Run Code',
            'Platform',
            'Target Type',
            'Target URL',
            'Flow Type',
            'Scenario Type',
            'Viewport Type',
            'Network Condition',
            'Run Status',
            'Started At',
            'Completed At',
            'Friction Level',
            'Confidence Percentage',
            'Prediction Source',
            'Main Model',
            'Main Model Type',
            'Baseline Model',
            'Baseline Friction Level',
            'Task Completed',
            'Completion Time',
            'Click Count',
            'Scroll Count',
            'Keyboard Count',
            'Retry Count',
            'Error Count',
            'Failed Clicks',
            'Unnecessary Clicks',
            'Path Deviation Score',
            'Page Load Time MS',
            'Screen Load Time MS',
            'Feedback Delay MS',
            'Interaction Response Time MS',
            'Cumulative Layout Shift',
            'Popup Detected',
            'Cookie Banner Detected',
            'Overlay Blocks CTA',
            'Overlay Blocks Action',
            'Timeout Occurred',
            'Crash Detected',
            'ANR Detected',
            'Model Recommendations',
            'Report Summary',
            'Report Conclusion',
            'AI Summary',
            'AI Explanation',
            'AI Risk Reason',
            'AI Recommendations',
            'AI Model',
            'Screenshot Count',
            'Interaction Log Count',
        ];
    }

    public function array(): array
    {
        return $this->reports
            ->map(function (Report $report): array {
                $run = $report->testRun;
                $project = $run?->project;
                $metric = $run?->uxMetric;
                $final = $run?->finalFrictionResult;
                $main = $run?->mainGAgentResult;
                $baseline = $run?->baselineResult;

                return [
                    $report->id,
                    $report->title,
                    optional($report->generated_at)
                        ?->format('Y-m-d H:i:s'),

                    $project?->id,
                    $project?->name,
                    $project?->description,

                    $run?->id,
                    $run?->run_code,
                    $run?->platform ?? 'web',

                    $project?->target_type
                        ?? $run?->target_type,

                    $run?->target_url
                        ?? $run?->page_url
                        ?? $project?->target_url,

                    $run?->flow_type,

                    $run?->scenario_type
                        ?? $run?->run_mode,

                    $run?->viewport_type,
                    $run?->network_condition,
                    $run?->status,

                    optional($run?->started_at)
                        ?->format('Y-m-d H:i:s'),

                    optional($run?->completed_at)
                        ?->format('Y-m-d H:i:s'),

                    $final?->friction_level,

                    $final?->confidence_score !== null
                        ? round(
                            $final->confidence_score * 100,
                            2
                        )
                        : null,

                    $final?->prediction_source,
                    $main?->model_name,
                    $main?->model_type,
                    $baseline?->model_name,
                    $baseline?->friction_level,

                    $this->yesNo(
                        $metric?->task_completed
                    ),

                    $metric?->completion_time,
                    $metric?->click_count,
                    $metric?->scroll_count,
                    $metric?->keyboard_count,
                    $metric?->retry_count,
                    $metric?->error_count,
                    $metric?->failed_clicks,
                    $metric?->unnecessary_clicks,
                    $metric?->path_deviation_score,
                    $metric?->page_load_time_ms,
                    $metric?->screen_load_time_ms,
                    $metric?->feedback_delay_ms,
                    $metric?->interaction_response_time_ms,
                    $metric?->cumulative_layout_shift,

                    $this->yesNo(
                        $metric?->popup_detected
                    ),

                    $this->yesNo(
                        $metric?->cookie_banner_detected
                    ),

                    $this->yesNo(
                        $metric?->overlay_blocks_cta
                    ),

                    $this->yesNo(
                        $metric?->overlay_blocks_action
                    ),

                    $this->yesNo(
                        $metric?->timeout_occurred
                    ),

                    $this->yesNo(
                        $metric?->crash_detected
                    ),

                    $this->yesNo(
                        $metric?->anr_detected
                    ),

                    $this->flattenList(
                        $main?->recommendations
                    ),

                    $report->summary,
                    $report->conclusion,
                    $report->llm_summary,
                    $report->llm_explanation,
                    $report->llm_risk_reason,

                    $this->flattenList(
                        $report->llm_recommendations
                    ),

                    $report->llm_model_name,

                    $run?->screenshots?->count() ?? 0,

                    $run?->interactionLogs?->count() ?? 0,
                ];
            })
            ->values()
            ->all();
    }

    public function title(): string
    {
        return 'Selected Reports';
    }

    private function yesNo(
        bool|int|null $value
    ): string {
        if ($value === null) {
            return 'N/A';
        }

        return (bool) $value
            ? 'Yes'
            : 'No';
    }

    private function flattenList(
        mixed $value
    ): string {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode(
                $value,
                true
            );

            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                return $value;
            }
        }

        if (!is_array($value)) {
            return (string) $value;
        }

        return collect($value)
            ->map(function (mixed $item): string {
                return is_array($item)
                    ? json_encode(
                        $item,
                        JSON_UNESCAPED_SLASHES
                    )
                    : (string) $item;
            })
            ->implode(' | ');
    }
}
