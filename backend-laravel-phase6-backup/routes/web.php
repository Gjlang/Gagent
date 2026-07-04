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

Route::resource('projects', ProjectController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy']);

Route::resource('test-runs', TestRunController::class)
    ->only(['index', 'show']);

Route::resource('reports', ReportController::class)
    ->only(['index', 'show']);

Route::post('/reports/{report}/predict', [ReportController::class, 'predict'])
    ->name('reports.predict');

Route::get('/reports/{report}/export', [ReportController::class, 'export'])
    ->name('reports.export');

Route::get('/test-ai-prediction', AIServiceTestController::class)
    ->name('ai.test');
