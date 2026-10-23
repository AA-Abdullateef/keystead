@if (session('success'))<div class="flash ok" role="status">{{ session('success') }}</div>@endif
@if (session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())
    <div class="flash bad" role="alert">
        @if ($errors->count() === 1) {{ $errors->first() }}
        @else <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </div>
@endif
