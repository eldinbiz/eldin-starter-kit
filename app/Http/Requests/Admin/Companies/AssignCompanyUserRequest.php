<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class AssignCompanyUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-companies') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
