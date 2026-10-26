<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MailSettingController extends Controller
{
    public function edit(Request $request)
    {
        $companies = $this->availableCompanies($request);
        $selectedId = $request->user()->role === 'super_admin'
            ? $request->integer('company_id')
            : $request->user()->company_id;
        $company = $companies->firstWhere('id', $selectedId) ?? $companies->first();

        return view('mail-settings.edit', compact('companies', 'company'));
    }

    public function update(Request $request)
    {
        $companyId = $request->user()->role === 'super_admin'
            ? $request->integer('company_id')
            : $request->user()->company_id;

        $data = $request->validate([
            'company_id' => ['nullable', Rule::exists('companies', 'id')],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['required', 'string', 'max:255'],
            'smtp_password' => [Rule::requiredIf(! $companyId || ! Company::find($companyId)?->smtp_password), 'nullable', 'string', 'max:1000'],
            'smtp_security' => ['required', Rule::in(['tls', 'ssl', 'none'])],
        ]);

        $company = Company::findOrFail($companyId);
        abort_if($request->user()->role !== 'super_admin' && $company->id !== $request->user()->company_id, 403);

        $payload = [
            'mail_from_name' => $data['mail_from_name'],
            'mail_from_address' => $data['mail_from_address'],
            'smtp_host' => $data['smtp_host'],
            'smtp_port' => $data['smtp_port'],
            'smtp_username' => $data['smtp_username'],
            'smtp_security' => $data['smtp_security'],
        ];
        if (! empty($data['smtp_password'])) {
            $payload['smtp_password'] = $data['smtp_password'];
        }
        $company->update($payload);

        ActivityLog::record($request, 'mail_settings_updated', 'Remitente de correo actualizado para '.$company->name, $company, $company->id);

        return redirect()->route('mail-settings.edit', ['company_id' => $company->id])
            ->with('success', 'Configuración del correo saliente guardada correctamente.');
    }

    private function availableCompanies(Request $request)
    {
        return Company::query()
            ->when($request->user()->role !== 'super_admin', fn ($query) => $query->whereKey($request->user()->company_id))
            ->orderBy('name')->get();
    }
}
