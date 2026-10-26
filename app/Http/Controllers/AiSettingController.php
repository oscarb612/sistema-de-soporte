<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiSettingController extends Controller
{
    public function edit(Request $request)
    {
        $companies = $this->availableCompanies($request);
        $selectedId = $request->user()->role === 'super_admin' ? $request->integer('company_id') : $request->user()->company_id;
        $company = $companies->firstWhere('id', $selectedId) ?? $companies->first();

        return view('ai-settings.edit', compact('companies', 'company'));
    }

    public function update(Request $request)
    {
        $companyId = $request->user()->role === 'super_admin' ? $request->integer('company_id') : $request->user()->company_id;
        $data = $request->validate([
            'company_id' => ['nullable', Rule::exists('companies', 'id')],
            'groq_api_key' => ['nullable', 'string', 'min:20', 'max:500'],
            'groq_model' => ['required', Rule::in(['qwen/qwen3.8-27b'])],
        ]);

        $company = Company::findOrFail($companyId);
        abort_if($request->user()->role !== 'super_admin' && $company->id !== $request->user()->company_id, 403);

        $payload = ['groq_model' => $data['groq_model']];
        if (! empty($data['groq_api_key'])) {
            $payload['groq_api_key'] = trim($data['groq_api_key']);
        }
        $company->update($payload);

        ActivityLog::record($request, 'ai_settings_updated', 'Configuración de Groq actualizada para '.$company->name, $company, $company->id);

        return redirect()->route('ai-settings.edit', ['company_id' => $company->id])
            ->with('success', 'Configuración de Groq guardada correctamente.');
    }

    private function availableCompanies(Request $request)
    {
        return Company::query()
            ->when($request->user()->role !== 'super_admin', fn ($query) => $query->whereKey($request->user()->company_id))
            ->orderBy('name')->get();
    }
}
