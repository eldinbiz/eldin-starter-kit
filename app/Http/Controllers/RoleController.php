<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Switch active role on the fly.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roleName = $request->string('role')->toString();
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

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

        // Redirect back with success message
        return redirect()->back()->with('success', 'Switched to '.ucfirst($roleName).' role.');
    }
}
