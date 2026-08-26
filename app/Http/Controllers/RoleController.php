<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Roles\SwitchActiveRoleAction;
use App\Http\Requests\SwitchRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Switch active role on the fly.
     */
    public function switch(SwitchRoleRequest $request, SwitchActiveRoleAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $roleName = $request->string('role')->toString();

        $action->execute($user, $roleName);

        // Redirect back with success message
        return redirect()->back()->with('success', 'Switched to '.ucfirst($roleName).' role.');
    }
}
