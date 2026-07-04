@extends('layouts.app')

@section('title', 'AI Service Test')

@section('content')
<div class="card">
    <h3>Laravel to FastAPI Connection Test</h3>
    <p class="muted">
        This page checks whether Laravel can call the FastAPI AI service at
        <strong>{{ env('GAGENT_AI_SERVICE_URL', 'http://127.0.0.1:8001') }}</strong>.
    </p>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Health Result</h3>
        <p><strong>Status:</strong> {{ $health['status'] ?? 'N/A' }}</p>
        <p><strong>HTTP:</strong> {{ $health['http_status'] ?? 'N/A' }}</p>
        <pre>{{ json_encode($health, JSON_PRETTY_PRINT) }}</pre>
    </div>

    <div class="card">
        <h3>Model Info</h3>
        <p><strong>Status:</strong> {{ $modelInfo['status'] ?? 'N/A' }}</p>
        <p><strong>HTTP:</strong> {{ $modelInfo['http_status'] ?? 'N/A' }}</p>
        <pre>{{ json_encode($modelInfo, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Main GAgent Prediction Test</h3>
        <p><strong>Status:</strong> {{ $mainPrediction['status'] ?? 'N/A' }}</p>
        <p><strong>HTTP:</strong> {{ $mainPrediction['http_status'] ?? 'N/A' }}</p>
        <pre>{{ json_encode($mainPrediction, JSON_PRETTY_PRINT) }}</pre>
    </div>

    <div class="card">
        <h3>Baseline Prediction Test</h3>
        <p><strong>Status:</strong> {{ $baselinePrediction['status'] ?? 'N/A' }}</p>
        <p><strong>HTTP:</strong> {{ $baselinePrediction['http_status'] ?? 'N/A' }}</p>
        <pre>{{ json_encode($baselinePrediction, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>

<div class="card">
    <h3>Payload Sent to Main GAgent</h3>
    <pre>{{ json_encode($sampleGAgentFeatures, JSON_PRETTY_PRINT) }}</pre>
</div>

<div class="card">
    <h3>Payload Sent to Baseline</h3>
    <pre>{{ json_encode($baselineFeatures, JSON_PRETTY_PRINT) }}</pre>
</div>
@endsection
