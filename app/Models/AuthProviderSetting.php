<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthProviderSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'is_enabled',
        'allow_auto_provisioning',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'allow_auto_provisioning' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get setting record by provider name.
     */
    public static function forProvider(string $provider): ?self
    {
        return static::where('provider', $provider)->first();
    }
}
