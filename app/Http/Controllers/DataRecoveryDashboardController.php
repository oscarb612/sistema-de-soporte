<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DataRecoveryCase;
use App\Models\DataRecoveryPayment;
use App\Models\DataRecoveryQuote;
use Illuminate\Http\Request;

class DataRecoveryDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $companyId = $request->user()->role === 'super_admin' ? null : $request->user()->company_id;
        $scope = fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query;

        return view('data-recovery.dashboard', [
            'clientsCount' => $scope(Client::query())->count(),
            'casesCount' => $scope(DataRecoveryCase::query())->count(),
            'activeCasesCount' => $scope(DataRecoveryCase::whereNotIn('status', ['entregado', 'rechazado', 'no_recuperable']))->count(),
            'approvedAmount' => $scope(DataRecoveryQuote::where('status', 'aprobado'))->sum('total_amount'),
            'paidAmount' => $scope(DataRecoveryPayment::query())->sum('amount'),
            'recentCases' => $scope(DataRecoveryCase::with('client'))->latest()->take(8)->get(),
        ]);
    }
}
