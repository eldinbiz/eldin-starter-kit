<?php

declare(strict_types=1);

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkflowTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-approval-workflows')
            || ($this->user()?->hasPlatformAccess() ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'approvable_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'scope' => ['nullable', 'string', 'in:this_company,this_and_descendants'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.type' => ['required', 'string', 'in:sequential,parallel'],
            'groups.*.min_approvals' => ['nullable', 'integer', 'min:1'],
            'groups.*.steps' => ['required', 'array', 'min:1'],
            'groups.*.steps.*.label' => ['nullable', 'string', 'max:255'],
            'groups.*.steps.*.resolver_type' => ['required', 'string', 'in:N+1,specific_user,N+1_of_previous'],
            'groups.*.steps.*.resolver_payload' => ['nullable', 'array'],
            'groups.*.steps.*.resolver_payload.user_id' => ['required_if:groups.*.steps.*.resolver_type,specific_user', 'integer', 'exists:users,id'],
        ];
    }
}
