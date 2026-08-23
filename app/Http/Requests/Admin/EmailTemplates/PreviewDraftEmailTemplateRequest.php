<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EmailTemplates;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewDraftEmailTemplateRequest extends FormRequest
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
            'subject' => ['required', 'string'],
            'body_html' => ['required', 'string'],
            'company_id' => ['nullable', 'integer'],
        ];
    }
}
