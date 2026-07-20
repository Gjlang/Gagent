<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Report;
use App\Models\TestRun;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnedResource
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $userId = $request->user()?->id;

        if (!$userId) {
            abort(401);
        }

        $project = $request->route('project');

        if (
            $project instanceof Project
            && $project->user_id !== $userId
        ) {
            abort(404);
        }

        $testRun = $request->route('testRun');

        if (
            $testRun instanceof TestRun
            && $testRun->project?->user_id !== $userId
        ) {
            abort(404);
        }

        $report = $request->route('report');

        if (
            $report instanceof Report
            && $report->testRun?->project?->user_id
                !== $userId
        ) {
            abort(404);
        }

        return $next($request);
    }
}
