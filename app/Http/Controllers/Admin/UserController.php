<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 5);
        $activeCompany = auth()->user()?->getActiveCompany();

        $allowedSort = ['name', 'email', 'created_at'];
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        if ($activeCompany) {
            $users = $activeCompany->users()
                ->with(['companyRoles', 'companies'])
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->appends($request->query());
        } else {
            $users = User::with(['companyRoles', 'companies'])
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->appends($request->query());
        }

        return view('admin.users.index', compact('users', 'activeCompany', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        // Enforce default 'user' role is always assigned
        $userRoleId = Role::where('name', 'user')->value('id');
        $roleIds = $request->input('roles', []);
        if ($userRoleId && ! in_array($userRoleId, $roleIds)) {
            $roleIds[] = $userRoleId;
        }

        // Assign the user to the active company if one is selected
        $activeCompany = auth()->user()?->getActiveCompany();
        if ($activeCompany) {
            $user->companies()->attach($activeCompany->id, [
                'job_title' => $request->input('job_title'),
                'manager_id' => $request->input('manager_id'),
            ]);

            // Attach company-scoped roles
            $user->companyRoles()->attach($roleIds, ['company_id' => $activeCompany->id]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        $activeCompany = auth()->user()?->getActiveCompany();

        if ($activeCompany) {
            $userRoleIds = $user->companyRoles()
                ->wherePivot('company_id', $activeCompany->id)
                ->pluck('roles.id')
                ->toArray();
        } else {
            $userRoleIds = [];
        }

        return view('admin.users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $userData = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->string('password')->toString());
        }

        $user->update($userData);

        // Enforce default 'user' role is always assigned
        $userRoleId = Role::where('name', 'user')->value('id');
        $roleIds = $request->input('roles', []);
        if ($userRoleId && ! in_array($userRoleId, $roleIds)) {
            $roleIds[] = $userRoleId;
        }

        // Sync company-scoped roles for the active company
        $activeCompany = auth()->user()?->getActiveCompany();
        if ($activeCompany) {
            $pivotData = array_fill(0, count($roleIds), ['company_id' => $activeCompany->id]);
            $user->companyRoles()
                ->wherePivot('company_id', $activeCompany->id)
                ->sync(array_combine($roleIds, $pivotData));
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
