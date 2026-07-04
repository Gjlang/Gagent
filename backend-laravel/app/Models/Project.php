<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_type',
        'target_url',
        'status',
    ];

    public function testRuns(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }
}
