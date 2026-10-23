@props(['value'])
@php
    $v = $value instanceof \BackedEnum ? $value->value : (string) $value;
    $cls = match ($v) {
        'active', 'open', 'completed', 'confirmed', 'distributed', 'credit', 'funded' => 'b-ok',
        'pending', 'distributing', 'investment' => 'b-warn',
        'overdue', 'rejected', 'defaulted', 'suspended', 'failed', 'debit' => 'b-bad',
        'cancelled', 'closed', 'inactive' => 'b-mute',
        default => 'b-info',
    };
@endphp
<span class="badge {{ $cls }}">{{ str_replace('_', ' ', $v) }}</span>
