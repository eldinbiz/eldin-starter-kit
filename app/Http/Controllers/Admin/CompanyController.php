<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Companies\AssignCompanyUserAction;
use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Companies\RemoveCompanyUserAction;
use App\Actions\Companies\ReparentCompanyAction;
use App\Actions\Companies\ToggleCompanyActiveAction;
use App\Actions\Companies\UpdateCompanyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Companies\AssignCompanyUserRequest;
use App\Http\Requests\Admin\Companies\ReparentCompanyRequest;
use App\Http\Requests\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\Admin\Companies\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class CompanyController extends Controller
{
    /**
     * Display the company hierarchy tree view.
     */
    public function hierarchy(): View
    {
        $companies = Company::buildTree();
        $canManage = auth()->user()?->can('manage-companies') ?? false;

        return view('admin.companies.hierarchy', compact('companies', 'canManage'));
    }

    /**
     * Return the full company tree as JSON for the Alpine.js tree view.
     */
    public function tree(): JsonResponse
    {
        $roots = Company::buildTree();

        return response()->json([
            'companies' => $this->flattenTree($roots),
        ]);
    }

    /**
     * Reparent a company via drag-and-drop.
     */
    public function reparent(ReparentCompanyRequest $request, ReparentCompanyAction $action): JsonResponse
    {
        /** @var array{company_id: int|string, new_parent_id?: int|string|null} $validated */
        $validated = $request->validated();
        $companyId = (int) $validated['company_id'];
        $newParentId = ! empty($validated['new_parent_id']) ? (int) $validated['new_parent_id'] : null;

        try {
            $action->execute($companyId, $newParentId);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle the active/inactive status of a company.
     * When disabling, validates that all children are already disabled.
     */
    public function toggleActive(Company $company, ToggleCompanyActiveAction $action): JsonResponse
    {
        try {
            $updatedCompany = $action->execute($company);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'is_active' => $updatedCompany->is_active,
        ]);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        $parentCompanies = Company::select('id', 'name')->orderBy('name')->get();
        $types = config('company.types', []);

        return view('admin.companies.create', compact('parentCompanies', 'types'));
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): RedirectResponse
    {
        /** @var array{name: string, slug: string, type: string, parent_id?: int|string|null, is_active?: bool|int|string} $validated */
        $validated = $request->validated();

        $action->execute($validated, $request->file('logo'));

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing a company.
     */
    public function edit(Company $company): View
    {
        // Exclude self and all descendants from parent options to prevent cycle creation
        $excludeIds = $company->allChildren()->pluck('id')->push($company->id);

        $parentCompanies = Company::select('id', 'name')->whereNotIn('id', $excludeIds)->orderBy('name')->get();
        $types = config('company.types', []);

        // Eager-load users with their company-scoped roles in a single query
        $companyUsers = $company->users()
            ->with(['companyRoles' => fn ($query) => $query->wherePivot('company_id', $company->id)])
            ->get();

        // Pre-compute how many team members report to each user in this company (collection-based, pure Eloquent)
        $managerCounts = $company->users()
            ->wherePivotNotNull('manager_id')
            ->get()
            ->groupBy('pivot.manager_id')
            ->map->count();

        foreach ($companyUsers as $user) {
            $user->direct_reports_count = (int) ($managerCounts->get($user->id, 0));
        }

        $availableUsers = User::whereDoesntHave('companies', function ($query) use ($company): void {
            $query->where('company_id', $company->id);
        })->get();

        $roles = Role::all();

        return view('admin.companies.edit', compact('company', 'parentCompanies', 'types', 'companyUsers', 'availableUsers', 'roles'));
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompanyAction $action): RedirectResponse
    {
        /** @var array{name: string, slug: string, type: string, parent_id?: int|string|null, is_active?: bool|int|string} $validated */
        $validated = $request->validated();

        $action->execute($company, $validated, $request->file('logo'));

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
    }

    /**
     * Assign a user to this company.
     */
    public function assignUser(AssignCompanyUserRequest $request, Company $company, AssignCompanyUserAction $action): RedirectResponse
    {
        /** @var array{user_id: int|string, roles: array<int, int|string>, job_title?: string|null, manager_id?: int|string|null} $validated */
        $validated = $request->validated();

        $action->execute($company, $validated);

        return redirect()->route('admin.companies.edit', $company)->with('success', 'User assigned to company.');
    }

    /**
     * Remove a user from this company.
     */
    public function removeUser(Company $company, int $userId, RemoveCompanyUserAction $action): RedirectResponse
    {
        $affectedCount = $action->execute($company, $userId);

        $message = 'User removed from company.';
        if ($affectedCount > 0) {
            $message .= ' '.$affectedCount.' team member(s) have been reassigned to report at the company level.';
        }

        return redirect()->route('admin.companies.edit', $company)->with('success', $message);
    }

    /**
     * Recursively flatten a nested tree into a flat array of nodes with parent references.
     *
     * @param  Collection<int, Company>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function flattenTree(Collection $nodes, ?int $parentId = null): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $flat[] = [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
                'type' => $node->type,
                'type_label' => $node->type_label,
                'parent_id' => $parentId,
                'is_active' => $node->is_active,
                'users_count' => $node->users_count ?? 0,
                'logo_url' => $node->logo ? asset('storage/'.$node->logo) : null,
            ];

            if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                $flat = array_merge($flat, $this->flattenTree($node->children, $node->id));
            }
        }

        return $flat;
    }
}
