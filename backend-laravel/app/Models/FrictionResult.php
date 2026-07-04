<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrictionResult extends Model
{
    protected $fillable = [
        'test_run_id',
        'model_name',
        'model_type',
        'prediction_source',
        'friction_level',
        'confidence_score',
        'class_probabilities',
        'recommendations',
        'input_features',
        'is_final',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'class_probabilities' => 'array',
        'recommendations' => 'array',
        'input_features' => 'array',
        'is_final' => 'boolean',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }
}
