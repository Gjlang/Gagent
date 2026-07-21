<?php $__env->startSection('title', 'Comparisons'); ?>
<?php $__env->startSection('kicker', 'Improvement Tracking'); ?>

<?php $__env->startSection('content'); ?>
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
    <?php if($comparisons->isEmpty()): ?>
        <div class="g-empty">
            <strong>No saved comparisons yet.</strong>

            Open a website report and select
            “Retest Website and Compare” to create one.
        </div>
    <?php else: ?>
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
                    <?php $__currentLoopData = $comparisons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
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
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?php echo e($item->project?->name ?? 'N/A'); ?>

                                </strong>
                            </td>

                            <td>
                                <?php echo e($item->beforeRun?->run_code ?? 'N/A'); ?>

                            </td>

                            <td>
                                <span class="g-badge <?php echo e($beforeBadgeClass); ?>">
                                    <?php echo e($beforeLevel); ?>

                                </span>
                            </td>

                            <td>
                                <?php echo e($item->afterRun?->run_code ?? 'N/A'); ?>

                            </td>

                            <td>
                                <span class="g-badge <?php echo e($afterBadgeClass); ?>">
                                    <?php echo e($afterLevel); ?>

                                </span>
                            </td>

                            <td>
                                <?php echo e(optional($item->created_at)
                                        ->format('Y-m-d H:i')); ?>

                            </td>

                            <td>
                                <?php if($item->llm_generated_at): ?>
                                    <span class="g-badge badge-low">
                                        Generated
                                    </span>

                                    <div
                                        class="g-muted g-small"
                                        style="margin-top: 5px;"
                                    >
                                        <?php echo e(optional(
                                                $item->llm_generated_at
                                            )->format('Y-m-d H:i')); ?>

                                    </div>
                                <?php else: ?>
                                    <span class="g-badge badge-neutral">
                                        Not Generated
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    class="g-btn g-btn-primary"
                                    href="<?php echo e(route(
                                            'comparisons.show',
                                            $item
                                        )); ?>"
                                >
                                    Show Comparison
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            <?php echo e($comparisons->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\FYP\GAgent\GAgent\backend-laravel\resources\views/comparisons/index.blade.php ENDPATH**/ ?>