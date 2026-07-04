<?php

use App\Http\Controllers\AIServiceTestController;
use App\Http\Controllers\DashboardController;
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

Route::get('/test-runs', [TestRunController::class, 'index'])->name('test-runs.index');
Route::get('/test-runs/{testRun}', [TestRunController::class, 'show'])->name('test-runs.show');
Route::post('/test-runs/{testRun}/predict-gagent', [TestRunController::class, 'runPrediction'])->name('test-runs.predict-gagent');
Route::post('/test-runs/{testRun}/predict-baseline', [TestRunController::class, 'runBaselinePrediction'])->name('test-runs.predict-baseline');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::post('/reports/generate/{testRun}', [ReportController::class, 'generate'])->name('reports.generate');

Route::get('/ai-service-test', AIServiceTestController::class)->name('ai.test');
