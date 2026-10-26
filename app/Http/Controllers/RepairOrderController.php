<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Device;
use App\Models\RepairOrder;
use App\Models\RepairOrderPayment;
use App\Models\Service;
use App\Mail\RepairOrderReceived;
use App\Services\OrderPhotoService;
use App\Services\BusinessDeadlineService;
use App\Rules\ValidRut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class RepairOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = RepairOrder::with(['company', 'client', 'device'])
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = mb_strtolower(trim($request->string('q')->toString()));

                $query->whereHas('client', function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(rut) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('repair-orders.index', [
            'orders' => $orders,
            'search' => trim($request->string('q')->toString()),
        ]);
    }

    public function searchSuggestions(Request $request)
    {
        $user = $request->user();
        $search = mb_strtolower(trim($request->string('q')->toString()));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $clients = Client::query()
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
            ->whereHas('repairOrders')
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(rut) LIKE ?', ["%{$search}%"]);
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'rut']);

        return response()->json($clients->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name,
            'rut' => $client->formatted_rut,
            'value' => $client->formatted_rut,
        ]));
    }

    public function create(Request $request)
    {
        return view('repair-orders.create', [
            'companies' => $this->availableCompanies($request),
            'statuses' => RepairOrder::STATUSES,
            'services' => $this->availableServices($request),
        ]);
    }

    public function store(Request $request, OrderPhotoService $photoService)
    {
        $data = $this->validatedData($request);

        $order = null;

        DB::transaction(function () use ($data, &$order) {
            $client = $this->resolveClient($data);
            $devices = $this->syncDevices($data, $client);
            $primaryDevice = $devices->first();
            $directTotals = $this->directServiceTotals($data);
            $company = Company::findOrFail($data['company_id']);

            $order = RepairOrder::create([
                'company_id' => $data['company_id'],
                'client_id' => $client->id,
                'device_id' => $primaryDevice->id,
                'code' => $this->nextCode($data['company_id']),
                'status' => $data['service_mode'] === 'precio_definido' ? 'servicio_aceptado' : $data['status'],
                'service_mode' => $data['service_mode'],
                'reported_problem' => $data['reported_problem'] ?? 'Sin información',
                'initial_observations' => $data['initial_observations'] ?? null,
                'technical_diagnosis' => $data['technical_diagnosis'] ?? null,
                'diagnosis_due_at' => $data['service_mode'] === 'diagnostico' ? app(BusinessDeadlineService::class)->addBusinessHours(now(), $company->technical_diagnostic_hours ?? 24) : null,
                'labor_amount' => $data['service_mode'] === 'precio_definido' ? $this->directServiceSubtotal($data) : ($data['labor_amount'] ?? 0),
                'parts_amount' => $data['service_mode'] === 'precio_definido' ? 0 : ($data['parts_amount'] ?? 0),
                'discount_amount' => $data['service_mode'] === 'precio_definido' ? ($data['discount_amount'] ?? 0) : 0,
                'net_amount' => $data['service_mode'] === 'precio_definido' ? $directTotals['net_amount'] : 0,
                'tax_amount' => $data['service_mode'] === 'precio_definido' ? $directTotals['tax_amount'] : 0,
                'total_amount' => $data['service_mode'] === 'precio_definido'
                    ? $directTotals['total_amount']
                    : ($data['labor_amount'] ?? 0) + ($data['parts_amount'] ?? 0),
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
                'customer_accepted_at' => $data['service_mode'] === 'precio_definido' ? now() : null,
            ]);

            $order->statusLogs()->create([
                'status' => $order->status,
                'comment' => 'Orden creada.',
            ]);

            $order->devices()->sync($devices->pluck('id')->all());
            $this->syncDirectServiceItem($order, $data);
        });

        if ($order) {
            ActivityLog::record($request, 'repair_order_created', 'Orden creada: ' . $order->code, $order);
            try {
                foreach ($request->file('photos', []) as $index => $photo) {
                    $photoService->store($order, $photo, (int) $request->input('label_photo_index', -1) === $index);
                }
                $this->sendReceptionEmail($order->fresh(), $request);
            } catch (Throwable $error) {
                report($error);
                $order->update(['email_status' => 'error', 'email_error' => mb_substr($error->getMessage(), 0, 1000)]);
            }
        }

        return redirect()->route('repair-orders.index')->with('success', 'Orden de reparación creada correctamente.');
    }

    public function show(RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        return view('repair-orders.show', [
            'order' => $repairOrder->load([
                'company',
                'client',
                'device',
                'devices',
                'statusLogs',
                'quotes',
                'payments.device',
                'payments.repairQuote',
                'payments.creator',
                'items.service',
                'photos',
                'homeServicePreorder',
            ]),
            'statuses' => RepairOrder::STATUSES,
            'paymentTypes' => RepairOrderPayment::TYPES,
            'paymentMethods' => RepairOrderPayment::METHODS,
        ]);
    }

    public function storeDiagnosis(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);
        abort_if($repairOrder->service_mode !== 'diagnostico', 422, 'Esta orden no requiere diagnóstico.');
        abort_if($repairOrder->quotes()->where('quote_type', '!=', 'closure')->exists(), 422, 'No se puede modificar el diagnóstico desde esta etapa porque la orden ya tiene un presupuesto.');

        $data = $request->validate([
            'technical_diagnosis' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($repairOrder, $data) {
            $repairOrder->update([
                'technical_diagnosis' => $data['technical_diagnosis'],
                'status' => 'diagnosticado',
            ]);
            $repairOrder->statusLogs()->create([
                'status' => 'diagnosticado',
                'comment' => 'Diagnóstico técnico registrado.',
            ]);
        });

        ActivityLog::record($request, 'repair_order_diagnosed', 'Diagnóstico registrado: '.$repairOrder->code, $repairOrder);

        return redirect()->route('repair-orders.show', $repairOrder)->with('success', 'Diagnóstico registrado. Ya puedes generar el presupuesto.');
    }

    public function edit(RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        return view('repair-orders.edit', [
            'order' => $repairOrder->load(['device', 'devices', 'client', 'items']),
            'companies' => $this->availableCompanies(request()),
            'clients' => $this->availableClients(request()),
            'statuses' => RepairOrder::STATUSES,
            'services' => $this->availableServices(request(), true),
        ]);
    }

    public function print(RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        return view('repair-orders.print', [
            'order' => $repairOrder->load([
                'company',
                'client',
                'device',
                'devices',
                'items.service',
                'payments',
                'homeServicePreorder',
            ]),
        ]);
    }

    public function sendEmail(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);
        try {
            $this->sendReceptionEmail($repairOrder, $request);
            return redirect()->route('repair-orders.show', $repairOrder)->with('success', 'Comprobante enviado al correo del cliente.');
        } catch (Throwable $error) {
            report($error);
            return redirect()->route('repair-orders.show', $repairOrder)->withErrors(['email' => 'No fue posible enviar el correo: '.$error->getMessage()]);
        }
    }

    public function update(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data, $repairOrder) {
            $client = $this->resolveClient($data);
            $devices = $this->syncDevices($data, $client);
            $primaryDevice = $devices->first();

            $oldStatus = $repairOrder->status;
            $isDirect = $data['service_mode'] === 'precio_definido';
            $directSubtotal = $isDirect ? $this->directServiceSubtotal($data) : 0;
            $directTotals = $this->directServiceTotals($data);
            $repairOrder->update([
                'company_id' => $data['company_id'],
                'client_id' => $client->id,
                'device_id' => $primaryDevice->id,
                'status' => $data['status'],
                'service_mode' => $data['service_mode'],
                'reported_problem' => $data['reported_problem'] ?? $repairOrder->reported_problem,
                'initial_observations' => $data['initial_observations'] ?? $repairOrder->initial_observations,
                'technical_diagnosis' => $data['technical_diagnosis'] ?? $repairOrder->technical_diagnosis,
                'labor_amount' => $isDirect ? $directSubtotal : ($data['labor_amount'] ?? $repairOrder->labor_amount),
                'parts_amount' => $isDirect ? 0 : ($data['parts_amount'] ?? $repairOrder->parts_amount),
                'discount_amount' => $isDirect ? ($data['discount_amount'] ?? 0) : 0,
                'net_amount' => $isDirect ? $directTotals['net_amount'] : $repairOrder->net_amount,
                'tax_amount' => $isDirect ? $directTotals['tax_amount'] : $repairOrder->tax_amount,
                'total_amount' => $isDirect ? $directTotals['total_amount'] : (array_key_exists('labor_amount', $data) || array_key_exists('parts_amount', $data)
                    ? ($data['labor_amount'] ?? 0) + ($data['parts_amount'] ?? 0)
                    : $repairOrder->total_amount),
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? $repairOrder->estimated_delivery_date,
                'customer_accepted_at' => $isDirect ? ($repairOrder->customer_accepted_at ?? now()) : null,
                'delivered_at' => $data['status'] === 'entregado' ? now() : null,
            ]);

            if ($oldStatus !== $repairOrder->status) {
                $repairOrder->statusLogs()->create([
                    'status' => $repairOrder->status,
                    'comment' => 'Estado actualizado desde edicion.',
                ]);
            }

            $repairOrder->devices()->sync($devices->pluck('id')->all());
            $this->syncDirectServiceItem($repairOrder, $data);
        });

        ActivityLog::record($request, 'repair_order_updated', 'Orden actualizada: ' . $repairOrder->code, $repairOrder);

        return redirect()->route('repair-orders.show', $repairOrder)->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(Request $request, RepairOrder $repairOrder)
    {
        $this->authorizeCompanyAccess($repairOrder);

        $code = $repairOrder->code;
        $companyId = $repairOrder->company_id;

        DB::transaction(function () use ($repairOrder) {
            $repairOrder->devices()->detach();
            $repairOrder->delete();
        });

        ActivityLog::record($request, 'repair_order_deleted', 'Orden eliminada: ' . $code, null, $companyId);

        return redirect()->route('repair-orders.index')->with('success', 'Orden eliminada correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge(['service_mode' => $request->input('service_mode', 'diagnostico')]);

        if ($request->filled('client_rut')) {
            $request->merge(['client_rut' => ValidRut::format($request->string('client_rut')->toString())]);
        }

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_rut' => ['required', 'string', 'max:20', new ValidRut],
            'client_name' => ['required', 'string', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', Rule::in(array_keys(config('chile.regions')))],
            'commune' => ['nullable', Rule::in(config('chile.regions.'.$request->input('region'), []))],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(RepairOrder::STATUSES))],
            'service_mode' => ['required', Rule::in(['diagnostico', 'precio_definido'])],
            'service_id' => ['nullable', 'required_if:service_mode,precio_definido', 'exists:services,id'],
            'service_quantity' => ['nullable', 'required_if:service_mode,precio_definido', 'integer', 'min:1'],
            'service_unit_price' => ['nullable', 'required_if:service_mode,precio_definido', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_acceptance' => ['exclude_unless:service_mode,precio_definido', 'required', 'accepted'],
            'devices' => ['required', 'array', 'min:1'],
            'devices.*.id' => ['nullable', 'exists:devices,id'],
            'devices.*.type' => ['required', 'string', 'max:80'],
            'devices.*.brand' => ['nullable', 'string', 'max:120'],
            'devices.*.model' => ['nullable', 'string', 'max:120'],
            'devices.*.serial_number' => ['nullable', 'string', 'max:120'],
            'devices.*.accessories' => ['nullable', 'string'],
            'devices.*.physical_condition' => ['nullable', 'string'],
            'reported_problem' => ['nullable', 'string'],
            'initial_observations' => ['nullable', 'string'],
            'technical_diagnosis' => ['nullable', 'string'],
            'labor_amount' => ['nullable', 'numeric', 'min:0'],
            'parts_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_date' => ['nullable', 'date'],
            'attach_photos' => ['nullable', 'boolean'],
            'label_photo_index' => ['nullable', 'integer', 'min:0', 'max:4'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        if ($request->user()->role !== 'super_admin') {
            $data['company_id'] = $request->user()->company_id;
        }

        if (($data['service_mode'] ?? null) === 'precio_definido') {
            abort_unless(Service::whereKey($data['service_id'])->where('company_id', $data['company_id'])->exists(), 422, 'El servicio no pertenece a la empresa seleccionada.');
        }

        return $data;
    }

    private function resolveClient(array $data): Client
    {
        $client = null;

        if (! empty($data['client_id'])) {
            $client = Client::where('company_id', $data['company_id'])->find($data['client_id']);
        }

        if (! $client) {
            $cleanRut = ValidRut::clean($data['client_rut']);
            $client = Client::where('company_id', $data['company_id'])
                ->get()
                ->first(fn (Client $candidate) => ValidRut::clean($candidate->rut) === $cleanRut);
        }

        $payload = [
            'company_id' => $data['company_id'],
            'rut' => ValidRut::format($data['client_rut']),
            'name' => $data['client_name'],
            'address' => $data['client_address'] ?? null,
            'region' => $data['region'] ?? null,
            'commune' => $data['commune'] ?? null,
            'phone' => $data['client_phone'] ?? null,
            'email' => $data['client_email'] ?? null,
        ];

        if (array_key_exists('client_notes', $data)) {
            $payload['notes'] = $data['client_notes'];
        }

        if ($client) {
            $client->update($payload);

            return $client;
        }

        return Client::create($payload);
    }

    private function syncDevices(array $data, Client $client)
    {
        return collect($data['devices'])->map(function (array $deviceData) use ($data, $client) {
            $payload = [
                'company_id' => $data['company_id'],
                'client_id' => $client->id,
                'type' => $deviceData['type'],
                'brand' => $deviceData['brand'] ?? null,
                'model' => $deviceData['model'] ?? null,
                'serial_number' => $deviceData['serial_number'] ?? null,
                'accessories' => $deviceData['accessories'] ?? null,
                'physical_condition' => $deviceData['physical_condition'] ?? null,
            ];

            if (! empty($deviceData['id'])) {
                $device = Device::where('company_id', $data['company_id'])
                    ->where('client_id', $client->id)
                    ->find($deviceData['id']);

                if ($device) {
                    $device->update($payload);

                    return $device;
                }
            }

            return Device::create($payload);
        });
    }

    private function nextCode(int $companyId): string
    {
        $nextNumber = RepairOrder::where('company_id', $companyId)->count() + 1;

        return 'OT-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function availableCompanies(Request $request)
    {
        if ($request->user()->role === 'super_admin') {
            return Company::orderBy('name')->get();
        }

        return Company::whereKey($request->user()->company_id)->get();
    }

    private function availableClients(Request $request)
    {
        return Client::with('company')
            ->when($request->user()->role !== 'super_admin', fn ($query) => $query->where('company_id', $request->user()->company_id))
            ->orderBy('name')
            ->get();
    }

    private function availableServices(Request $request, bool $includeInactive = false)
    {
        return Service::query()
            ->when($request->user()->role !== 'super_admin', fn ($query) => $query->where('company_id', $request->user()->company_id))
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')->get();
    }

    private function directServiceSubtotal(array $data): float
    {
        return (int) ($data['service_quantity'] ?? 1) * (float) ($data['service_unit_price'] ?? 0);
    }

    private function directServiceTotals(array $data): array
    {
        if (($data['service_mode'] ?? null) !== 'precio_definido') {
            return ['net_amount' => 0, 'tax_amount' => 0, 'total_amount' => 0];
        }
        $net = max($this->directServiceSubtotal($data) - (float) ($data['discount_amount'] ?? 0), 0);
        $tax = round($net * 0.19);
        return ['net_amount' => $net, 'tax_amount' => $tax, 'total_amount' => $net + $tax];
    }

    private function syncDirectServiceItem(RepairOrder $order, array $data): void
    {
        $order->items()->delete();
        if ($data['service_mode'] !== 'precio_definido') {
            return;
        }
        $service = Service::findOrFail($data['service_id']);
        $quantity = (int) $data['service_quantity'];
        $unitPrice = (float) $data['service_unit_price'];
        $order->items()->create([
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ]);
    }

    private function sendReceptionEmail(RepairOrder $order, Request $request): void
    {
        $order->load(['company', 'client', 'devices', 'items', 'photos']);
        if (! $order->client->email) {
            $order->update(['email_status' => 'omitido', 'email_error' => 'El cliente no tiene correo registrado.']);
            return;
        }
        if (! $order->company->smtp_host || ! $order->company->smtp_username || ! $order->company->smtp_password) {
            throw new \RuntimeException('La empresa no tiene completa su configuración SMTP.');
        }
        config(['mail.mailers.company_smtp' => [
            'transport' => 'smtp',
            'scheme' => $order->company->smtp_security === 'ssl' ? 'smtps' : null,
            'host' => $order->company->smtp_host,
            'port' => $order->company->smtp_port ?: 587,
            'username' => $order->company->smtp_username,
            'password' => $order->company->smtp_password,
            'timeout' => 30,
            'auto_tls' => $order->company->smtp_security !== 'none',
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]]);
        $order->update(['email_status' => 'enviando', 'email_error' => null]);
        try {
            Mail::mailer('company_smtp')->to($order->client->email)->send(new RepairOrderReceived($order));
            $order->update(['email_status' => 'enviado', 'emailed_at' => now(), 'email_error' => null]);
            ActivityLog::record($request, 'repair_order_emailed', 'Comprobante enviado: '.$order->code, $order);
        } catch (Throwable $error) {
            $order->update(['email_status' => 'error', 'email_error' => mb_substr($error->getMessage(), 0, 1000)]);
            throw $error;
        }
    }

    private function authorizeCompanyAccess(RepairOrder $repairOrder): void
    {
        $user = request()->user();

        if ($user->role !== 'super_admin' && $repairOrder->company_id !== $user->company_id) {
            abort(403);
        }
    }
}
