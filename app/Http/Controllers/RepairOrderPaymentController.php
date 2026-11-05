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

        if (! isset($data['repair_quote_id']) && $activeQuote = $repairOrder->activePayableQuote()) {
            $data['repair_quote_id'] = $activeQuote->id;
        }

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
            'payment' => $payment->load(['repairQuote', 'device', 'creator']),
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
        if ($payment->payment_type === 'pago_final') {
            $this->finalizeOrder($repairOrder, 'Pago final registrado.');

            return;
        }

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

        $this->finalizeOrder($repairOrder, 'Presupuesto aprobado pagado completamente: '.$quote->quote_number);
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
        $payment->load(['company', 'repairOrder.client', 'repairOrder.devices', 'repairQuote', 'device', 'creator']);
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
