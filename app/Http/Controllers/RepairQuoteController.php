<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RepairOrder;
use App\Models\RepairQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairQuoteController extends Controller
{
    public function create(RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);
        abort_if($repairOrder->service_mode === 'precio_definido', 403, 'Los servicios con precio definido no requieren presupuesto.');
        abort_if($repairOrder->quotes()->where('quote_type', '!=', 'closure')->where('status', '!=', 'rechazado')->exists(), 403, 'Esta orden ya tiene un presupuesto activo.');

        $repairOrder->load(['company', 'client', 'devices', 'items']);
        $items = [['type' => 'mano_obra', 'description' => 'Mano de obra', 'quantity' => 1, 'unit_price' => 0]];
        foreach ($repairOrder->items as $item) {
            $items[] = ['type' => 'servicio', 'description' => $item->description, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price];
        }

        return view('repair-quotes.create', [
            'order' => $repairOrder,
            'quote' => null,
            'items' => $items,
        ]);
    }

    public function store(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);
        abort_if($repairOrder->service_mode === 'precio_definido', 403, 'Los servicios con precio definido no requieren presupuesto.');
        abort_if($repairOrder->quotes()->where('quote_type', '!=', 'closure')->where('status', '!=', 'rechazado')->exists(), 403, 'Esta orden ya tiene un presupuesto activo.');

        $data = $this->validatedData($request);
        $quote = null;

        DB::transaction(function () use ($data, $repairOrder, &$quote) {
            $totals = $this->calculateTotals($data['items'], $data['discount_amount'] ?? 0);

            $quote = RepairQuote::create([
                'company_id' => $repairOrder->company_id,
                'repair_order_id' => $repairOrder->id,
                'quote_number' => $this->nextQuoteNumber($repairOrder->company_id),
                'status' => 'emitido',
                'diagnosis_summary' => $data['diagnosis_summary'],
                'labor_amount' => $totals['labor_amount'],
                'parts_amount' => $totals['parts_amount'],
                'discount_amount' => $totals['discount_amount'],
                'net_amount' => $totals['net_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'valid_until' => $data['valid_until'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            $this->syncItems($quote, $data['items']);
            $repairOrder->update(['technical_diagnosis' => $data['diagnosis_summary']]);
            $this->setOrderStatus($repairOrder, 'presupuesto_emitido', 'Presupuesto emitido: ' . $quote->quote_number);
        });

        ActivityLog::record($request, 'quote_created', 'Presupuesto creado: ' . $quote->quote_number, $quote);

        return redirect()->route('repair-quotes.show', $quote)->with('success', 'Presupuesto creado correctamente.');
    }

    public function show(RepairQuote $repairQuote)
    {
        $this->authorizeCompanyAccess($repairQuote->repairOrder);

        return view('repair-quotes.show', [
            'quote' => $repairQuote->load(['repairOrder.client', 'repairOrder.company', 'repairOrder.devices', 'items']),
        ]);
    }

    public function print(RepairQuote $repairQuote)
    {
        $this->authorizeCompanyAccess($repairQuote->repairOrder);

        return view('repair-quotes.print', [
            'quote' => $repairQuote->load([
                'company',
                'items',
                'repairOrder.client',
                'repairOrder.company',
                'repairOrder.device',
                'repairOrder.devices',
                'repairOrder.photos',
            ]),
        ]);
    }

    public function approve(Request $request, RepairQuote $repairQuote)
    {
        $this->authorizeCompanyAccess($repairQuote->repairOrder);

        $data = $request->validate([
            'customer_response_notes' => ['nullable', 'string'],
            'customer_pickup' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $repairQuote) {
            $repairQuote->update([
                'status' => 'aprobado',
                'approved_at' => now(),
                'rejected_at' => null,
                'customer_response_notes' => $data['customer_response_notes'] ?? null,
            ]);

            if ($repairQuote->quote_type !== 'closure') {
                $this->setOrderStatus($repairQuote->repairOrder, 'en_reparacion', 'Presupuesto aprobado: ' . $repairQuote->quote_number);
            }
        });

        ActivityLog::record($request, 'quote_approved', 'Presupuesto aprobado: ' . $repairQuote->quote_number, $repairQuote);

        return redirect()->route('repair-quotes.show', $repairQuote)->with('success', 'Presupuesto aprobado.');
    }

    public function reject(Request $request, RepairQuote $repairQuote)
    {
        $this->authorizeCompanyAccess($repairQuote->repairOrder);

        $data = $request->validate([
            'customer_response_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $repairQuote) {
            $repairQuote->update([
                'status' => 'rechazado',
                'approved_at' => null,
                'rejected_at' => now(),
                'customer_response_notes' => $data['customer_response_notes'] ?? null,
            ]);

            $this->setOrderStatus($repairQuote->repairOrder, 'rechazado_por_cliente', 'Presupuesto rechazado: ' . $repairQuote->quote_number);
            $this->createClosureQuote($repairQuote, ! ($data['customer_pickup'] ?? false));
        });

        ActivityLog::record($request, 'quote_rejected', 'Presupuesto rechazado: ' . $repairQuote->quote_number, $repairQuote);

        return redirect()->route('repair-quotes.show', $repairQuote)->with('success', 'Presupuesto rechazado.');
    }

    public function destroy(Request $request, RepairQuote $repairQuote)
    {
        $this->authorizeCompanyAccess($repairQuote->repairOrder);

        $order = $repairQuote->repairOrder;
        $quoteNumber = $repairQuote->quote_number;
        $wasApproved = $repairQuote->status === 'aprobado';

        DB::transaction(function () use ($order, $repairQuote, $quoteNumber, $wasApproved) {
            RepairQuote::where('parent_quote_id', $repairQuote->id)->delete();
            $repairQuote->delete();

            if (! $order->quotes()->where('quote_type', '!=', 'closure')->exists() && ($wasApproved || in_array($order->status, ['presupuesto_emitido', 'rechazado_por_cliente'], true))) {
                $this->setOrderStatus($order, 'diagnosticado', 'Presupuesto eliminado: ' . $quoteNumber);
            }
        });

        ActivityLog::record($request, 'quote_deleted', 'Presupuesto eliminado: ' . $quoteNumber, null, $order->company_id);

        return redirect()->route('repair-orders.show', $order)->with('success', 'Presupuesto eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'diagnosis_summary' => ['required', 'string'],
            'valid_until' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:mano_obra,repuesto,servicio'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function syncItems(RepairQuote $quote, array $items): void
    {
        $quote->items()->delete();

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $quote->items()->create([
                'type' => $item['type'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ]);
        }
    }

    private function calculateTotals(array $items, float|int $discount): array
    {
        $labor = 0;
        $parts = 0;
        $services = 0;

        foreach ($items as $item) {
            $total = (int) $item['quantity'] * (float) $item['unit_price'];

            if ($item['type'] === 'mano_obra') {
                $labor += $total;
            } elseif ($item['type'] === 'repuesto') {
                $parts += $total;
            } else {
                $services += $total;
            }
        }

        $discountAmount = (float) $discount;

        $netAmount = max(($labor + $parts + $services) - $discountAmount, 0);
        $taxAmount = round($netAmount * 0.19);

        return [
            'labor_amount' => $labor + $services,
            'parts_amount' => $parts,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $netAmount + $taxAmount,
        ];
    }

    private function nextQuoteNumber(int $companyId): string
    {
        $nextNumber = RepairQuote::where('company_id', $companyId)->count() + 1;

        return 'PRES-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function createClosureQuote(RepairQuote $source, bool $includeDelivery = true): void
    {
        if ($source->quote_type === 'closure' || RepairQuote::where('parent_quote_id', $source->id)->exists()) {
            return;
        }

        $source->loadMissing(['repairOrder.company', 'repairOrder.items']);
        $gross = (float) ($source->repairOrder->company->diagnostic_fee_gross ?? 20000);
        $items = [['type' => 'servicio', 'description' => 'Diagnóstico (cobro por presupuesto no aprobado)', 'quantity' => 1, 'unit_price' => round($gross / 1.19)]];
        $pickup = $source->repairOrder->items->first(fn ($item) => str_contains(mb_strtolower($item->description), 'retiro'));
        if ($pickup) {
            $items[] = ['type' => 'servicio', 'description' => 'Retiro a domicilio', 'quantity' => (int) $pickup->quantity, 'unit_price' => (float) $pickup->unit_price];
        }
        $delivery = $source->repairOrder->items->first(fn ($item) => str_contains(mb_strtolower($item->description), 'entrega'));
        if ($includeDelivery && $delivery) {
            $items[] = ['type' => 'servicio', 'description' => 'Entrega a domicilio', 'quantity' => (int) $delivery->quantity, 'unit_price' => (float) $delivery->unit_price];
        }
        $totals = $this->calculateTotals($items, 0);
        $closure = RepairQuote::create(['company_id'=>$source->company_id,'repair_order_id'=>$source->repair_order_id,'quote_number'=>$this->nextQuoteNumber($source->company_id),'status'=>'emitido','quote_type'=>'closure','parent_quote_id'=>$source->id,'diagnosis_summary'=>'Liquidación por presupuesto de reparación no aprobado.','labor_amount'=>$totals['labor_amount'],'parts_amount'=>0,'discount_amount'=>0,'net_amount'=>$totals['net_amount'],'tax_amount'=>$totals['tax_amount'],'total_amount'=>$totals['total_amount']]);
        $this->syncItems($closure, $items);
    }

    private function setOrderStatus(RepairOrder $order, string $status, string $comment): void
    {
        $order->update(['status' => $status]);
        $order->statusLogs()->create([
            'status' => $status,
            'comment' => $comment,
        ]);
    }

    private function authorizeCompanyAccess(RepairOrder $order): void
    {
        $user = request()->user();

        if ($user->role !== 'super_admin' && $order->company_id !== $user->company_id) {
            abort(403);
        }
    }
}
