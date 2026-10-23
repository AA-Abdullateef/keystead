@props(['value' => '0', 'sign' => false])
@php $s = \App\Support\Money::fiat((string) ($value ?? '0')); @endphp
<span class="num">{{ $sign && bccomp($s, '0', 2) > 0 ? '+' : '' }}${{ number_format((float) $s, 2) }}</span>