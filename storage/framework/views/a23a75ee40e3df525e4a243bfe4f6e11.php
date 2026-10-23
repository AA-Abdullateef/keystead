<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> · <?php echo e(config('app.name')); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/keystead.css')); ?>">
</head>
<body>
<?php
    $user = auth()->user();
    $is = fn (string $p) => request()->routeIs($p) ? 'on' : '';
    $groups = [
        'Investments' => ['admin.properties.*' => ['Properties', 'admin.properties.index'], 'admin.investments.*' => ['Projects & investors', 'admin.investments.index'], 'admin.rental.*' => ['Rental income', 'admin.rental.index'], 'admin.expenses.*' => ['Expenses', 'admin.expenses.index']],
        'Finance' => ['admin.profit.*' => ['Profit distribution', 'admin.profit.distribution'], 'admin.earnings.*' => ['Earnings', 'admin.earnings.index'], 'admin.withdrawals.*' => ['Withdrawals', 'admin.withdrawals.index'], 'admin.transactions.*' => ['Ledger', 'admin.transactions.index']],
        'Lending' => ['admin.loan.*' => ['Loan products', 'admin.loan.projects'], 'admin.loans.*' => ['Mortgage & property loans', 'admin.loans.index', 'mortgage']],
    ];
?>
<header class="topbar">
    <div class="wrap">
        <a class="brand" href="<?php echo e(route('admin.index')); ?>">
            <svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="7" fill="#f6f2e9"/><path d="M7 15 16 7l9 8v10h-6v-6h-6v6H7z" fill="#1f4d3a"/><circle cx="16" cy="13.5" r="1.8" fill="#b08d57"/></svg>
            <?php echo e(config('app.name')); ?>

        </a>
        <button class="burger" type="button" aria-label="Menu">☰</button>
        <nav class="nav">
            <a class="<?php echo e($is('admin.index')); ?>" href="<?php echo e(route('admin.index')); ?>">Overview</a>
            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="dd <?php echo e(collect(array_keys($items))->contains(fn ($p) => request()->routeIs($p)) ? 'on' : ''); ?>">
                    <button type="button"><?php echo e($label); ?></button>
                    <div class="dd-menu">
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pattern => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a class="<?php echo e($is($pattern)); ?>" href="<?php echo e(route($item[1], $item[2] ?? [])); ?>"><?php echo e($item[0]); ?></a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <a class="<?php echo e($is('admin.users.*') . $is('admin.user.*')); ?>" href="<?php echo e(route('admin.users.index')); ?>">People</a>
            <?php if($user->isAdmin()): ?><a class="<?php echo e($is('admin.audit.*')); ?>" href="<?php echo e(route('admin.audit.index')); ?>">Audit</a><?php endif; ?>
        </nav>
        <div class="who">
            <span><?php echo e($user->name); ?> <span class="role"><?php echo e($user->isAdmin() ? 'admin' : 'manager'); ?></span></span>
            <form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?> <button class="btn ghost sm" style="color:#fff;border-color:rgba(255,255,255,.4)">Sign out</button></form>
        </div>
    </div>
</header>

<main>
    <div class="wrap">
        <?php echo $__env->make('admin.partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</main>
<script src="<?php echo e(asset('assets/js/keystead.js')); ?>" defer></script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/layouts/app.blade.php ENDPATH**/ ?>