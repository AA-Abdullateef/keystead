<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['value']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['value']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $v = $value instanceof \BackedEnum ? $value->value : (string) $value;
    $cls = match ($v) {
        'active', 'open', 'completed', 'confirmed', 'distributed', 'credit', 'funded' => 'b-ok',
        'pending', 'distributing', 'investment' => 'b-warn',
        'overdue', 'rejected', 'defaulted', 'suspended', 'failed', 'debit' => 'b-bad',
        'cancelled', 'closed', 'inactive' => 'b-mute',
        default => 'b-info',
    };
?>
<span class="badge <?php echo e($cls); ?>"><?php echo e(str_replace('_', ' ', $v)); ?></span>
<?php /**PATH C:\xampp\htdocs\portfolio\keystead\resources\views/components/status.blade.php ENDPATH**/ ?>