<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\TestRun;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with([
            'testRun.project',
            'testRun.finalFrictionResult',
            'testRun.mainGAgentResult',
            'testRun.baselineResult',
        ])
            ->latest()
            ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $report->load([
            'testRun.project',
            'testRun.uxMetric',
            'testRun.frictionResults',
            'testRun.finalFrictionResult',
            'testRun.mainGAgentResult',
            'testRun.baselineResult',
            'testRun.screenshots',
            'testRun.interactionLogs',
        ]);

        return view('reports.show', compact('report'));
    }

    public function generate(TestRun $testRun)
    {
        $testRun->load([
            'project',
            'uxMetric',
            'finalFrictionResult',
            'mainGAgentResult',
            'baselineResult',
        ]);

        if (!$testRun->uxMetric) {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Cannot generate report because this test run has no UX metrics.');
        }

        $finalResult = $testRun->finalFrictionResult;

        $summary = $finalResult
            ? 'Final UX friction level is ' . $finalResult->friction_level . ' with confidence score ' . number_format(($finalResult->confidence_score ?? 0) * 100, 1) . '%.'
            : 'Report generated from stored UX metrics. No final GAgent prediction has been saved yet.';

        $conclusion = $finalResult
            ? 'The main GAgent model is used as the final system decision. The baseline model is only used as a comparison reference.'
            : 'Run the main GAgent prediction to complete the AI-assisted friction diagnosis.';

        $report = Report::updateOrCreate(
            ['test_run_id' => $testRun->id],
            [
                'title' => 'UX Friction Report - ' . $testRun->run_code,
                'summary' => $summary,
                'conclusion' => $conclusion,
                'generated_at' => Carbon::now(),
            ]
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report generated successfully.');
    }
}
