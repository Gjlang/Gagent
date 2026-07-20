<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(
        Request $request
    ): View {
        $user = $request->user();

        $projectCount = $user
            ->projects()
            ->count();

        $testRunCount = $user
            ->projects()
            ->withCount('testRuns')
            ->get()
            ->sum('test_runs_count');

        $reportCount = \App\Models\Report::query()
            ->whereHas(
                'testRun.project',
                function ($query) use ($user) {
                    $query->where(
                        'user_id',
                        $user->id
                    );
                }
            )
            ->count();

        return view(
            'profile.show',
            compact(
                'user',
                'projectCount',
                'testRunCount',
                'reportCount'
            )
        );
    }
}
