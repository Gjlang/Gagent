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
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }
}
