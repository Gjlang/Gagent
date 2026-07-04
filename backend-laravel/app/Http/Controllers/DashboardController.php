<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\Report;
use App\Models\TestRun;
use App\Models\UXMetric;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProjects = Project::count();
        $totalTestRuns = TestRun::count();
        $totalReports = Report::count();

        $finalResultsQuery = FrictionResult::where('is_final', true);

        $severityCounts = [
            'Low' => (clone $finalResultsQuery)->where('friction_level', 'Low')->count(),
            'Medium' => (clone $finalResultsQuery)->where('friction_level', 'Medium')->count(),
            'High' => (clone $finalResultsQuery)->where('friction_level', 'High')->count(),
        ];

        $averageConfidence = FrictionResult::where('is_final', true)
            ->whereNotNull('confidence_score')
            ->avg('confidence_score');

        $flowDistribution = UXMetric::query()
            ->selectRaw('flow_type, COUNT(*) as total')
            ->groupBy('flow_type')
            ->pluck('total', 'flow_type')
            ->toArray();

        $recentTestRuns = TestRun::with([
            'project',
            'uxMetric',
            'finalFrictionResult',
            'mainGAgentResult',
            'baselineResult',
        ])
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::with(['testRun.project', 'testRun.finalFrictionResult'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalProjects',
            'totalTestRuns',
            'totalReports',
            'severityCounts',
            'averageConfidence',
            'flowDistribution',
            'recentTestRuns',
            'recentReports'
        ));
    }
}
