<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'project_name',
        'platform_type',
        'website_url',
        'status',
    ];

    public function testRuns(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }
}
