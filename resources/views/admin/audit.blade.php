@extends('layouts.app')
@section('title', 'Audit trail')
@section('content')
<x-page-head title="Audit trail" sub="Append-only record of who changed what. Entries cannot be edited or deleted, even by administrators." />
<div class="card">
    <form class="filters" method="GET" action="{{ route('admin.audit.index') }}">
        <div class="field"><label>Event prefix</label><input name="event" value="{{ request('event') }}" placeholder="e.g. wallet. or auth."></div>
        <div class="field"><label>Actor</label><input name="actor" value="{{ request('actor') }}"></div>
        <div class="field"><label>Subject ID</label><input name="subject_id" value="{{ request('subject_id') }}"></div>
        <div class="field"><label>From</label><input type="date" name="from" value="{{ request('from') }}"></div>
        <div class="field"><label>To</label><input type="date" name="to" value="{{ request('to') }}"></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>When</th><th>Actor</th><th>Event</th><th>Subject</th><th>Details</th></tr></thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td class="muted small">{{ \Illuminate\Support\Carbon::parse($log->occurred_at)->format('d M Y H:i:s') }}</td>
                <td>{{ $log->actor_label }} <span class="muted small">{{ $log->actor_role }}</span></td>
                <td><code>{{ $log->event }}</code></td>
                <td class="small">{{ $log->subject_type }}<br><span class="muted">{{ $log->subject_id }}</span></td>
                <td class="small">{{ $log->description }}
                    @if ($log->old_values || $log->new_values)
                        <details><summary class="muted">Changes</summary>
                            @if ($log->old_values)<pre>before: {{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>@endif
                            @if ($log->new_values)<pre>after: {{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>@endif
                        </details>
                    @endif
                </td>
            </tr>
        @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
        </tbody></table></div>{{ $logs->links() }}
</div>
@endsection
