<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Project;
use App\Models\TestRun;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProjects = Project::count();
        $totalTestRuns = TestRun::count();

        $severityCounts = [
            'Low' => FrictionResult::where('friction_level', 'Low')->count(),
            'Medium' => FrictionResult::where('friction_level', 'Medium')->count(),
            'High' => FrictionResult::where('friction_level', 'High')->count(),
        ];

        $averageConfidence = FrictionResult::whereNotNull('confidence_score')
            ->avg('confidence_score');

        $recentTestRuns = TestRun::with(['project', 'uxMetric', 'frictionResult'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalProjects',
            'totalTestRuns',
            'severityCounts',
            'averageConfidence',
            'recentTestRuns'
        ));
    }
}
