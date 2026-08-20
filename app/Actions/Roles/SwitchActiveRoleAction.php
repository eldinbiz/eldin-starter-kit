<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SwitchActiveRoleAction
{
    /**
     * Switch the user's active role context for the current active company.
     *
     * @throws HttpException
     */
    public function execute(Request $request, User $user, string $roleName): void
    {
        $company = $user->getActiveCompany();

        if (! $company) {
            abort(403, 'No active company selected.');
        }

        // Validate that the user actually has this role in the active company
        if (! $user->hasRole($roleName, $company)) {
            abort(403, 'You do not possess the '.$roleName.' role in this company.');
        }

        // Update active role in session (scoped to active company)
        $request->session()->put("active_role.{$company->id}", $roleName);
    }
}
