<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionLog extends Model
{
    protected $fillable = [
        'test_run_id',
        'event_type',
        'event_label',
        'event_value',
        'event_time',
        'metadata',
    ];

    protected $casts = [
        'event_time' => 'float',
        'metadata' => 'array',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }
}
