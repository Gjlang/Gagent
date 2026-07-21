<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\TestRunComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectComparisonController extends Controller
{
    public function index(
        Request $request,
        Project $project,
        TestRunComparisonService $comparisonService
    ): View|RedirectResponse {
        $eligibleRuns = $project
            ->testRuns()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereHas('uxMetric')
            ->whereHas('finalFrictionResult')
            ->with([
                'uxMetric',
                'finalFrictionResult',
            ])
            ->orderByDesc('completed_at')
            ->get();

        $comparison = null;
        $beforeRun = null;
        $afterRun = null;

        if (
            $request->filled('before_run')
            && $request->filled('after_run')
        ) {
            $validated = $request->validate([
                'before_run' => [
                    'required',
                    'integer',
                    'different:after_run',
                ],

                'after_run' => [
                    'required',
                    'integer',
                    'different:before_run',
                ],
            ]);

            $selectedRuns = $project
                ->testRuns()
                ->whereIn(
                    'id',
                    [
                        $validated['before_run'],
                        $validated['after_run'],
                    ]
                )
                ->with([
                    'uxMetric',
                    'finalFrictionResult',
                    'screenshots',
                    'report',
                ])
                ->get()
                ->keyBy('id');

            $beforeRun = $selectedRuns->get(
                (int) $validated['before_run']
            );

            $afterRun = $selectedRuns->get(
                (int) $validated['after_run']
            );

            if (!$beforeRun || !$afterRun) {
                abort(
                    404,
                    'One or both test runs do not belong to this project.'
                );
            }

            if (
                $beforeRun->platform !== 'web'
                || $afterRun->platform !== 'web'
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'This comparison flow currently supports website test runs only.'
                    );
            }

            if (
                $beforeRun->completed_at
                && $afterRun->completed_at
                && $afterRun->completed_at
                    ->lessThanOrEqualTo(
                        $beforeRun->completed_at
                    )
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'The After Test Run must be newer than the Before Test Run.'
                    );
            }

            $comparison = $comparisonService->compare(
                $beforeRun,
                $afterRun
            );
        }

        return view(
            'projects.comparison',
            [
                'project' => $project,
                'eligibleRuns' => $eligibleRuns,
                'comparison' => $comparison,
                'beforeRun' => $beforeRun,
                'afterRun' => $afterRun,
            ]
        );
    }
}
