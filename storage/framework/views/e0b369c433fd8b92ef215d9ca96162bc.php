<?php $__env->startSection('title', 'Investments'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal00a2e3c817942d1565d2933416f28c6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal00a2e3c817942d1565d2933416f28c6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-head','data' => ['title' => 'Projects & investors','sub' => 'Funding projects and the contributions behind them.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Projects & investors','sub' => 'Funding projects and the contributions behind them.']); ?>
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

<div class="grid g3" style="margin-bottom:18px">
<?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php $pct = (float) bcmul(bcdiv((string) $p->total_raised, (string) $p->target_amount, 4), '100', 2); ?>
    <div class="card"><div class="card-b">
        <div style="display:flex;justify-content:space-between;gap:8px"><h3><?php echo e($p->property->name); ?></h3><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $p->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></div>
        <div class="muted small" style="margin:2px 0 12px"><?php echo e($p->property->location); ?></div>
        <div class="bar"><i style="width:<?php echo e(min(100, $pct)); ?>%"></i></div>
        <div style="display:flex;justify-content:space-between;margin:6px 0 12px" class="small"><span><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $p->total_raised]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p->total_raised)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></span><span class="muted"><?php echo e($pct); ?>% of <?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $p->target_amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p->target_amount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></span></div>
        <dl class="kv small"><dt>Minimum</dt><dd><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $p->min_investment]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p->min_investment)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></dd><dt>Management fee</dt><dd><?php echo e($p->management_fee_percent); ?>%</dd><dt>Investors</dt><dd><?php echo e($p->investments_count); ?></dd></dl>
        <div class="row-actions" style="margin-top:12px;justify-content:flex-start">
            <a class="btn ghost sm" href="<?php echo e(route('admin.investments.projects.edit', $p)); ?>">Edit</a>
            <?php if($admin && $p->investments_count === 0): ?>
                <form method="POST" action="<?php echo e(route('admin.investments.projects.destroy', $p)); ?>" data-confirm="Delete this project?"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn danger sm" type="submit">Delete</button></form>
            <?php endif; ?>
        </div>
    </div></div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card" style="grid-column:1/-1"><?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>No projects yet — create one below. <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?></div>
<?php endif; ?>
</div>

<div class="grid split" style="margin-bottom:18px">
    <div class="card">
        <div class="card-h"><h2>Investor positions</h2></div>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Investor</th><th>Property</th><th class="num">Invested</th><th class="num">Ownership</th><th></th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $investments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr><td><?php echo e($i->user->name); ?></td><td><?php echo e($i->project->property->name); ?></td><td><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $i->total_invested]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($i->total_invested)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></td><td class="num"><?php echo e(rtrim(rtrim($i->ownership_percent, '0'), '.')); ?>%</td>
                    <td><div class="row-actions">
                    <?php if($admin): ?>
                        <a class="btn ghost sm" href="<?php echo e(route('admin.investments.investors.edit', $i)); ?>">Top up</a>
                        <form method="POST" action="<?php echo e(route('admin.investments.investors.destroy', $i)); ?>" data-confirm="Reverse this investment and refund the wallet?"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn danger sm" type="submit">Reverse</button></form>
                    <?php endif; ?>
                    </div></td></tr>
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
            </tbody></table></div>
        <?php echo e($investments->links()); ?>

    </div>

    <div class="grid">
        <div class="card">
            <div class="card-h"><h2><?php echo e($editProject ? 'Edit project' : 'New project'); ?></h2><?php if($editProject): ?><a class="small" href="<?php echo e(route('admin.investments.index')); ?>">Cancel</a><?php endif; ?></div>
            <div class="card-b">
                <form method="POST" action="<?php echo e($editProject ? route('admin.investments.projects.update', $editProject) : route('admin.investments.projects.store')); ?>"><?php echo csrf_field(); ?> <?php if($editProject): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
                    <?php if($editProject): ?>
                        <p class="small muted">Property: <strong><?php echo e($editProject->property->name); ?></strong></p>
                        <?php if($editProject->investments_count ?? $editProject->investments()->exists()): ?><div class="callout" style="margin-bottom:12px">Terms are frozen once investors have contributed.</div><?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'status','label' => 'Status','type' => 'select','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','label' => 'Status','type' => 'select','required' => true]); ?>
                            <option value="<?php echo e($editProject->status->value); ?>" selected><?php echo e(ucfirst($editProject->status->value)); ?> (current)</option>
                            <?php $__currentLoopData = $editProject->status->allowedNext(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->value); ?>"><?php echo e(ucfirst($s->value)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'property_id','label' => 'Property','type' => 'select','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'property_id','label' => 'Property','type' => 'select','required' => true]); ?>
                            <option value="">Select…</option><?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($pr->id); ?>" <?php if(old('property_id') === $pr->id): echo 'selected'; endif; ?>><?php echo e($pr->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                    <?php endif; ?>
                    <div class="form-row">
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'target_amount','label' => 'Funding target','type' => 'number','step' => '0.01','value' => $editProject?->target_amount,'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'target_amount','label' => 'Funding target','type' => 'number','step' => '0.01','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->target_amount),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'min_investment','label' => 'Minimum','type' => 'number','step' => '0.01','value' => $editProject?->min_investment,'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'min_investment','label' => 'Minimum','type' => 'number','step' => '0.01','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->min_investment),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                    </div>
                    <div class="form-row">
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'management_fee_percent','label' => 'Mgmt fee %','type' => 'number','step' => '0.01','value' => $editProject?->management_fee_percent,'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'management_fee_percent','label' => 'Mgmt fee %','type' => 'number','step' => '0.01','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->management_fee_percent),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'expected_roi','label' => 'Expected ROI %','type' => 'number','step' => '0.01','value' => $editProject?->expected_roi,'hint' => 'Informational only; never a guarantee.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'expected_roi','label' => 'Expected ROI %','type' => 'number','step' => '0.01','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->expected_roi),'hint' => 'Informational only; never a guarantee.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                    </div>
                    <div class="form-row">
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'start_date','label' => 'Start','type' => 'date','value' => $editProject?->start_date?->format('Y-m-d'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'start_date','label' => 'Start','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->start_date?->format('Y-m-d')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'end_date','label' => 'End','type' => 'date','value' => $editProject?->end_date?->format('Y-m-d')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'end_date','label' => 'End','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editProject?->end_date?->format('Y-m-d'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                    </div>
                    <button class="btn" type="submit"><?php echo e($editProject ? 'Save project' : 'Create project'); ?></button>
                </form>
            </div>
        </div>

        <?php if($admin): ?>
        <div class="card">
            <div class="card-h"><h2><?php echo e($editInvestment ? 'Top up position' : 'Invest on behalf'); ?></h2><?php if($editInvestment): ?><a class="small" href="<?php echo e(route('admin.investments.index')); ?>">Cancel</a><?php endif; ?></div>
            <div class="card-b">
                <?php if($editInvestment): ?>
                    <p class="small muted"><?php echo e($editInvestment->user->name); ?> · <?php echo e($editInvestment->project->property->name); ?><br>Currently <?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['value' => $editInvestment->total_invested]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editInvestment->total_invested)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>. The difference is debited from their wallet.</p>
                    <form method="POST" action="<?php echo e(route('admin.investments.investors.update', $editInvestment)); ?>" data-confirm="Debit the investor wallet?"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'total_invested','label' => 'New total','type' => 'number','step' => '0.01','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'total_invested','label' => 'New total','type' => 'number','step' => '0.01','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <button class="btn" type="submit">Update</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?php echo e(route('admin.investments.investors.store')); ?>" data-confirm="Debit the investor wallet?"><?php echo csrf_field(); ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'user_id','label' => 'Investor','type' => 'select','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user_id','label' => 'Investor','type' => 'select','required' => true]); ?><option value="">Select…</option><?php $__currentLoopData = $investors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($u->id); ?>" <?php if(old('user_id') === $u->id): echo 'selected'; endif; ?>><?php echo e($u->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'investment_project_id','label' => 'Project','type' => 'select','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'investment_project_id','label' => 'Project','type' => 'select','required' => true]); ?><option value="">Select…</option><?php $__currentLoopData = $projects->where('status', \App\Enums\ProjectStatus::Open); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($p->id); ?>" <?php if(old('investment_project_id') === $p->id): echo 'selected'; endif; ?>><?php echo e($p->property->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'amount','label' => 'Amount','type' => 'number','step' => '0.01','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'amount','label' => 'Amount','type' => 'number','step' => '0.01','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
                        <button class="btn" type="submit">Record contribution</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/admin/investments.blade.php ENDPATH**/ ?>