<?php $__env->startSection('title', 'Reports'); ?>
<?php $__env->startSection('kicker', 'UX Audit Output'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $reportCollection = $reports ?? collect();
?>

<div class="g-page-header">
    <div>
        <h2>UX Friction Reports</h2>

        <p>
            Open generated AI-assisted reports or select
            multiple reports for PDF and Excel export.
        </p>
    </div>

    <div class="g-actions">
        <a
            class="g-btn g-btn-primary"
            href="<?php echo e(route('test-runs.index')); ?>"
        >
            Generate From Test Run
        </a>
    </div>
</div>

<div class="g-card">
    <div
        class="g-split-row"
        style="margin-bottom: 12px;"
    >
        <div>
            <h3>Report Library</h3>

            <p
                class="g-muted g-small"
                style="margin-top: 5px;"
            >
                Select reports from the current page and
                choose the required export format.
            </p>
        </div>

        <span class="g-badge badge-final">
            <?php echo e(method_exists(
                    $reportCollection,
                    'total'
                )
                    ? $reportCollection->total()
                    : $reportCollection->count()); ?>

            reports
        </span>
    </div>

    <?php if($errors->any()): ?>
        <div
            class="g-card"
            style="
                margin-bottom: 14px;
                border-color: #dc2626;
                background: #fef2f2;
            "
        >
            <strong>
                Export could not be completed.
            </strong>

            <ul style="margin: 8px 0 0 18px;">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if($reportCollection->isEmpty()): ?>
        <div class="g-empty">
            <strong>No reports yet.</strong>

            Run a test and generate a report to see
            results here.
        </div>
    <?php else: ?>
        <form
            id="selected-report-export-form"
            method="POST"
        >
            <?php echo csrf_field(); ?>

            <div
                class="g-split-row"
                style="margin-bottom: 14px;"
            >
                <div class="g-actions">
                    <button
                        type="button"
                        class="g-btn"
                        id="select-all-reports"
                    >
                        Select All
                    </button>

                    <button
                        type="button"
                        class="g-btn"
                        id="clear-selected-reports"
                    >
                        Clear
                    </button>

                    <span
                        id="selected-report-count"
                        class="g-badge badge-neutral"
                    >
                        0 selected
                    </span>
                </div>

                <div class="g-actions">
                    <button
                        type="submit"
                        class="g-btn"
                        formaction="<?php echo e(route(
                                'reports.download-selected.pdf'
                            )); ?>"
                        formmethod="POST"
                    >
                        Download Selected PDF
                    </button>

                    <button
                        type="submit"
                        class="g-btn g-btn-primary"
                        formaction="<?php echo e(route(
                                'reports.download-selected.excel'
                            )); ?>"
                        formmethod="POST"
                    >
                        Download Selected Excel
                    </button>
                </div>
            </div>

            <div class="g-table-wrap">
                <table class="g-table">
                    <thead>
                        <tr>
                            <th style="width: 42px;">
                                <input
                                    type="checkbox"
                                    id="master-report-checkbox"
                                    aria-label="Select all reports"
                                >
                            </th>

                            <th>Report</th>
                            <th>Project</th>
                            <th>Test Run</th>
                            <th>Friction Level</th>
                            <th>Confidence</th>
                            <th>Generated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__currentLoopData = $reportCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $run = $report->testRun;
                                $result = $run
                                    ?->finalFrictionResult;

                                $level = $result
                                    ?->friction_level
                                    ?? 'Not predicted';

                                $badgeClass = match (
                                    $level
                                ) {
                                    'Low' => 'badge-low',
                                    'Medium' => 'badge-medium',
                                    'High' => 'badge-high',
                                    default => 'badge-neutral',
                                };
                            ?>

                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        name="report_ids[]"
                                        value="<?php echo e($report->id); ?>"
                                        class="report-selection-checkbox"
                                        aria-label="Select report <?php echo e($report->id); ?>"
                                    >
                                </td>

                                <td>
                                    <strong>
                                        <?php echo e($report->title
                                            ?? 'Untitled Report'); ?>

                                    </strong>

                                    <div class="g-table-meta">
                                        <?php echo e(\Illuminate\Support\Str::limit(
                                                $report->summary
                                                ?? 'No summary',
                                                76
                                            )); ?>

                                    </div>
                                </td>

                                <td>
                                    <?php echo e($run?->project?->name
                                        ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <?php echo e($run?->run_code
                                        ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <span
                                        class="g-badge <?php echo e($badgeClass); ?>"
                                    >
                                        <?php echo e($level); ?>

                                    </span>
                                </td>

                                <td>
                                    <?php echo e($result?->confidence_score
                                        !== null
                                            ? number_format(
                                                $result
                                                    ->confidence_score
                                                * 100,
                                                1
                                            ) . '%'
                                            : 'N/A'); ?>

                                </td>

                                <td>
                                    <?php echo e(optional(
                                            $report->generated_at
                                        )->format(
                                            'Y-m-d H:i'
                                        )
                                        ?? 'N/A'); ?>

                                </td>

                                <td>
                                    <div class="g-actions">
                                        <a
                                            class="g-btn g-btn-primary"
                                            href="<?php echo e(route(
                                                    'reports.show',
                                                    $report
                                                )); ?>"
                                        >
                                            View
                                        </a>

                                        <a
                                            class="g-btn"
                                            href="<?php echo e(route(
                                                    'reports.download.pdf',
                                                    $report
                                                )); ?>"
                                        >
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </form>

        <div class="g-pager">
            <?php echo e($reportCollection->links()); ?>

        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const masterCheckbox = document.getElementById(
        'master-report-checkbox'
    );

    const selectAllButton = document.getElementById(
        'select-all-reports'
    );

    const clearButton = document.getElementById(
        'clear-selected-reports'
    );

    const countBadge = document.getElementById(
        'selected-report-count'
    );

    const exportForm = document.getElementById(
        'selected-report-export-form'
    );

    const reportCheckboxes = Array.from(
        document.querySelectorAll(
            '.report-selection-checkbox'
        )
    );

    function updateSelectionState() {
        const selectedCount = reportCheckboxes.filter(
            checkbox => checkbox.checked
        ).length;

        countBadge.textContent =
            selectedCount + ' selected';

        masterCheckbox.checked =
            reportCheckboxes.length > 0
            && selectedCount === reportCheckboxes.length;

        masterCheckbox.indeterminate =
            selectedCount > 0
            && selectedCount < reportCheckboxes.length;
    }

    masterCheckbox.addEventListener(
        'change',
        function () {
            reportCheckboxes.forEach(
                checkbox => {
                    checkbox.checked =
                        masterCheckbox.checked;
                }
            );

            updateSelectionState();
        }
    );

    selectAllButton.addEventListener(
        'click',
        function () {
            reportCheckboxes.forEach(
                checkbox => {
                    checkbox.checked = true;
                }
            );

            updateSelectionState();
        }
    );

    clearButton.addEventListener(
        'click',
        function () {
            reportCheckboxes.forEach(
                checkbox => {
                    checkbox.checked = false;
                }
            );

            updateSelectionState();
        }
    );

    reportCheckboxes.forEach(
        checkbox => {
            checkbox.addEventListener(
                'change',
                updateSelectionState
            );
        }
    );

    exportForm.addEventListener(
        'submit',
        function (event) {
            const hasSelection =
                reportCheckboxes.some(
                    checkbox => checkbox.checked
                );

            if (!hasSelection) {
                event.preventDefault();

                alert(
                    'Please select at least one report before exporting.'
                );
            }
        }
    );

    updateSelectionState();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/reports/index.blade.php ENDPATH**/ ?>