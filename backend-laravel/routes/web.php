<?php

use App\Http\Controllers\AIServiceTestController;
use App\Http\Controllers\AndroidTestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LiveTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectComparisonController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestRunController;
use App\Http\Controllers\UnifiedTestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestRunComparisonController;

Route::middleware('guest')->group(function () {
    Route::get(
        '/login',
        [
            AuthenticatedSessionController::class,
            'create',
        ]
    )->name('login');
    Route::get(
    '/profile',
    [
        ProfileController::class,
        'show',
    ]
)->name('profile.show');

    Route::post(
        '/login',
        [
            AuthenticatedSessionController::class,
            'store',
        ]
    )->name('login.store');

    Route::get(
        '/register',
        [
            RegisteredUserController::class,
            'create',
        ]
    )->name('register');

    Route::post(
        '/register',
        [
            RegisteredUserController::class,
            'store',
        ]
    )->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::post(
        '/logout',
        [
            AuthenticatedSessionController::class,
            'destroy',
        ]
    )->name('logout');

    Route::get(
        '/dashboard',
        [
            DashboardController::class,
            'index',
        ]
    )->name('dashboard');

    Route::get(
        '/projects',
        [
            ProjectController::class,
            'index',
        ]
    )->name('projects.index');
    Route::get(
    '/projects/{project}/comparison',
    [
        ProjectComparisonController::class,
        'index',
    ]
)
    ->middleware('owned')
    ->name('projects.comparison');

    Route::get(
        '/projects/create',
        [
            ProjectController::class,
            'create',
        ]
    )->name('projects.create');

    Route::post(
        '/projects',
        [
            ProjectController::class,
            'store',
        ]
    )->name('projects.store');

    Route::get(
        '/projects/{project}',
        [
            ProjectController::class,
            'show',
        ]
    )
        ->middleware('owned')
        ->name('projects.show');

    Route::get(
        '/run-test',
        [
            UnifiedTestController::class,
            'create',
        ]
    )->name('unified-tests.create');

    Route::post(
        '/run-test',
        [
            UnifiedTestController::class,
            'store',
        ]
    )->name('unified-tests.store');

    Route::get(
        '/live-tests/create',
        [
            LiveTestController::class,
            'create',
        ]
    )->name('live-tests.create');

    Route::post(
        '/live-tests',
        [
            LiveTestController::class,
            'store',
        ]
    )->name('live-tests.store');

    Route::get(
        '/live-tests/{testRun}',
        [
            LiveTestController::class,
            'show',
        ]
    )
        ->middleware('owned')
        ->name('live-tests.show');

    Route::post(
        '/live-tests/{testRun}/run',
        [
            LiveTestController::class,
            'run',
        ]
    )
        ->middleware('owned')
        ->name('live-tests.run');

    Route::get(
        '/android-tests/create',
        [
            AndroidTestController::class,
            'create',
        ]
    )->name('android-tests.create');

    Route::post(
        '/android-tests',
        [
            AndroidTestController::class,
            'store',
        ]
    )->name('android-tests.store');

    Route::get(
        '/android-tests/{testRun}',
        [
            AndroidTestController::class,
            'show',
        ]
    )
        ->middleware('owned')
        ->name('android-tests.show');

    Route::post(
        '/android-tests/{testRun}/run',
        [
            AndroidTestController::class,
            'run',
        ]
    )
        ->middleware('owned')
        ->name('android-tests.run');

    Route::get(
        '/test-runs',
        [
            TestRunController::class,
            'index',
        ]
    )->name('test-runs.index');

    Route::get(
        '/test-runs/{testRun}',
        [
            TestRunController::class,
            'show',
        ]
    )
        ->middleware('owned')
        ->name('test-runs.show');

    Route::post(
        '/test-runs/{testRun}/predict-gagent',
        [
            TestRunController::class,
            'runPrediction',
        ]
    )
        ->middleware('owned')
        ->name('test-runs.predict-gagent');

    Route::post(
        '/test-runs/{testRun}/predict-baseline',
        [
            TestRunController::class,
            'runBaselinePrediction',
        ]
    )
        ->middleware('owned')
        ->name('test-runs.predict-baseline');

    Route::get(
        '/reports',
        [
            ReportController::class,
            'index',
        ]
    )->name('reports.index');
    Route::get(
    '/comparisons',
    [
        TestRunComparisonController::class,
        'index',
    ]
)->name('comparisons.index');

Route::get(
    '/comparisons/{comparison}',
    [
        TestRunComparisonController::class,
        'show',
    ]
)->name('comparisons.show');

Route::post(
    '/comparisons/{comparison}/generate-explanation',
    [
        TestRunComparisonController::class,
        'generateExplanation',
    ]
)->name('comparisons.generate-explanation');

    /*
     * Keep these two routes if you completed
     * multiple-report export.
     */
    Route::post(
        '/reports/download-selected/pdf',
        [
            ReportController::class,
            'downloadSelectedPdf',
        ]
    )->name('reports.download-selected.pdf');

    Route::post(
        '/reports/download-selected/excel',
        [
            ReportController::class,
            'downloadSelectedExcel',
        ]
    )->name('reports.download-selected.excel');

    /*
     * Keep this route if you completed
     * single-report PDF export.
     */
    Route::get(
        '/reports/{report}/download/pdf',
        [
            ReportController::class,
            'downloadPdf',
        ]
    )
        ->middleware('owned')
        ->name('reports.download.pdf');

    Route::get(
        '/reports/{report}',
        [
            ReportController::class,
            'show',
        ]
    )
        ->middleware('owned')
        ->name('reports.show');

    Route::post(
        '/reports/generate/{testRun}',
        [
            ReportController::class,
            'generate',
        ]
    )
        ->middleware('owned')
        ->name('reports.generate');

    Route::post(
        '/reports/{report}/generate-ai-explanation',
        [
            ReportController::class,
            'generateAIExplanation',
        ]
    )
        ->middleware('owned')
        ->name('reports.generate-ai-explanation');

    Route::get(
        '/ai-service-test',
        AIServiceTestController::class
    )->name('ai.test');
});
