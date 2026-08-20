<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\OrgChart;

use Illuminate\Foundation\Http\FormRequest;

final class ReassignManagerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-org-chart') ?? false;
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
            'new_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,on_leave,inactive'],
        ];
    }
}
