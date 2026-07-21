<?php

namespace App\Http\Controllers;

use App\Models\TestRunComparison;
use App\Services\ComparisonLLMService;
use App\Services\TestRunComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TestRunComparisonController extends Controller
{
    public function index(): View
    {
        $comparisons = TestRunComparison::query()
            ->where(
                'user_id',
                Auth::id()
            )
            ->with([
                'project',
                'beforeRun.finalFrictionResult',
                'afterRun.finalFrictionResult',
            ])
            ->latest()
            ->paginate(15);

        return view(
            'comparisons.index',
            compact('comparisons')
        );
    }

    public function show(
        TestRunComparison $comparison,
        TestRunComparisonService $comparisonService
    ): View {
        $this->authoriseComparison(
            $comparison
        );

        $this->loadComparisonRelations(
            $comparison
        );

        $result = $comparisonService->compare(
            $comparison->beforeRun,
            $comparison->afterRun
        );

        $eligibleRuns = collect([
            $comparison->beforeRun,
            $comparison->afterRun,
        ]);

        return view(
            'projects.comparison',
            [
                'project' => $comparison->project,
                'eligibleRuns' => $eligibleRuns,
                'comparison' => $result,
                'beforeRun' => $comparison->beforeRun,
                'afterRun' => $comparison->afterRun,
                'savedComparison' => $comparison,
            ]
        );
    }

    public function generateExplanation(
        TestRunComparison $comparison,
        TestRunComparisonService $comparisonService,
        ComparisonLLMService $llmService
    ): RedirectResponse {
        $this->authoriseComparison(
            $comparison
        );

        $this->loadComparisonRelations(
            $comparison
        );

        $result = $comparisonService->compare(
            $comparison->beforeRun,
            $comparison->afterRun
        );

        $explanation = $llmService->generate(
            $result,
            $comparison->project?->name
                ?? 'Website UX Project'
        );

        $comparison->update([
            'llm_summary' => (
                $explanation['summary']
                ?? null
            ),

            'llm_assessment' => (
                $explanation['assessment']
                ?? null
            ),

            'llm_improvements' => (
                $explanation['improvements']
                ?? []
            ),

            'llm_regressions' => (
                $explanation['regressions']
                ?? []
            ),

            'llm_next_actions' => (
                $explanation['next_actions']
                ?? []
            ),

            'llm_provider' => (
                $explanation['provider']
                ?? 'unknown'
            ),

            'llm_model' => (
                $explanation['model']
                ?? null
            ),

            'llm_error' => (
                $explanation['error']
                ?? null
            ),

            'llm_generated_at' => now(),
        ]);

        $message = (
            $explanation['status']
            ?? null
        ) === 'success'
            ? 'AI comparison explanation generated successfully.'
            : 'Ollama was unavailable, so a rule-based comparison explanation was saved.';

        return redirect()
            ->route(
                'comparisons.show',
                $comparison
            )
            ->with(
                'success',
                $message
            );
    }

    private function authoriseComparison(
        TestRunComparison $comparison
    ): void {
        abort_unless(
            (int) $comparison->user_id
                === (int) Auth::id(),
            403
        );
    }

    private function loadComparisonRelations(
        TestRunComparison $comparison
    ): void {
        $comparison->load([
            'project',
            'beforeRun.uxMetric',
            'beforeRun.finalFrictionResult',
            'beforeRun.screenshots',
            'afterRun.uxMetric',
            'afterRun.finalFrictionResult',
            'afterRun.screenshots',
        ]);

        abort_if(
            !$comparison->beforeRun
                || !$comparison->afterRun,
            404,
            'The comparison test runs could not be found.'
        );
    }
}
