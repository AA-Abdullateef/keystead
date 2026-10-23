<?php $__env->startSection('title', 'Audit trail'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal00a2e3c817942d1565d2933416f28c6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal00a2e3c817942d1565d2933416f28c6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-head','data' => ['title' => 'Audit trail','sub' => 'Append-only record of who changed what. Entries cannot be edited or deleted, even by administrators.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Audit trail','sub' => 'Append-only record of who changed what. Entries cannot be edited or deleted, even by administrators.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal00a2e3c817942d1565d2933416f28c6a)): ?>
<?php $attributes = $__attributesOriginal00a2e3c817942d1565d2933416f28c6a; ?>
<?php unset($__attributesOriginal00a2e3c817942d1565d2933416f28c6a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal00a2e3c817942d1565d2933416f28c6a)): ?>
<?php $component = $__componentOriginal00a2e3c817942d1565d2933416f28c6a; ?>
<?php unset($__componentOriginal00a2e3c817942d1565d2933416f28c6a); ?>
<?php endif; ?>
<div class="card">
    <form class="filters" method="GET" action="<?php echo e(route('admin.audit.index')); ?>">
        <div class="field"><label>Event prefix</label><input name="event" value="<?php echo e(request('event')); ?>" placeholder="e.g. wallet. or auth."></div>
        <div class="field"><label>Actor</label><input name="actor" value="<?php echo e(request('actor')); ?>"></div>
        <div class="field"><label>Subject ID</label><input name="subject_id" value="<?php echo e(request('subject_id')); ?>"></div>
        <div class="field"><label>From</label><input type="date" name="from" value="<?php echo e(request('from')); ?>"></div>
        <div class="field"><label>To</label><input type="date" name="to" value="<?php echo e(request('to')); ?>"></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>When</th><th>Actor</th><th>Event</th><th>Subject</th><th>Details</th></tr></thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="muted small"><?php echo e(\Illuminate\Support\Carbon::parse($log->occurred_at)->format('d M Y H:i:s')); ?></td>
                <td><?php echo e($log->actor_label); ?> <span class="muted small"><?php echo e($log->actor_role); ?></span></td>
                <td><code><?php echo e($log->event); ?></code></td>
                <td class="small"><?php echo e($log->subject_type); ?><br><span class="muted"><?php echo e($log->subject_id); ?></span></td>
                <td class="small"><?php echo e($log->description); ?>

                    <?php if($log->old_values || $log->new_values): ?>
                        <details><summary class="muted">Changes</summary>
                            <?php if($log->old_values): ?><pre>before: <?php echo e(json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre><?php endif; ?>
                            <?php if($log->new_values): ?><pre>after: <?php echo e(json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre><?php endif; ?>
                        </details>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="5"><?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?></td></tr> <?php endif; ?>
        </tbody></table></div><?php echo e($logs->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/admin/audit.blade.php ENDPATH**/ ?>