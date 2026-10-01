<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('module'), fn ($q) => $q->module($request->string('module')->value()))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->value()))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'filters' => $request->only('q', 'module', 'action', 'user_id', 'from', 'to'),
            'stats' => [
                'today' => AuditLog::query()->whereDate('created_at', now())->count(),
                'week' => AuditLog::query()->where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
                'total' => AuditLog::query()->count(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', ['log' => $auditLog]);
    }
}
