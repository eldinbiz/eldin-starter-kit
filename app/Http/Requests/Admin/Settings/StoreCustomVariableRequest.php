<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreCustomVariableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-system-settings') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'type' => ['required', 'in:text,numeric,json'],
            'value' => ['nullable'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $value = $this->input('value');

            if ($type === 'numeric' && ! empty($value) && ! is_numeric($value)) {
                $validator->errors()->add('value', __('The value must be numeric for Numeric type.'));
            }

            if ($type === 'json' && ! empty($value)) {
                json_decode((string) $value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validator->errors()->add('value', __('Invalid JSON format: ').json_last_error_msg());
                }
            }
        });
    }
}
