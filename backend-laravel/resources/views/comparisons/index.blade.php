@extends('layouts.app')

@section('title', 'Comparisons')
@section('kicker', 'Improvement Tracking')

@section('content')
<div class="g-page-header">
    <div>
        <h2>Saved Comparisons</h2>

        <p>
            View website test runs that have already been
            compared.
        </p>
    </div>
</div>

<div class="g-card">
    @if ($comparisons->isEmpty())
        <div class="g-empty">
            <strong>No saved comparisons yet.</strong>

            Open a website report and select
            “Retest Website and Compare” to create one.
        </div>
    @else
        <div class="g-table-wrap">
            <table class="g-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Before Run</th>
                        <th>Before Friction</th>
                        <th>After Run</th>
                        <th>After Friction</th>
                        <th>Created</th>
                        <th>AI Explanation</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($comparisons as $item)
                        @php
                            $beforeLevel =
                                $item
                                    ->beforeRun
                                    ?->finalFrictionResult
                                    ?->friction_level
                                ?? 'N/A';

                            $afterLevel =
                                $item
                                    ->afterRun
                                    ?->finalFrictionResult
                                    ?->friction_level
                                ?? 'N/A';

                            $beforeBadgeClass = match ($beforeLevel) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };

                            $afterBadgeClass = match ($afterLevel) {
                                'Low' => 'badge-low',
                                'Medium' => 'badge-medium',
                                'High' => 'badge-high',
                                default => 'badge-neutral',
                            };
                        @endphp

                        <tr>
                            <td>
                                <strong>
                                    {{ $item->project?->name ?? 'N/A' }}
                                </strong>
                            </td>

                            <td>
                                {{ $item->beforeRun?->run_code ?? 'N/A' }}
                            </td>

                            <td>
                                <span class="g-badge {{ $beforeBadgeClass }}">
                                    {{ $beforeLevel }}
                                </span>
                            </td>

                            <td>
                                {{ $item->afterRun?->run_code ?? 'N/A' }}
                            </td>

                            <td>
                                <span class="g-badge {{ $afterBadgeClass }}">
                                    {{ $afterLevel }}
                                </span>
                            </td>

                            <td>
                                {{
                                    optional($item->created_at)
                                        ->format('Y-m-d H:i')
                                }}
                            </td>

                            <td>
                                @if ($item->llm_generated_at)
                                    <span class="g-badge badge-low">
                                        Generated
                                    </span>

                                    <div
                                        class="g-muted g-small"
                                        style="margin-top: 5px;"
                                    >
                                        {{
                                            optional(
                                                $item->llm_generated_at
                                            )->format('Y-m-d H:i')
                                        }}
                                    </div>
                                @else
                                    <span class="g-badge badge-neutral">
                                        Not Generated
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    class="g-btn g-btn-primary"
                                    href="{{
                                        route(
                                            'comparisons.show',
                                            $item
                                        )
                                    }}"
                                >
                                    Show Comparison
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $comparisons->links() }}
        </div>
    @endif
</div>
@endsection
