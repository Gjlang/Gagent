<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\Report;
use App\Models\TestRun;
use App\Models\UXMetric;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request
    ): View {
        $userId = $request->user()->id;

        $projectIds = Project::query()
            ->ownedBy($userId)
            ->select('id');

        $testRunIds = TestRun::query()
            ->whereIn(
                'project_id',
                clone $projectIds
            )
            ->select('id');

        $totalProjects = Project::query()
            ->ownedBy($userId)
            ->count();

        $totalTestRuns = TestRun::query()
            ->whereIn(
                'project_id',
                clone $projectIds
            )
            ->count();

        $totalReports = Report::query()
            ->whereIn(
                'test_run_id',
                clone $testRunIds
            )
            ->count();

        $finalResultsQuery = FrictionResult::query()
            ->whereIn(
                'test_run_id',
                clone $testRunIds
            )
            ->where('is_final', true);

        $severityCounts = [
            'Low' => (clone $finalResultsQuery)
                ->where(
                    'friction_level',
                    'Low'
                )
                ->count(),

            'Medium' => (clone $finalResultsQuery)
                ->where(
                    'friction_level',
                    'Medium'
                )
                ->count(),

            'High' => (clone $finalResultsQuery)
                ->where(
                    'friction_level',
                    'High'
                )
                ->count(),
        ];

        $averageConfidence = (
            clone $finalResultsQuery
        )
            ->whereNotNull(
                'confidence_score'
            )
            ->avg('confidence_score');

        $flowDistribution = UXMetric::query()
            ->whereIn(
                'test_run_id',
                clone $testRunIds
            )
            ->selectRaw(
                'flow_type, COUNT(*) as total'
            )
            ->groupBy('flow_type')
            ->pluck('total', 'flow_type')
            ->toArray();

        $recentTestRuns = TestRun::query()
            ->whereIn(
                'project_id',
                clone $projectIds
            )
            ->with([
                'project',
                'uxMetric',
                'finalFrictionResult',
                'mainGAgentResult',
                'baselineResult',
            ])
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::query()
            ->whereIn(
                'test_run_id',
                clone $testRunIds
            )
            ->with([
                'testRun.project',
                'testRun.finalFrictionResult',
            ])
            ->latest()
            ->take(5)
            ->get();

        return view(
            'dashboard',
            compact(
                'totalProjects',
                'totalTestRuns',
                'totalReports',
                'severityCounts',
                'averageConfidence',
                'flowDistribution',
                'recentTestRuns',
                'recentReports'
            )
        );
    }
}
