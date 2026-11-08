<?php

use App\Models\User;
use App\Modules\Administration\Models\Role;

it('creates the administrator user from the command', function () {
    $this->artisan('erp:create-admin', [
        'email' => 'admin@example.com',
        '--name' => 'Administrador',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($user->name)->toBe('Administrador');
    expect($user->is_active)->toBeTrue();
    expect($user->role)->not->toBeNull();
    expect($user->role->name)->toBe('Administrator');
    expect($user->role->permissions)->toContain('*');
});

it('renders ERP index pages for administrators', function (string $route) {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)->get(route($route))->assertOk();
})->with([
    'catalog.products.index',
    'catalog.categories.index',
    'catalog.units.index',
    'inventory.warehouses.index',
    'inventory.stock.index',
    'inventory.movements.index',
    'inventory.lots.index',
    'purchasing.suppliers.index',
    'purchasing.purchases.index',
    'sales.customers.index',
    'sales.sales.index',
    'payments.payments.index',
    'admin.roles.index',
    'admin.users.index',
    'admin.tax-rates.index',
]);

it('protects the administrator role from losing total access', function () {
    $role = Role::factory()->administrator()->create();
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)
        ->put(route('admin.roles.update', $role), [
            'name' => 'Administrator',
            'permissions' => ['catalog.products.view'],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('name');
});
