<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\Report;
use App\Services\GAgentAIService;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with([
            'testRun.project',
            'testRun.uxMetric',
            'testRun.frictionResult',
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
            'testRun.frictionResult',
            'testRun.screenshots',
            'testRun.interactionLogs',
        ]);

        return view('reports.show', compact('report'));
    }

    public function predict(Report $report, GAgentAIService $aiService)
    {
        $report->load(['testRun.uxMetric']);

        if (!$report->testRun || !$report->testRun->uxMetric) {
            return redirect()
                ->route('reports.show', $report)
                ->with('error', 'No UX metrics found for this report.');
        }

        $payload = $report->testRun->uxMetric->toPredictionPayload();

        $predictionResult = $aiService->predict($payload);

        if (($predictionResult['status'] ?? null) !== 'success') {
            return redirect()
                ->route('reports.show', $report)
                ->with('error', 'FastAPI prediction failed. Check that the AI service is running.');
        }

        $data = $predictionResult['data'];

        FrictionResult::updateOrCreate(
            ['test_run_id' => $report->test_run_id],
            [
                'friction_level' => $data['friction_level'] ?? null,
                'confidence_score' => $data['confidence_score'] ?? null,
                'model_used' => $data['model_used'] ?? null,
                'class_probabilities' => $data['class_probabilities'] ?? null,
                'recommendation' => $data['recommendation'] ?? [],
                'predicted_at' => Carbon::now(),
            ]
        );

        $report->update([
            'summary' => 'AI-assisted UX friction prediction generated successfully.',
            'conclusion' => 'The report has been updated using the latest stored UX metrics and FastAPI prediction result.',
            'generated_at' => Carbon::now(),
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Prediction generated and saved successfully.');
    }

    public function export(Report $report)
    {
        $report->load([
            'testRun.project',
            'testRun.uxMetric',
            'testRun.frictionResult',
        ]);

        $testRun = $report->testRun;
        $project = $testRun?->project;
        $metric = $testRun?->uxMetric;
        $result = $testRun?->frictionResult;

        $content = "GAgent UX Friction Report\n";
        $content .= "=========================\n\n";
        $content .= "Project: " . ($project?->project_name ?? 'N/A') . "\n";
        $content .= "Platform: " . ($project?->platform_type ?? 'N/A') . "\n";
        $content .= "Flow Type: " . ($testRun?->flow_type ?? 'N/A') . "\n";
        $content .= "Status: " . ($testRun?->status ?? 'N/A') . "\n\n";
        $content .= "Friction Level: " . ($result?->friction_level ?? 'Not predicted') . "\n";
        $content .= "Confidence Score: " . ($result?->confidence_score ?? 'N/A') . "\n";
        $content .= "Model Used: " . ($result?->model_used ?? 'N/A') . "\n\n";

        if ($metric) {
            $content .= "UX Metrics\n";
            $content .= "----------\n";
            $content .= "Completion Time: {$metric->completion_time}\n";
            $content .= "Click Count: {$metric->click_count}\n";
            $content .= "Scroll Count: {$metric->scroll_count}\n";
            $content .= "Keyboard Count: {$metric->keyboard_count}\n";
            $content .= "Retry Count: {$metric->retry_count}\n";
            $content .= "Error Count: {$metric->error_count}\n";
            $content .= "Failed Clicks: {$metric->failed_clicks}\n";
            $content .= "Feedback Delay: {$metric->feedback_delay}\n";
            $content .= "Task Completed: {$metric->task_completed}\n";
            $content .= "Screenshot Count: {$metric->screenshot_count}\n";
            $content .= "Error Message Clarity: {$metric->error_message_clarity}\n\n";
        }

        $content .= "Recommendations\n";
        $content .= "---------------\n";

        foreach (($result?->recommendation ?? []) as $recommendation) {
            $content .= "- {$recommendation}\n";
        }

        $content .= "\nConclusion\n";
        $content .= "----------\n";
        $content .= $report->conclusion ?? 'No conclusion available.';

        $filename = 'gagent-report-' . $report->id . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
}
