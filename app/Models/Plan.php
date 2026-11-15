<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property string $billing_period
 * @property int|null $max_users
 * @property int|null $max_warehouses
 * @property int|null $max_products
 * @property int|null $max_monthly_sales
 * @property array<int, string>|null $features
 * @property bool $is_active
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'price',
    'currency',
    'billing_period',
    'max_users',
    'max_warehouses',
    'max_products',
    'max_monthly_sales',
    'features',
    'is_active',
    'sort_order',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_users' => 'integer',
            'max_warehouses' => 'integer',
            'max_products' => 'integer',
            'max_monthly_sales' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Tenant, $this> */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function limit(string $key): ?int
    {
        $value = $this->getAttribute($key);

        return $value === null ? null : (int) $value;
    }
}
