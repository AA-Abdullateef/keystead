<?php if(session('success')): ?><div class="flash ok" role="status"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('status')): ?><div class="flash ok" role="status"><?php echo e(session('status')); ?></div><?php endif; ?>
<?php if($errors->any()): ?>
    <div class="flash bad" role="alert">
        <?php if($errors->count() === 1): ?> <?php echo e($errors->first()); ?>

        <?php else: ?> <ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/admin/partials/flash.blade.php ENDPATH**/ ?>