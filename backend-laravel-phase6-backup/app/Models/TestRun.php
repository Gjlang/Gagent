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
        'page_url',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uxMetric(): HasOne
    {
        return $this->hasOne(UXMetric::class);
    }

    public function frictionResult(): HasOne
    {
        return $this->hasOne(FrictionResult::class);
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
}
