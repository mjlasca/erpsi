<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\UnitType;
use App\Modules\Catalog\Policies\UnitPolicy;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property UnitType|null $unit_type
 * @property bool $allows_decimal
 * @property bool $is_active
 */
#[Fillable(['name', 'abbreviation', 'unit_type', 'allows_decimal', 'is_active'])]
#[UseFactory(UnitFactory::class)]
#[UsePolicy(UnitPolicy::class)]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit_type' => UnitType::class,
            'allows_decimal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
