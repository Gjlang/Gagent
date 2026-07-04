<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrictionResult extends Model
{
    protected $fillable = [
        'test_run_id',
        'friction_level',
        'confidence_score',
        'model_used',
        'class_probabilities',
        'recommendation',
        'predicted_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'class_probabilities' => 'array',
        'recommendation' => 'array',
        'predicted_at' => 'datetime',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }
}
