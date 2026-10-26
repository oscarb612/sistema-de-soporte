<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $users = User::with('company')
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('company_id', $user->company_id))
            ->latest()
            ->paginate(12);

        return view('users.index', compact('users'));
    }

    public function create(Request $request)
    {
        return view('users.create', [
            'companies' => $this->availableCompanies($request),
            'roles' => $this->availableRoles($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $managedUser = User::create([
            'company_id' => $data['company_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record($request, 'user_created', 'Usuario creado: ' . $managedUser->email, $managedUser, $managedUser->company_id);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeCompanyAccess($request, $user);

        return view('users.edit', [
            'managedUser' => $user,
            'companies' => $this->availableCompanies($request),
            'roles' => $this->availableRoles($request),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeCompanyAccess($request, $user);
        $data = $this->validatedData($request, $user);

        $payload = [
            'company_id' => $data['company_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        ActivityLog::record($request, 'user_updated', 'Usuario actualizado: ' . $user->email, $user, $user->company_id);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeCompanyAccess($request, $user);
        abort_if($request->user()->is($user), 403, 'No puedes eliminar tu propia cuenta.');
        abort_if(
            $user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1,
            403,
            'No se puede eliminar el último superadministrador.'
        );

        $email = $user->email;
        $companyId = $user->company_id;
        ActivityLog::record($request, 'user_deleted', 'Usuario eliminado: '.$email, $user, $companyId);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $currentUser = $request->user();
        $roles = array_keys($this->availableRoles($request));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in($roles)],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($currentUser->role === 'super_admin') {
            $rules['company_id'] = ['nullable', 'exists:companies,id'];
        } else {
            $rules['company_id'] = ['nullable'];
        }

        $data = $request->validate($rules);

        if ($currentUser->role !== 'super_admin') {
            $data['company_id'] = $currentUser->company_id;
        } elseif ($data['role'] !== 'super_admin' && empty($data['company_id'])) {
            throw ValidationException::withMessages([
                'company_id' => 'Debes seleccionar una empresa para este rol.',
            ]);
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

    private function availableRoles(Request $request): array
    {
        if ($request->user()->role === 'super_admin') {
            return [
                'super_admin' => 'Super administrador',
                'admin' => 'Administrador empresa',
                'recepcionista' => 'Recepcionista',
                'tecnico' => 'Técnico',
            ];
        }

        return [
            'admin' => 'Administrador empresa',
            'recepcionista' => 'Recepcionista',
            'tecnico' => 'Técnico',
        ];
    }

    private function authorizeCompanyAccess(Request $request, User $user): void
    {
        if ($request->user()->role !== 'super_admin' && $user->company_id !== $request->user()->company_id) {
            abort(403);
        }
    }
}
