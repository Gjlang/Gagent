<?php

use App\Http\Controllers\AIServiceTestController;
use App\Http\Controllers\AndroidTestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LiveTestController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestRunController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/live-tests/create', [LiveTestController::class, 'create'])->name('live-tests.create');
Route::post('/live-tests', [LiveTestController::class, 'store'])->name('live-tests.store');
Route::get('/live-tests/{testRun}', [LiveTestController::class, 'show'])->name('live-tests.show');
Route::post('/live-tests/{testRun}/run', [LiveTestController::class, 'run'])->name('live-tests.run');

Route::get('/android-tests/create', [AndroidTestController::class, 'create'])->name('android-tests.create');
Route::post('/android-tests', [AndroidTestController::class, 'store'])->name('android-tests.store');
Route::get('/android-tests/{testRun}', [AndroidTestController::class, 'show'])->name('android-tests.show');
Route::post('/android-tests/{testRun}/predict', [AndroidTestController::class, 'predict'])->name('android-tests.predict');

Route::get('/test-runs', [TestRunController::class, 'index'])->name('test-runs.index');
Route::get('/test-runs/{testRun}', [TestRunController::class, 'show'])->name('test-runs.show');
Route::post('/test-runs/{testRun}/predict-gagent', [TestRunController::class, 'runPrediction'])->name('test-runs.predict-gagent');
Route::post('/test-runs/{testRun}/predict-baseline', [TestRunController::class, 'runBaselinePrediction'])->name('test-runs.predict-baseline');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::post('/reports/generate/{testRun}', [ReportController::class, 'generate'])->name('reports.generate');

Route::get('/ai-service-test', AIServiceTestController::class)->name('ai.test');
