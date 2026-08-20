<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateCompanyRequest extends FormRequest
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
        /** @var Company $company */
        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->id : (int) $company;

        $types = config('company.types', ['operating' => 'Operating Company']);
        $allowedTypes = implode(',', array_keys((array) $types));

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('companies', 'slug')->ignore($companyId)],
            'type' => ['required', 'string', 'in:'.$allowedTypes],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance with custom parent cycle validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $company = $this->route('company');
            if (! $company instanceof Company) {
                $company = Company::find($company);
            }

            if (! $company) {
                return;
            }

            $newParentId = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;

            // Prevent self-referencing
            if ($newParentId === $company->id) {
                $v->errors()->add('parent_id', 'A company cannot be its own parent.');

                return;
            }

            // Cycle detection: prevent setting a subsidiary as the parent company
            if ($newParentId !== null) {
                $newParent = Company::find($newParentId);

                if ($newParent && $newParent->isDescendantOf($company)) {
                    $v->errors()->add('parent_id', 'Cannot set a subsidiary as the parent company.');
                }
            }
        });
    }
}
