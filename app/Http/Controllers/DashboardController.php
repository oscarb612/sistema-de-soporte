<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\RepairOrder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $companyScope = fn ($query) => $user->role === 'super_admin'
            ? $query
            : $query->where('company_id', $user->company_id);

        return view('dashboard', [
            'companiesCount' => $user->role === 'super_admin' ? Company::count() : 1,
            'clientsCount' => $companyScope(Client::query())->count(),
            'ordersCount' => $companyScope(RepairOrder::query())->count(),
            'pendingOrdersCount' => $companyScope(RepairOrder::whereNotIn('status', ['entregado', 'cancelado']))->count(),
            'recentOrders' => $companyScope(RepairOrder::with(['company', 'client', 'device']))->latest()->take(8)->get(),
        ]);
    }
}
