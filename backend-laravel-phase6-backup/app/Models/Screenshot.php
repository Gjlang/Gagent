<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Screenshot extends Model
{
    protected $fillable = [
        'test_run_id',
        'file_path',
        'label',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }
}
