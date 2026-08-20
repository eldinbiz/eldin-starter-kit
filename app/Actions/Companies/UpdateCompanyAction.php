<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UpdateCompanyAction
{
    /**
     * Update an existing company, replacing the logo file if a new one is uploaded.
     *
     * @param array{
     *     name: string,
     *     slug: string,
     *     type: string,
     *     parent_id?: int|string|null,
     *     is_active?: bool|int|string
     * } $data
     */
    public function execute(Company $company, array $data, ?UploadedFile $logoFile = null): Company
    {
        $logoPath = $company->logo;

        if ($logoFile !== null) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $filename = Str::slug((string) $data['name']).'.'.$logoFile->getClientOriginalExtension();
            $logoPath = $logoFile->storeAs('logos', $filename, 'public');
        }

        $company->update([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'type' => (string) $data['type'],
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'logo' => $logoPath,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $company;
    }
}
