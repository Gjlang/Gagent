<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\TestRun;
use App\Services\GAgentAIService;
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

    public function generateAIExplanation(
        Report $report,
        GAgentAIService $aiService
    ) {
        $report->load([
            'testRun.uxMetric',
            'testRun.finalFrictionResult',
            'testRun.mainGAgentResult',
            'testRun.androidResult',
        ]);

        $run = $report->testRun;

        $metric = $run?->uxMetric;

        $finalResult = (
            $run?->finalFrictionResult
        );

        if (!$run || !$metric) {
            return back()->with(
                'error',
                'AI explanation cannot be generated because UX metrics are missing.'
            );
        }

        if (
            !$finalResult
            || !$finalResult->friction_level
        ) {
            return back()->with(
                'error',
                'AI explanation cannot be generated before the ML prediction is saved.'
            );
        }

        /*
         * The platform determines which existing
         * metric conversion method is used.
         */
        $platform = $run->isAndroidRun()
            ? 'android'
            : 'web';

        $metrics = $run->isAndroidRun()
            ? $metric->toAndroidPayload()
            : $metric->toGAgentPayload();

        /*
         * This payload uses the prediction that has
         * already been produced by the ML model.
         *
         * The LLM cannot modify friction_level.
         */
        $payload = [
            'platform' => $platform,

            'flow_type' => (string) (
                $run->flow_type
                ?? $metric->flow_type
                ?? 'unknown'
            ),

            'friction_level' => (string) (
                $finalResult->friction_level
            ),

            'confidence_score' => (
                $finalResult->confidence_score
            ),

            'class_probabilities' => (
                $finalResult->class_probabilities
                ?? []
            ),

            'metrics' => $metrics,

            'existing_recommendations' => (
                $finalResult->recommendations
                ?? []
            ),
        ];

        $response = (
            $aiService
                ->generateReportExplanation(
                    $payload
                )
        );

        $successful = (
            ($response['status'] ?? null)
            === 'success'
        );

        $data = $response['data'] ?? null;

        if (
            !$successful
            || !is_array($data)
        ) {
            return back()->with(
                'error',
                'AI explanation could not be generated: '
                . (
                    $response['message']
                    ?? 'FastAPI is unavailable.'
                )
            );
        }

        /*
         * Only update the report explanation fields.
         *
         * Do not update friction_results or change the
         * original ML result.
         */
        $report->update([
            'llm_summary' => (
                $data['summary']
                ?? null
            ),

            'llm_explanation' => (
                $data['explanation']
                ?? null
            ),

            'llm_recommendations' => (
                $data['recommendations']
                ?? []
            ),

            'llm_risk_reason' => (
                $data['risk_reason']
                ?? null
            ),

            'llm_model_name' => (
                $data['model_name']
                ?? 'llama3.2:1b'
            ),

            'llm_used' => (bool) (
                $data['llm_used']
                ?? false
            ),

            'llm_generated_at' => (
                Carbon::now()
            ),
        ]);

        $message = (
            $data['llm_used'] ?? false
        )
            ? (
                'AI report explanation generated '
                . 'with Ollama.'
            )
            : (
                'Ollama was unavailable. '
                . 'A rule-based fallback '
                . 'explanation was generated.'
            );

        return redirect()
            ->route(
                'reports.show',
                $report
            )
            ->with(
                'success',
                $message
            );
    }
}
