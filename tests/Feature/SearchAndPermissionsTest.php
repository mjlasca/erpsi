<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Models\Customer;
use Inertia\Testing\AssertableInertia as Assert;

it('searches products through the internal endpoint', function () {
    $user = User::factory()->administrator()->create();
    Product::factory()->create(['name' => 'Producto buscado']);
    Product::factory()->create(['name' => 'Otro producto']);

    $this->actingAs($user)
        ->get(route('catalog.products.search', ['search' => 'buscado']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Producto buscado');
});

it('searches customers through the internal endpoint', function () {
    $user = User::factory()->administrator()->create();
    Customer::factory()->create(['first_name' => 'Ana', 'last_name' => 'Buscada']);
    Customer::factory()->create(['first_name' => 'Luis', 'last_name' => 'Otro']);

    $this->actingAs($user)
        ->get(route('sales.customers.search', ['search' => 'Buscada']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches suppliers through the internal endpoint', function () {
    $user = User::factory()->administrator()->create();
    Supplier::factory()->create(['name' => 'Proveedor buscado']);
    Supplier::factory()->create(['name' => 'Proveedor otro']);

    $this->actingAs($user)
        ->get(route('purchasing.suppliers.search', ['search' => 'buscado']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches available lots through the internal endpoint', function () {
    $user = User::factory()->administrator()->create();
    $warehouse = Warehouse::factory()->create();
    InventoryLot::factory()->forWarehouse($warehouse)->create(['code' => 'LOT-BUSCADO']);

    $this->actingAs($user)
        ->get(route('inventory.lots.search', ['search' => 'BUSCADO']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shares the can permission object with Inertia pages', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can', fn ($can) => $can['catalog.products.create'] === true
                && $can['purchasing.purchases.pay'] === true));
});

it('shares false permissions for users without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can', fn ($can) => $can['catalog.products.create'] === false));
});
