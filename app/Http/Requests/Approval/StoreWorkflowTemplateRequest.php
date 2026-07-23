<?php

declare(strict_types=1);

namespace App\Http\Requests\Approval;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowTemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'approvable_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.type' => ['required', 'string', 'in:sequential,parallel'],
            'groups.*.steps' => ['required', 'array', 'min:1'],
            'groups.*.steps.*.label' => ['nullable', 'string', 'max:255'],
            'groups.*.steps.*.resolver_type' => ['required', 'string', 'in:N+1,specific_user,N+1_of_previous'],
            'groups.*.steps.*.resolver_payload' => ['nullable', 'array'],
            'groups.*.steps.*.resolver_payload.user_id' => ['required_if:groups.*.steps.*.resolver_type,specific_user', 'integer', 'exists:users,id'],
        ];
    }
}
