<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'test_run_id',
        'title',
        'summary',
        'conclusion',
        'generated_at',

        'llm_summary',
        'llm_explanation',
        'llm_recommendations',
        'llm_risk_reason',
        'llm_model_name',
        'llm_used',
        'llm_generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',

        'llm_recommendations' => 'array',
        'llm_used' => 'boolean',
        'llm_generated_at' => 'datetime',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(
            TestRun::class
        );
    }
}
