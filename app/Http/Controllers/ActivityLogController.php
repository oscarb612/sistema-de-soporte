<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $from = now()->subDays(60);

        $logs = ActivityLog::with(['company', 'user'])
            ->where('created_at', '>=', $from)
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'companies' => $user->role === 'super_admin'
                ? Company::orderBy('name')->get()
                : Company::whereKey($user->company_id)->get(),
            'actions' => ActivityLog::where('created_at', '>=', $from)
                ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'from' => $from,
        ]);
    }
}
