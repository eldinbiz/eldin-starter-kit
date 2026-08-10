<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get the company that owns the setting.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Scope a query to only include global settings (company_id is null).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope a query to include settings for a specific company.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        if ($companyId === null) {
            return $query->whereNull('company_id');
        }

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to only include settings belonging to a group.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to only include custom variables.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCustomVariables(Builder $query): Builder
    {
        return $query->where('group', 'custom');
    }

    /**
     * Get cast typed value accessor.
     *
     * @return Attribute<mixed, mixed>
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes): mixed {
                $raw = $attributes['value'] ?? null;
                $type = $attributes['type'] ?? 'text';

                if ($raw === null) {
                    return null;
                }

                return match ($type) {
                    'numeric' => is_numeric($raw)
                        ? (str_contains((string) $raw, '.') ? (float) $raw : (int) $raw)
                        : $raw,
                    'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                    'json' => is_string($raw) ? (json_decode($raw, true) ?? $raw) : $raw,
                    'encrypted' => $this->decryptValue((string) $raw),
                    default => (string) $raw,
                };
            }
        );
    }

    /**
     * Safely decrypt encrypted setting value.
     */
    private function decryptValue(string $encrypted): ?string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }
}
