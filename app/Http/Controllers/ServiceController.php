<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('services.index', ['services' => Service::with('company')->when($user->role !== 'super_admin', fn ($q) => $q->where('company_id', $user->company_id))->orderBy('name')->paginate(25)]);
    }

    public function create(Request $request)
    {
        return view('services.create', ['service' => new Service, 'companies' => $this->companies($request)]);
    }

    public function store(Request $request)
    {
        $service = Service::create($this->validatedData($request));
        ActivityLog::record($request, 'service_created', 'Servicio creado: '.$service->name, $service, $service->company_id);
        return redirect()->route('services.index')->with('success', 'Servicio creado correctamente.');
    }

    public function edit(Request $request, Service $service)
    {
        $this->authorizeAccess($request, $service);
        return view('services.edit', ['service' => $service, 'companies' => $this->companies($request)]);
    }

    public function update(Request $request, Service $service)
    {
        $this->authorizeAccess($request, $service);
        $service->update($this->validatedData($request, $service));
        ActivityLog::record($request, 'service_updated', 'Servicio actualizado: '.$service->name, $service, $service->company_id);
        return redirect()->route('services.index')->with('success', 'Servicio actualizado correctamente.');
    }

    private function validatedData(Request $request, ?Service $service = null): array
    {
        $companyId = $request->user()->role === 'super_admin' ? $request->integer('company_id') : $request->user()->company_id;
        $request->merge(['company_id' => $companyId]);
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('services')->where('company_id', $companyId)->ignore($service)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0.01'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function companies(Request $request)
    {
        return Company::query()->when($request->user()->role !== 'super_admin', fn ($q) => $q->whereKey($request->user()->company_id))->orderBy('name')->get();
    }

    private function authorizeAccess(Request $request, Service $service): void
    {
        abort_if($request->user()->role !== 'super_admin' && $service->company_id !== $request->user()->company_id, 403);
    }
}
