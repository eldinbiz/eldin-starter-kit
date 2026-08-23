<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ApiTokens;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApiTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-api-tokens') ?? false;
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
            'expiry' => ['nullable', 'string', 'in:30,60,90,365,never'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ];
    }
}
