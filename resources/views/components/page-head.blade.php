@props(['title', 'sub' => null])
<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if ($sub)<p>{{ $sub }}</p>@endif
    </div>
    <div>{{ $slot }}</div>
</div>
