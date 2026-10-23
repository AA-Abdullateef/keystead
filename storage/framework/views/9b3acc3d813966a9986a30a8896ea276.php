<?php $__env->startSection('title', 'Ledger'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal00a2e3c817942d1565d2933416f28c6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal00a2e3c817942d1565d2933416f28c6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-head','data' => ['title' => 'Ledger','sub' => 'Append-only record of every wallet movement.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Ledger','sub' => 'Append-only record of every wallet movement.']); ?>
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
    <form class="filters" method="GET" action="<?php echo e(route('admin.transactions.index')); ?>">
        <div class="field"><label>Type</label><select name="type"><option value="">All</option><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($t->value); ?>" <?php if(request('type') === $t->value): echo 'selected'; endif; ?>><?php echo e(str_replace('_', ' ', $t->value)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="field"><label>Status</label><select name="status"><option value="">All</option><?php $__currentLoopData = ['pending','completed','rejected','failed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s); ?>" <?php if(request('status') === $s): echo 'selected'; endif; ?>><?php echo e(ucfirst($s)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="field"><label>Investor</label><select name="user_id"><option value="">All</option><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($u->id); ?>" <?php if(request('user_id') === $u->id): echo 'selected'; endif; ?>><?php echo e($u->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>When</th><th>Investor</th><th>Type</th><th>Status</th><th class="num">Amount</th><th class="num">Balance after</th><th></th></tr></thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr><td class="muted small"><?php echo e($t->created_at->format('d M Y H:i')); ?></td><td><?php echo e($t->user?->name); ?></td><td><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $t->type]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($t->type)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></td><td><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $t->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($t->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></td>
                <td class="num <?php echo e($t->direction->value === 'credit' ? 'pos' : 'neg'); ?>"><?php echo e($t->direction->value === 'credit' ? '+' : '−'); ?><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $t->amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($t->amount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></td><td><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $t->balance_after]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($t->balance_after)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></td>
                <td><a class="btn ghost sm" href="<?php echo e(route('admin.transactions.show', $t->id)); ?>">Open</a></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="7"><?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
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
        </tbody></table></div><?php echo e($transactions->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/admin/transactions.blade.php ENDPATH**/ ?>