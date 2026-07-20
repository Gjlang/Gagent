<?php

namespace App\Http\Controllers;

use App\Models\FrictionResult;
use App\Models\TestRun;
use App\Services\GAgentAIService;
use Illuminate\Support\Facades\DB;
use Throwable;

class TestRunController extends Controller
{
    public function index(
    \Illuminate\Http\Request $request
) {
    $testRuns = TestRun::query()
        ->whereHas(
            'project',
            function ($query) use ($request) {
                $query->where(
                    'user_id',
                    $request->user()->id
                );
            }
        )
        ->with([
            'project',
            'uxMetric',
            'finalFrictionResult',
            'mainGAgentResult',
            'baselineResult',
            'report',
        ])
        ->latest()
        ->paginate(10);

    return view(
        'test-runs.index',
        compact('testRuns')
    );
}

    public function show(TestRun $testRun)
    {
        $testRun->load([
            'project',
            'uxMetric',
            'frictionResults',
            'finalFrictionResult',
            'mainGAgentResult',
            'baselineResult',
            'screenshots',
            'interactionLogs',
            'report',
        ]);

        return view('test-runs.show', compact('testRun'));
    }

    public function runPrediction(TestRun $testRun, GAgentAIService $aiService)
    {
        $testRun->load('uxMetric');

        if (!$testRun->uxMetric) {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'No UX metrics found for this test run.');
        }

        $payload = $testRun->uxMetric->toGAgentPayload();
        $prediction = $aiService->predictGAgent($payload);

        if (($prediction['status'] ?? null) !== 'success') {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Main GAgent prediction failed: ' . ($prediction['message'] ?? 'Unknown error.'));
        }

        $data = $prediction['data'] ?? [];

        try {
            DB::transaction(function () use ($testRun, $data, $payload) {
                FrictionResult::where('test_run_id', $testRun->id)
                    ->where('prediction_source', 'main_gagent')
                    ->delete();

                FrictionResult::where('test_run_id', $testRun->id)
                    ->update(['is_final' => false]);

                FrictionResult::create([
                    'test_run_id' => $testRun->id,
                    'model_name' => $data['model_name'] ?? $data['model_used'] ?? 'main_gagent_model',
                    'model_type' => $data['model_type'] ?? 'main_gagent',
                    'prediction_source' => 'main_gagent',
                    'friction_level' => $data['friction_level'] ?? $data['prediction'] ?? null,
                    'confidence_score' => $data['confidence_score'] ?? $data['confidence'] ?? null,
                    'class_probabilities' => $data['class_probabilities'] ?? [],
                    'recommendations' => $data['recommendations'] ?? $data['recommendation'] ?? [],
                    'input_features' => $payload,
                    'is_final' => true,
                ]);
            });
        } catch (Throwable $error) {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Prediction was returned but database save failed: ' . $error->getMessage());
        }

        return redirect()
            ->route('test-runs.show', $testRun)
            ->with('success', 'Main GAgent prediction saved successfully.');
    }

    public function runBaselinePrediction(TestRun $testRun, GAgentAIService $aiService)
    {
        $testRun->load('uxMetric');

        if (!$testRun->uxMetric) {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'No UX metrics found for this test run.');
        }

        $payload = $testRun->uxMetric->toBaselinePayload();
        $prediction = $aiService->predictBaseline($payload);

        if (($prediction['status'] ?? null) !== 'success') {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Baseline prediction failed: ' . ($prediction['message'] ?? 'Unknown error.'));
        }

        $data = $prediction['data'] ?? [];

        try {
            FrictionResult::where('test_run_id', $testRun->id)
                ->where('prediction_source', 'baseline')
                ->delete();

            FrictionResult::create([
                'test_run_id' => $testRun->id,
                'model_name' => $data['model_name'] ?? $data['model_used'] ?? 'baseline_model',
                'model_type' => $data['model_type'] ?? 'baseline',
                'prediction_source' => 'baseline',
                'friction_level' => $data['friction_level'] ?? $data['prediction'] ?? null,
                'confidence_score' => $data['confidence_score'] ?? $data['confidence'] ?? null,
                'class_probabilities' => $data['class_probabilities'] ?? [],
                'recommendations' => $data['recommendations'] ?? $data['recommendation'] ?? [],
                'input_features' => $payload,
                'is_final' => false,
            ]);
        } catch (Throwable $error) {
            return redirect()
                ->route('test-runs.show', $testRun)
                ->with('error', 'Baseline result returned but database save failed: ' . $error->getMessage());
        }

        return redirect()
            ->route('test-runs.show', $testRun)
            ->with('success', 'Baseline prediction saved successfully.');
    }
}
