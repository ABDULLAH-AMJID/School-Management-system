<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AccessController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('administration.access', ['users' => User::where('school_id', $schoolId)->with('roles')->orderBy('name')->get(), 'roles' => Role::withCount('permissions')->orderBy('label')->get(), 'permissions' => Permission::orderBy('module')->orderBy('label')->get()->groupBy('module')]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:150', 'unique:users,email'], 'phone' => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'], 'role_id' => ['required', 'exists:roles,id'], 'password' => ['required', 'string', 'min:10', 'confirmed']]);
        $user = User::create(['school_id' => $request->user()->school_id, 'public_id' => (string) Str::ulid(), 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'status' => 'active', 'password' => Hash::make($data['password'])]);
        $user->roles()->sync([$data['role_id']]);

        return back()->with('success', 'Staff account created.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['role_ids' => ['required', 'array', 'min:1'], 'role_ids.*' => ['exists:roles,id'], 'status' => ['required', Rule::in(['active', 'suspended'])]]);
        abort_if($user->is($request->user()) && $data['status'] === 'suspended', 422, 'You cannot suspend your own account.');
        $user->roles()->sync($data['role_ids']);
        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Access updated.');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:80'], 'name' => ['required', 'alpha_dash', 'max:80', 'unique:roles,name'], 'permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['exists:permissions,id']]);
        $role = Role::create(['name' => $data['name'], 'label' => $data['label'], 'system' => false]);
        $role->permissions()->sync($data['permission_ids'] ?? []);

        return back()->with('success', 'Custom role created.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate(['permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['exists:permissions,id']]);
        if ($role->name === 'principal' && ! collect($data['permission_ids'] ?? [])->contains(Permission::where('name', 'access.manage')->value('id'))) {
            return back()->withErrors(['permission_ids' => 'Principal role must retain access management permission.']);
        }$role->permissions()->sync($data['permission_ids'] ?? []);

        return back()->with('success','Role permissions updated.');
    }
}
