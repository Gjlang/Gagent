<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UXMetric extends Model
{
    protected $table = 'ux_metrics';

    protected $fillable = [
        'test_run_id',
        'flow_type',
        'scenario_type',
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

    protected $casts = [
        'task_completed' => 'boolean',
        'task_failed' => 'boolean',
        'completion_time' => 'float',
        'click_count' => 'integer',
        'scroll_count' => 'integer',
        'keyboard_count' => 'integer',
        'retry_count' => 'integer',
        'error_count' => 'integer',
        'failed_clicks' => 'integer',
        'unnecessary_clicks' => 'integer',
        'path_deviation_score' => 'float',
        'page_load_time_ms' => 'float',
        'dom_content_loaded_ms' => 'float',
        'time_to_first_byte_ms' => 'float',
        'feedback_delay_ms' => 'float',
        'interaction_to_next_paint_ms' => 'float',
        'cumulative_layout_shift' => 'float',
        'error_message_present' => 'boolean',
        'error_message_clarity' => 'integer',
        'popup_detected' => 'boolean',
        'cookie_banner_detected' => 'boolean',
        'overlay_blocks_cta' => 'boolean',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }

    public function toGAgentPayload(): array
    {
        return [
            'flow_type' => (string) $this->flow_type,
            'viewport_type' => (string) $this->viewport_type,
            'task_completed' => (int) $this->task_completed,
            'task_failed' => (int) $this->task_failed,
            'completion_time' => (float) $this->completion_time,
            'click_count' => (int) $this->click_count,
            'scroll_count' => (int) $this->scroll_count,
            'keyboard_count' => (int) $this->keyboard_count,
            'retry_count' => (int) $this->retry_count,
            'error_count' => (int) $this->error_count,
            'failed_clicks' => (int) $this->failed_clicks,
            'unnecessary_clicks' => (int) $this->unnecessary_clicks,
            'path_deviation_score' => (float) $this->path_deviation_score,
            'page_load_time_ms' => (float) $this->page_load_time_ms,
            'dom_content_loaded_ms' => (float) $this->dom_content_loaded_ms,
            'time_to_first_byte_ms' => (float) $this->time_to_first_byte_ms,
            'feedback_delay_ms' => (float) $this->feedback_delay_ms,
            'interaction_to_next_paint_ms' => (float) $this->interaction_to_next_paint_ms,
            'cumulative_layout_shift' => (float) $this->cumulative_layout_shift,
            'error_message_present' => (int) $this->error_message_present,
            'error_message_clarity' => (int) $this->error_message_clarity,
            'popup_detected' => (int) $this->popup_detected,
            'cookie_banner_detected' => (int) $this->cookie_banner_detected,
            'overlay_blocks_cta' => (int) $this->overlay_blocks_cta,
        ];
    }

   public function toBaselinePayload(): array
    {
        return [
            'completion_time' => (float) $this->completion_time,
            'click_count' => (int) $this->click_count,
            'scroll_count' => (int) $this->scroll_count,
            'keyboard_count' => (int) $this->keyboard_count,
            'retry_count' => (int) $this->retry_count,
            'error_count' => (int) $this->error_count,
            'failed_clicks' => (int) $this->failed_clicks,
            'task_completed' => (int) $this->task_completed,
        ];
    }
}
