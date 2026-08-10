<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'event',
        'name',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that owns the template.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Scope a query to only include global default templates.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope a query to include templates for a specific company.
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
     * Scope a query to only include active templates.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
