<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Rules\ValidRut;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $user = request()->user();

        return view('companies.index', [
            'companies' => Company::query()
                ->when($user->role !== 'super_admin', fn ($query) => $query->whereKey($user->company_id))
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create()
    {
        abort_unless(request()->user()->role === 'super_admin', 403);

        return view('companies.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);

        $company = Company::create($this->validatedData($request));
        ActivityLog::record($request, 'company_created', 'Empresa creada: ' . $company->name, $company, $company->id);

        return redirect()->route('companies.index')->with('success', 'Empresa creada correctamente.');
    }

    public function edit(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $company->update($this->validatedData($request, $company));
        ActivityLog::record($request, 'company_updated', 'Empresa actualizada: ' . $company->name, $company, $company->id);

        return redirect()->route('companies.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Request $request, Company $company)
    {
        abort_unless($request->user()->role === 'super_admin', 403);

        $name = $company->name;
        ActivityLog::record($request, 'company_deleted', 'Empresa eliminada con todos sus datos: '.$name, $company, $company->id);

        User::where('company_id', $company->id)->delete();
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Empresa y datos asociados eliminados correctamente.');
    }

    private function validatedData(Request $request, ?Company $company = null): array
    {
        $companyId = $company?->id ?? 'NULL';

        if ($request->filled('rut')) {
            $request->merge(['rut' => ValidRut::format($request->string('rut')->toString())]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:20', 'unique:companies,rut,' . $companyId],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'diagnostic_fee_gross' => ['required', 'numeric', 'min:0'],
            'technical_diagnostic_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'recovery_diagnostic_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'plan' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function authorizeCompanyAccess(Company $company): void
    {
        $user = request()->user();

        if ($user->role !== 'super_admin' && $company->id !== $user->company_id) {
            abort(403);
        }
    }
}
