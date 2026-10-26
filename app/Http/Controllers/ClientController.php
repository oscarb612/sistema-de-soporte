<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Rules\ValidRut;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $clients = Client::with('company')
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = mb_strtolower(trim($request->string('q')->toString()));

                $query->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(rut) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
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
            'label' => $client->name . ' - ' . $client->formatted_rut,
            'value' => $client->formatted_rut,
        ]));
    }

    public function create(Request $request)
    {
        return view('clients.create', [
            'companies' => $this->availableCompanies($request),
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::create($this->validatedData($request));
        ActivityLog::record($request, 'client_created', 'Cliente creado: ' . $client->name, $client);

        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Client $client)
    {
        $this->authorizeCompanyAccess($client);

        return view('clients.edit', [
            'client' => $client,
            'companies' => $this->availableCompanies(request()),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeCompanyAccess($client);

        $client->update($this->validatedData($request, $client));
        ActivityLog::record($request, 'client_updated', 'Cliente actualizado: ' . $client->name, $client);

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function lookupByRut(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'rut' => ['required', 'string', 'max:20', new ValidRut],
        ]);

        if ($request->user()->role !== 'super_admin') {
            $data['company_id'] = $request->user()->company_id;
        }

        $client = Client::where('company_id', $data['company_id'])
            ->whereRaw("replace(replace(replace(upper(rut), '.', ''), '-', ''), ' ', '') = ?", [ValidRut::clean($data['rut'])])
            ->first();

        if (! $client) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'client' => [
                'id' => $client->id,
                'rut' => $client->formatted_rut,
                'name' => $client->name,
                'address' => $client->address,
                'region' => $client->region,
                'commune' => $client->commune,
                'phone' => $client->phone,
                'email' => $client->email,
                'notes' => $client->notes,
            ],
        ]);
    }

    private function validatedData(Request $request, ?Client $client = null): array
    {
        if ($request->filled('rut')) {
            $request->merge(['rut' => ValidRut::format($request->string('rut')->toString())]);
        }

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'rut' => [
                'required',
                'string',
                'max:20',
                new ValidRut,
                Rule::unique('clients')->where('company_id', $request->input('company_id'))->ignore($client),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', Rule::in(array_keys(config('chile.regions')))],
            'commune' => ['nullable', Rule::in(config('chile.regions.'.$request->input('region'), []))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->user()->role !== 'super_admin') {
            $data['company_id'] = $request->user()->company_id;
        }

        return $data;
    }

    private function availableCompanies(Request $request)
    {
        if ($request->user()->role === 'super_admin') {
            return Company::orderBy('name')->get();
        }

        return Company::whereKey($request->user()->company_id)->get();
    }

    private function authorizeCompanyAccess(Client $client): void
    {
        $user = request()->user();

        if ($user->role !== 'super_admin' && $client->company_id !== $user->company_id) {
            abort(403);
        }
    }
}
