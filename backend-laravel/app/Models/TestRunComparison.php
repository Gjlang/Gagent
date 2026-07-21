<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRunComparison extends Model
{
    protected $fillable = [
        'project_id',
        'before_test_run_id',
        'after_test_run_id',
        'user_id',

        'llm_summary',
        'llm_assessment',
        'llm_improvements',
        'llm_regressions',
        'llm_next_actions',
        'llm_provider',
        'llm_model',
        'llm_error',
        'llm_generated_at',
    ];

    protected $casts = [
        'llm_improvements' => 'array',
        'llm_regressions' => 'array',
        'llm_next_actions' => 'array',
        'llm_generated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class
        );
    }

    public function beforeRun(): BelongsTo
    {
        return $this->belongsTo(
            TestRun::class,
            'before_test_run_id'
        );
    }

    public function afterRun(): BelongsTo
    {
        return $this->belongsTo(
            TestRun::class,
            'after_test_run_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function hasLlmExplanation(): bool
    {
        return $this->llm_generated_at !== null
            && filled($this->llm_summary);
    }
}
