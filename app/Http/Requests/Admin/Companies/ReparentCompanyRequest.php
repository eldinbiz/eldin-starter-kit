<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class ReparentCompanyRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'new_parent_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }
}
