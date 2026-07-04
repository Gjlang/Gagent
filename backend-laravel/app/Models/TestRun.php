<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestRun extends Model
{
    protected $fillable = [
        'project_id',
        'run_code',
        'flow_type',
        'scenario_type',
        'viewport_type',
        'network_condition',
        'run_mode',
        'max_duration_seconds',
        'page_url',
        'target_url',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'playwright_exit_code',
        'error_message',
        'raw_metrics_path',
        'report_path',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'float',
        'max_duration_seconds' => 'integer',
        'playwright_exit_code' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uxMetric(): HasOne
    {
        return $this->hasOne(UXMetric::class);
    }

    public function frictionResults(): HasMany
    {
        return $this->hasMany(FrictionResult::class);
    }

    public function finalFrictionResult(): HasOne
    {
        return $this->hasOne(FrictionResult::class)->where('is_final', true);
    }

    public function mainGAgentResult(): HasOne
    {
        return $this->hasOne(FrictionResult::class)->where('prediction_source', 'main_gagent');
    }

    public function baselineResult(): HasOne
    {
        return $this->hasOne(FrictionResult::class)->where('prediction_source', 'baseline');
    }

    public function interactionLogs(): HasMany
    {
        return $this->hasMany(InteractionLog::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function isLiveWebsiteRun(): bool
    {
        return $this->run_mode === 'live_website';
    }
}
