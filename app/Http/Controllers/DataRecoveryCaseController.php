<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\DataRecoveryCase;
use App\Models\DataRecoveryPayment;
use App\Rules\ValidRut;
use App\Services\DataRecoveryPhotoService;
use App\Services\BusinessDeadlineService;
use App\Mail\DataRecoveryReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class DataRecoveryCaseController extends Controller
{
    public function index(Request $request)
    {
        $cases = DataRecoveryCase::with(['client', 'company'])->when($request->user()->role !== 'super_admin', fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when($request->filled('q'), function ($q) use ($request) { $term = '%'.mb_strtolower(trim($request->string('q'))).'%'; $q->where(fn ($q) => $q->whereRaw('LOWER(code) LIKE ?', [$term])->orWhereHas('client', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(rut) LIKE ?', [$term]))); })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->paginate(50)->withQueryString();
        return view('data-recovery.index', compact('cases') + ['statuses' => DataRecoveryCase::STATUSES]);
    }

    public function create(Request $request)
    {
        return view('data-recovery.create', ['case' => new DataRecoveryCase, 'companies' => $this->companies($request), 'statuses' => DataRecoveryCase::STATUSES, 'unitTypes' => DataRecoveryCase::UNIT_TYPES, 'failureReasons' => DataRecoveryCase::FAILURE_REASONS]);
    }

    public function store(Request $request, DataRecoveryPhotoService $photos)
    {
        $data = $this->validateData($request);
        $case = DB::transaction(function () use ($data, $request) {
            $client = $this->resolveClient($data);
            $company = Company::findOrFail($data['company_id']);
            $case = DataRecoveryCase::create($this->caseData($data) + ['client_id' => $client->id, 'received_by' => $request->user()->id, 'code' => $this->nextCode($data['company_id']), 'received_at' => now(), 'diagnosis_due_at' => app(BusinessDeadlineService::class)->addBusinessHours(now(), $company->recovery_diagnostic_hours ?? 48)]);
            $case->statusLogs()->create(['user_id' => $request->user()->id, 'status' => $case->status, 'comment' => 'Caso recibido.']);
            return $case;
        });
        foreach ($request->file('photos', []) as $photo) $photos->store($case, $photo);
        try { $this->sendReceptionEmail($case, $request); } catch (Throwable $error) { report($error); }
        ActivityLog::record($request, 'data_recovery_created', 'Caso Recuperadata creado: '.$case->code, $case, $case->company_id);
        return redirect()->route('data-recovery.show', $case)->with('success', 'Caso de recuperación registrado correctamente.');
    }

    public function show(DataRecoveryCase $dataRecovery)
    {
        $this->authorizeCase($dataRecovery);
        return view('data-recovery.show', [
            'case' => $dataRecovery->load(['client', 'company', 'receiver', 'photos', 'statusLogs.user', 'quotes.items', 'payments.creator', 'payments.dataRecoveryQuote', 'homeServicePreorder']),
            'statuses' => DataRecoveryCase::STATUSES,
            'paymentTypes' => DataRecoveryPayment::TYPES,
            'paymentMethods' => DataRecoveryPayment::METHODS,
        ]);
    }

    public function edit(DataRecoveryCase $dataRecovery)
    {
        $this->authorizeCase($dataRecovery);
        return view('data-recovery.edit', ['case' => $dataRecovery->load('client'), 'companies' => $this->companies(request()), 'statuses' => DataRecoveryCase::STATUSES, 'unitTypes' => DataRecoveryCase::UNIT_TYPES, 'failureReasons' => DataRecoveryCase::FAILURE_REASONS]);
    }

    public function update(Request $request, DataRecoveryCase $dataRecovery)
    {
        $this->authorizeCase($dataRecovery); $oldStatus = $dataRecovery->status; $data = $this->validateData($request, true);
        DB::transaction(function () use ($data, $dataRecovery, $request, $oldStatus) { $client = $this->resolveClient($data); $dataRecovery->update($this->caseData($data) + ['client_id' => $client->id, 'delivered_at' => $data['status'] === 'entregado' ? ($dataRecovery->delivered_at ?? now()) : null]); if ($oldStatus !== $data['status']) $dataRecovery->statusLogs()->create(['user_id' => $request->user()->id, 'status' => $data['status'], 'comment' => $data['status_comment'] ?? null]); });
        ActivityLog::record($request, 'data_recovery_updated', 'Caso Recuperadata actualizado: '.$dataRecovery->code, $dataRecovery, $dataRecovery->company_id);
        return redirect()->route('data-recovery.show', $dataRecovery)->with('success', 'Caso actualizado correctamente.');
    }

    public function destroy(Request $request, DataRecoveryCase $dataRecovery)
    {
        $this->authorizeCase($dataRecovery); $code = $dataRecovery->code; $company = $dataRecovery->company_id; $dataRecovery->delete(); ActivityLog::record($request, 'data_recovery_deleted', 'Caso Recuperadata eliminado: '.$code, null, $company);
        return redirect()->route('data-recovery.index')->with('success', 'Caso eliminado correctamente.');
    }

    public function print(DataRecoveryCase $dataRecovery) { $this->authorizeCase($dataRecovery); return view('data-recovery.print', ['case' => $dataRecovery->load(['client', 'company', 'receiver', 'homeServicePreorder'])]); }

    private function validateData(Request $request, bool $editing = false): array
    {
        if ($request->filled('rut')) $request->merge(['rut' => ValidRut::format($request->string('rut'))]);
        $rules = ['company_id' => ['required', 'exists:companies,id'], 'rut' => ['required', new ValidRut], 'client_name' => ['required','string','max:255'], 'client_email' => ['nullable','email','max:255'], 'client_phone' => ['nullable','string','max:50'], 'client_address' => ['nullable','string','max:255'], 'status' => ['required', Rule::in(array_keys(DataRecoveryCase::STATUSES))], 'unit_type' => ['required','string','max:100'], 'brand' => ['nullable','string','max:100'], 'model' => ['nullable','string','max:100'], 'capacity' => ['nullable','string','max:50'], 'serial_number' => ['nullable','string','max:150'], 'interface' => ['nullable','string','max:100'], 'failure_reason' => ['nullable','string','max:150'], 'previously_opened' => ['nullable','boolean'], 'contains_sensitive_data' => ['nullable','boolean'], 'priority_data' => ['nullable','string'], 'access_credentials' => ['nullable','string'], 'accessories' => ['nullable','string'], 'observations' => ['nullable','string'], 'diagnosis' => ['nullable','string'], 'result_summary' => ['nullable','string'], 'net_amount' => ['nullable','numeric','min:0'], 'status_comment' => ['nullable','string'], 'photos' => [$editing ? 'nullable' : 'nullable','array','max:5'], 'photos.*' => ['image','max:10240']];
        $data = $request->validate($rules); if ($request->user()->role !== 'super_admin') $data['company_id'] = $request->user()->company_id; return $data;
    }

    private function resolveClient(array $data): Client
    {
        $client = Client::where('company_id', $data['company_id'])
            ->whereRaw("replace(replace(replace(upper(rut), '.', ''), '-', ''), ' ', '') = ?", [ValidRut::clean($data['rut'])])
            ->first();
        $values = ['name' => $data['client_name'], 'email' => $data['client_email'] ?? null, 'phone' => $data['client_phone'] ?? null, 'address' => $data['client_address'] ?? null];
        if ($client) { $client->update($values); return $client; }
        return Client::create($values + ['company_id' => $data['company_id'], 'rut' => $data['rut']]);
    }

    private function caseData(array $data): array
    {
        $net = (float) ($data['net_amount'] ?? 0); return collect($data)->only(['company_id','status','unit_type','brand','model','capacity','serial_number','interface','failure_reason','priority_data','access_credentials','accessories','observations','diagnosis','result_summary'])->all() + ['previously_opened' => (bool) ($data['previously_opened'] ?? false), 'contains_sensitive_data' => (bool) ($data['contains_sensitive_data'] ?? false), 'net_amount' => $net, 'tax_amount' => round($net * .19, 2), 'total_amount' => round($net * 1.19, 2)];
    }
    private function nextCode(int $companyId): string
    {
        // PostgreSQL no permite FOR UPDATE sobre una función de agregación.
        // Bloquear la empresa serializa la generación del correlativo por empresa.
        Company::whereKey($companyId)->lockForUpdate()->firstOrFail();
        $lastCode = DataRecoveryCase::where('company_id', $companyId)->max('code');
        $next = ((int) substr((string) $lastCode, 3)) + 1;

        return 'RD-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
    private function sendReceptionEmail(DataRecoveryCase $case, Request $request): void
    {
        $case->load(['company','client','photos']);
        if (! $case->client->email) { $case->update(['email_status'=>'omitido','email_error'=>'El cliente no tiene correo registrado.']); return; }
        if (! $case->company->smtp_host || ! $case->company->smtp_username || ! $case->company->smtp_password) { $case->update(['email_status'=>'error','email_error'=>'Configuración SMTP incompleta.']); return; }
        config(['mail.mailers.company_smtp'=>['transport'=>'smtp','scheme'=>$case->company->smtp_security==='ssl'?'smtps':null,'host'=>$case->company->smtp_host,'port'=>$case->company->smtp_port ?: 587,'username'=>$case->company->smtp_username,'password'=>$case->company->smtp_password,'timeout'=>30,'auto_tls'=>$case->company->smtp_security!=='none','local_domain'=>parse_url((string)config('app.url'),PHP_URL_HOST)]]);
        try { Mail::mailer('company_smtp')->to($case->client->email)->send(new DataRecoveryReceived($case)); $case->update(['email_status'=>'enviado','emailed_at'=>now(),'email_error'=>null]); ActivityLog::record($request,'data_recovery_emailed','Comprobante Recuperadata enviado: '.$case->code,$case,$case->company_id); }
        catch (Throwable $error) { $case->update(['email_status'=>'error','email_error'=>mb_substr($error->getMessage(),0,1000)]); throw $error; }
    }
    private function companies(Request $request) { return Company::query()->when($request->user()->role !== 'super_admin', fn ($q) => $q->whereKey($request->user()->company_id))->orderBy('name')->get(); }
    private function authorizeCase(DataRecoveryCase $case): void { abort_if(request()->user()->role !== 'super_admin' && $case->company_id !== request()->user()->company_id, 403); }
}
