@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'step' => null])
<div class="field">
    <label for="{{ $name }}">{{ $label }}@if($required) <span class="muted">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" @required($required)>{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($required)>{{ $slot }}</select>
    @else
        <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @if($step) step="{{ $step }}" @endif @required($required) {{ $attributes }}>
    @endif
    @error($name)<div class="err">{{ $message }}</div>@enderror
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
