<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RepairOrder;
use App\Models\RepairOrderPayment;
use App\Mail\RepairOrderPaymentReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RepairOrderPaymentController extends Controller
{
    public function store(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        $deviceIds = $repairOrder->devices()->pluck('devices.id')->all();
        if ($deviceIds === []) {
            $deviceIds = [$repairOrder->device_id];
        }

        $availableQuotes = $repairOrder->quotes()->whereIn('status', ['emitido', 'aprobado'])->get();
        $quoteIds = $availableQuotes->pluck('id')->all();

        $data = $request->validate([
            'device_id' => ['nullable', 'integer', Rule::in($deviceIds)],
            'repair_quote_id' => ['nullable', 'integer', Rule::in($quoteIds)],
            'payment_type' => ['required', Rule::in(array_keys(RepairOrderPayment::TYPES))],
            'payment_method' => ['required', Rule::in(array_keys(RepairOrderPayment::METHODS))],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $allocations = collect($data['allocations'] ?? [])
            ->filter(fn ($amount) => (float) $amount > 0)
            ->mapWithKeys(fn ($amount, $quoteId) => [(int) $quoteId => (float) $amount]);

        if ($allocations->isEmpty() && isset($data['repair_quote_id'])) {
            $allocations = collect([(int) $data['repair_quote_id'] => (float) $data['amount']]);
        }

        if ($repairOrder->service_mode === 'diagnostico') {
            if ($allocations->isEmpty() || $allocations->keys()->diff($quoteIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['allocations' => 'Debes distribuir el abono entre los presupuestos disponibles.']);
            }
            if (abs($allocations->sum() - (float) $data['amount']) > 0.009) {
                throw ValidationException::withMessages(['allocations' => 'La distribución debe ser igual al monto recibido.']);
            }
            foreach ($availableQuotes as $quote) {
                if ((float) $allocations->get($quote->id, 0) > $quote->pendingBalance() + 0.009) {
                    throw ValidationException::withMessages(['allocations' => "El monto asignado a {$quote->quote_number} supera su saldo pendiente."]);
                }
            }
        }

        unset($data['allocations']);
        $data['repair_quote_id'] = $allocations->count() === 1 ? $allocations->keys()->first() : null;

        $payment = DB::transaction(function () use ($data, $allocations, $repairOrder, $request) {
            $payment = RepairOrderPayment::create([
                ...$data,
                'company_id' => $repairOrder->company_id,
                'repair_order_id' => $repairOrder->id,
                'created_by' => $request->user()->id,
            ]);

            foreach ($allocations as $quoteId => $amount) {
                $payment->allocations()->create(['repair_quote_id' => $quoteId, 'amount' => $amount]);
            }

            $this->finalizeIfFullyPaid($repairOrder, $payment);

            return $payment;
        });

        ActivityLog::record(
            $request,
            'repair_order_payment_created',
            'Abono registrado en orden ' . $repairOrder->code . ': $' . number_format((float) $payment->amount, 0, ',', '.'),
            $repairOrder
        );

        $message = 'Abono registrado correctamente.';
        try {
            if ($this->sendPaymentEmail($payment, $request)) {
                $message .= ' Comprobante de abono enviado a '.$repairOrder->client->email.'.';
            }
        } catch (Throwable $error) {
            report($error);
            $message .= ' El abono fue guardado, pero no fue posible enviar el correo.';
        }

        return redirect()->route('repair-orders.show', $repairOrder)->with('success', $message);
    }

    public function print(RepairOrder $repairOrder, RepairOrderPayment $payment)
    {
        $this->authorizeCompanyAccess($repairOrder);
        abort_unless($payment->repair_order_id === $repairOrder->id, 404);

        return view('repair-orders.payment-print', [
            'order' => $repairOrder->load(['company', 'client', 'devices']),
            'payment' => $payment->load(['repairQuote', 'allocations.quote', 'device', 'creator']),
        ]);
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
        $repairOrder->unsetRelation('payments');
        if ($repairOrder->pendingBalance() > 0) {
            return;
        }

        $this->finalizeOrder($repairOrder, $repairOrder->service_mode === 'diagnostico'
            ? 'Todos los presupuestos cobrables fueron pagados completamente.'
            : 'Servicio pagado completamente.');
    }

    private function finalizeOrder(RepairOrder $repairOrder, string $comment): void
    {
        if ($repairOrder->status === 'finalizado') {
            return;
        }

        $repairOrder->update(['status' => 'finalizado']);
        $repairOrder->statusLogs()->create([
            'user_id' => request()->user()->id,
            'status' => 'finalizado',
            'comment' => $comment,
        ]);
    }

    private function sendPaymentEmail(RepairOrderPayment $payment, Request $request): bool
    {
        $payment->load(['company', 'repairOrder.client', 'repairOrder.devices', 'repairOrder.quotes', 'repairOrder.payments', 'repairQuote', 'allocations.quote', 'device', 'creator']);
        $order = $payment->repairOrder;
        $company = $payment->company;

        if (! $order->client->email) {
            return false;
        }
        if (! $company->smtp_host || ! $company->smtp_username || ! $company->smtp_password) {
            throw new \RuntimeException('La empresa no tiene completa su configuración SMTP.');
        }

        config(['mail.mailers.company_smtp' => [
            'transport' => 'smtp',
            'scheme' => $company->smtp_security === 'ssl' ? 'smtps' : null,
            'host' => $company->smtp_host,
            'port' => $company->smtp_port ?: 587,
            'username' => $company->smtp_username,
            'password' => $company->smtp_password,
            'timeout' => 30,
            'auto_tls' => $company->smtp_security !== 'none',
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]]);

        Mail::mailer('company_smtp')->to($order->client->email)->send(new RepairOrderPaymentReceived($payment));
        ActivityLog::record($request, 'repair_order_payment_emailed', 'Comprobante de abono enviado: '.$order->code, $payment, $company->id);

        return true;
    }
}
