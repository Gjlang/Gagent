<?php

namespace App\Http\Controllers;

use App\Models\TestRun;

class TestRunController extends Controller
{
    public function index()
    {
        $testRuns = TestRun::with(['project', 'uxMetric', 'frictionResult'])
            ->latest()
            ->paginate(10);

        return view('test-runs.index', compact('testRuns'));
    }

    public function show(TestRun $testRun)
    {
        $testRun->load([
            'project',
            'uxMetric',
            'frictionResult',
            'screenshots',
            'interactionLogs',
            'report',
        ]);

        return view('test-runs.show', compact('testRun'));
    }
}
