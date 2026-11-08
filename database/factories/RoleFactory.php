<?php

namespace Database\Factories;

use App\Modules\Administration\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'permissions' => [],
            'is_active' => true,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Administrator',
            'permissions' => ['*'],
        ]);
    }

    public function sales(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Sales',
            'permissions' => [
                'catalog.*.view',
                'sales.*',
                'payments.payments.view',
                'payments.payments.create',
                'payments.allocations.manage',
                'inventory.stock.view',
            ],
        ]);
    }

    public function inventoryManager(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Inventory Manager',
            'permissions' => [
                'catalog.*.view',
                'inventory.*',
                'purchasing.*',
                'sales.sales.view',
                'inventory.stock.view',
            ],
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (): array => [
            'permissions' => $permissions,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
