<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RepairOrder;
use App\Models\RepairOrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RepairOrderPaymentController extends Controller
{
    public function store(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        $deviceIds = $repairOrder->devices()->pluck('devices.id')->all();
        if ($deviceIds === []) {
            $deviceIds = [$repairOrder->device_id];
        }

        $quoteIds = $repairOrder->quotes()->pluck('id')->all();

        $data = $request->validate([
            'device_id' => ['nullable', 'integer', Rule::in($deviceIds)],
            'repair_quote_id' => ['nullable', 'integer', Rule::in($quoteIds)],
            'payment_type' => ['required', Rule::in(array_keys(RepairOrderPayment::TYPES))],
            'payment_method' => ['required', Rule::in(array_keys(RepairOrderPayment::METHODS))],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = DB::transaction(function () use ($data, $repairOrder, $request) {
            $payment = RepairOrderPayment::create([
                ...$data,
                'company_id' => $repairOrder->company_id,
                'repair_order_id' => $repairOrder->id,
                'created_by' => $request->user()->id,
            ]);

            $this->finalizeIfFullyPaid($repairOrder, $payment);

            return $payment;
        });

        ActivityLog::record(
            $request,
            'repair_order_payment_created',
            'Abono registrado en orden ' . $repairOrder->code . ': $' . number_format((float) $payment->amount, 0, ',', '.'),
            $repairOrder
        );

        return redirect()->route('repair-orders.show', $repairOrder)->with('success', 'Abono registrado correctamente.');
    }

    public function destroy(Request $request, RepairOrder $repairOrder, RepairOrderPayment $payment)
    {
        $this->authorizeCompanyAccess($repairOrder);

        if ($payment->repair_order_id !== $repairOrder->id) {
            abort(404);
        }

        $amount = $payment->amount;
        $payment->delete();

        ActivityLog::record(
            $request,
            'repair_order_payment_deleted',
            'Abono eliminado de orden ' . $repairOrder->code . ': $' . number_format((float) $amount, 0, ',', '.'),
            $repairOrder
        );

        return redirect()->route('repair-orders.show', $repairOrder)->with('success', 'Abono eliminado correctamente.');
    }

    private function authorizeCompanyAccess(RepairOrder $repairOrder): void
    {
        $user = request()->user();

        if ($user->role !== 'super_admin' && $repairOrder->company_id !== $user->company_id) {
            abort(403);
        }
    }

    private function finalizeIfFullyPaid(RepairOrder $repairOrder, RepairOrderPayment $payment): void
    {
        if (! $payment->repair_quote_id) {
            return;
        }

        $quote = $repairOrder->quotes()
            ->whereKey($payment->repair_quote_id)
            ->where('quote_type', '!=', 'closure')
            ->where('status', 'aprobado')
            ->first();

        if (! $quote || (float) $quote->payments()->sum('amount') < (float) $quote->total_amount) {
            return;
        }

        if ($repairOrder->status !== 'finalizado') {
            $repairOrder->update(['status' => 'finalizado']);
            $repairOrder->statusLogs()->create([
                'user_id' => request()->user()->id,
                'status' => 'finalizado',
                'comment' => 'Presupuesto aprobado pagado completamente: '.$quote->quote_number,
            ]);
        }
    }
}
