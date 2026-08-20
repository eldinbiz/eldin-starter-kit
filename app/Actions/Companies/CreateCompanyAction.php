<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class CreateCompanyAction
{
    /**
     * Create a new company record, optionally storing the uploaded logo.
     *
     * @param array{
     *     name: string,
     *     slug: string,
     *     type: string,
     *     parent_id?: int|string|null,
     *     is_active?: bool|int|string
     * } $data
     */
    public function execute(array $data, ?UploadedFile $logoFile = null): Company
    {
        $logoPath = null;

        if ($logoFile !== null) {
            $filename = Str::slug((string) $data['name']).'.'.$logoFile->getClientOriginalExtension();
            $logoPath = $logoFile->storeAs('logos', $filename, 'public');
        }

        return Company::create([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'type' => (string) $data['type'],
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'logo' => $logoPath,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
