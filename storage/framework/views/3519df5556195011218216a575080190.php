<?php $__env->startSection('title', ucfirst($type->value).' loans'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal00a2e3c817942d1565d2933416f28c6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal00a2e3c817942d1565d2933416f28c6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-head','data' => ['title' => ucfirst($type->value).' loans','sub' => 'Applications and active loans. Approving keeps the initial payment; rejecting refunds it.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(ucfirst($type->value).' loans'),'sub' => 'Applications and active loans. Approving keeps the initial payment; rejecting refunds it.']); ?>
    <a class="btn ghost" href="<?php echo e(route('admin.loans.index', $type->value === 'mortgage' ? 'property' : 'mortgage')); ?>">Switch to <?php echo e($type->value === 'mortgage' ? 'property' : 'mortgage'); ?> loans</a>
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
<?php $admin = auth()->user()->isAdmin(); ?>
<div class="card">
    <form class="filters" method="GET" action="<?php echo e(route('admin.loans.index', $type->value)); ?>">
        <div class="field"><label>Status</label><select name="status"><option value="">All</option><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->value); ?>" <?php if(request('status') === $s->value): echo 'selected'; endif; ?>><?php echo e(ucfirst($s->value)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Borrower</th><th>Property</th><th>Status</th><th class="num">Loan</th><th class="num">Monthly</th><th class="num">Remaining</th><th></th></tr></thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr><td><?php echo e($l->user?->name); ?><div class="muted small"><?php echo e($l->created_at->format('d M Y')); ?></div></td><td><?php echo e($l->loanProject?->property?->name); ?></td><td><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $l->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($l->status)]); ?>
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
                <td><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $l->loan_amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($l->loan_amount)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $l->monthly_payment]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($l->monthly_payment)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $l->balance_remaining]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($l->balance_remaining)]); ?>
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
                <td><div class="row-actions"><a class="btn ghost sm" href="<?php echo e(route('admin.loans.show', [$type->value, $l->id])); ?>">Open</a>
                <?php if($admin && $l->status === \App\Enums\LoanStatus::Pending): ?>
                    <form method="POST" action="<?php echo e(route('admin.loans.approve', [$type->value, $l->id])); ?>" data-confirm="Approve this loan?"><?php echo csrf_field(); ?><button class="btn sm" type="submit">Approve</button></form>
                    <form method="POST" action="<?php echo e(route('admin.loans.reject', [$type->value, $l->id])); ?>" data-confirm="Reject and refund the initial payment?"><?php echo csrf_field(); ?><button class="btn danger sm" type="submit">Reject</button></form>
                <?php endif; ?></div></td></tr>
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
        </tbody></table></div><?php echo e($loans->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/admin/loans.blade.php ENDPATH**/ ?>