<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UXMetric extends Model
{
    protected $table = 'ux_metrics';

    protected $fillable = [
        'test_run_id',
        'completion_time',
        'click_count',
        'scroll_count',
        'keyboard_count',
        'retry_count',
        'error_count',
        'failed_clicks',
        'feedback_delay',
        'task_completed',
        'screenshot_count',
        'error_message_clarity',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }

    public function toPredictionPayload(): array
    {
        return [
            'completion_time' => (float) $this->completion_time,
            'click_count' => (int) $this->click_count,
            'scroll_count' => (int) $this->scroll_count,
            'keyboard_count' => (int) $this->keyboard_count,
            'retry_count' => (int) $this->retry_count,
            'error_count' => (int) $this->error_count,
            'failed_clicks' => (int) $this->failed_clicks,
            'feedback_delay' => (float) $this->feedback_delay,
            'task_completed' => (int) $this->task_completed,
            'screenshot_count' => (int) $this->screenshot_count,
            'error_message_clarity' => (int) $this->error_message_clarity,
        ];
    }
}
