<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCompanyRequest extends FormRequest
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
        $types = config('company.types', ['operating' => 'Operating Company']);
        $allowedTypes = implode(',', array_keys((array) $types));

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:companies,slug'],
            'type' => ['required', 'string', 'in:'.$allowedTypes],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
