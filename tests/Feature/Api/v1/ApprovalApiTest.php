<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Company;
use App\Models\Approval\ApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists approval requests for a given company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->system()->create();
    $user->companies()->attach($company->id);
    
    // Create some approvals
    ApprovalRequest::factory()->count(3)->create([
        'company_id' => $company->id,
    ]);

    $token = $user->createToken('System API')->plainTextToken;

    $response = $this->getJson('/api/v1/approvals', [
        'Authorization' => 'Bearer ' . $token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});
