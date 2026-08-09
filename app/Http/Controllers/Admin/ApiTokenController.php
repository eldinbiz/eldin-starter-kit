<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get system users (bots) and their tokens
        $systemUsers = User::system()->with('tokens')->get();
        return view('admin.api-tokens.index', compact('systemUsers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create or find a system user for this token
        // We namespace it to avoid collisions. 
        $botEmail = 'bot.' . Str::slug($request->name) . '@system.local';
        
        $bot = User::firstOrCreate(
            ['email' => $botEmail],
            [
                'name' => $request->name . ' (System Bot)',
                'password' => bcrypt(Str::random(32)),
                'is_system' => true,
                'auth_method' => 'local',
            ]
        );

        // Make sure it's a system bot
        if (!$bot->is_system) {
            $bot->update(['is_system' => true]);
        }

        // We can assign the bot to a specific company if requested, or grant platform access
        // For now, API tokens often need platform access or specific scoping.
        // If the token scope requires a company, it can be passed in the API header.
        // Let's grant the bot platform access so it can act on any company passed in the header.
        $platformRole = \App\Models\Role::where('name', 'platform-admin')->whereNull('company_id')->first();
        if ($platformRole && !$bot->hasPlatformAccess()) {
            $bot->roles()->attach($platformRole->id);
        }

        // Create the token
        $token = $bot->createToken($request->name);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token generated successfully.',
            'api_token' => $token->plainTextToken, // Pass the plain text token to show ONCE
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $token = PersonalAccessToken::findOrFail($id);
        
        // Ensure we are only deleting system tokens for safety, or any token if admin.
        $token->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token revoked successfully.',
        ]);
    }
}
