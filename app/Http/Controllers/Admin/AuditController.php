<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->when($request->filled('event'), fn ($q) => $q->where('event', 'like', str_replace(['%', '_'], ['\\%', '\\_'], $request->string('event')->toString()) . '%'))
            ->when($request->filled('actor'), fn ($q) => $q->where('actor_label', 'like', '%' . $request->string('actor')->toString() . '%'))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->string('subject_id')->toString()))
            ->when($request->filled('from'), fn ($q) => $q->where('occurred_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('occurred_at', '<=', $request->date('to')->endOfDay()))
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('admin.audit', ['logs' => $logs]);
    }
}
