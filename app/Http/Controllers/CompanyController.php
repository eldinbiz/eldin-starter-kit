<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Switch the currently active company.
     */
    public function switch(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('company_user', 'company_id')->where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                }),
            ],
        ]);

        $companyId = (int) $request->input('company_id');

        session(['active_company_id' => $companyId]);

        return redirect()->back()->with('success', 'Company switched successfully.');
    }
}
