<?php

use App\Http\Middleware\ResolveTenant;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;

it('scopes tenant data and prevents cross-tenant access', function () {
    $tenantA = makeTenant('alpha');
    $tenantB = makeTenant('beta');
    $userA = makeTenantUser($tenantA);
    $userB = makeTenantUser($tenantB);

    $productA = app(TenantContext::class)->run(
        $tenantA,
        fn () => Product::factory()->create(['name' => 'Alpha product']),
    );
    $productB = app(TenantContext::class)->run(
        $tenantB,
        fn () => Product::factory()->create(['name' => 'Beta product']),
    );

    expect($productA->tenant_id)->toBe($tenantA->id);
    expect($productB->tenant_id)->toBe($tenantB->id);

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->get(route('catalog.products.show', $productA))
        ->assertOk();

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->get(route('catalog.products.show', $productB))
        ->assertNotFound();

    app(TenantContext::class)->set(null);

    $this->actingAs($userB)
        ->get(route('catalog.products.index'))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.name', 'Beta product')
            ->has('products.data', 1));
});

it('resolves the tenant before binding route models', function () {
    app(Kernel::class)->bootstrap();

    $group = app('router')->getMiddlewareGroups()['web'];

    $tenantPosition = array_search(ResolveTenant::class, $group, true);
    $bindingPosition = array_search(SubstituteBindings::class, $group, true);

    expect($tenantPosition)->toBeInt()
        ->and($bindingPosition)->toBeInt()
        ->and($tenantPosition)->toBeLessThan($bindingPosition);
});

it('blocks route model binding across companies', function () {
    $tenantA = makeTenant('binding-a');
    $tenantB = makeTenant('binding-b');
    $userA = makeTenantUser($tenantA);

    $saleB = app(TenantContext::class)->run(
        $tenantB,
        fn () => Sale::factory()->create(),
    );

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->get(route('sales.sales.show', $saleB))
        ->assertNotFound();

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->delete(route('sales.sales.destroy', $saleB))
        ->assertNotFound();

    expect(Sale::query()->withoutGlobalScope('tenant')->whereKey($saleB->id)->exists())->toBeTrue();
});

it('denies policy access to models from another company', function () {
    $tenantA = makeTenant('policy-a');
    $tenantB = makeTenant('policy-b');
    $userA = makeTenantUser($tenantA);

    $saleB = app(TenantContext::class)->run(
        $tenantB,
        fn () => Sale::factory()->create(),
    );

    app(TenantContext::class)->run($tenantA, function () use ($userA, $saleB): void {
        expect($userA->can('view', $saleB))->toBeFalse()
            ->and($userA->can('update', $saleB))->toBeFalse()
            ->and($userA->can('delete', $saleB))->toBeFalse();
    });
});

it('rejects references to records from another company', function () {
    $tenantA = makeTenant('refs-a');
    $tenantB = makeTenant('refs-b');
    $userA = makeTenantUser($tenantA);

    [$warehouseA, $productA] = app(TenantContext::class)->run($tenantA, fn (): array => [
        Warehouse::factory()->create(),
        Product::factory()->create(),
    ]);

    $customerB = app(TenantContext::class)->run(
        $tenantB,
        fn () => Customer::factory()->create(),
    );

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->post(route('sales.sales.store'), [
            'customer_id' => $customerB->id,
            'warehouse_id' => $warehouseA->id,
            'sale_date' => now()->toDateString(),
            'details' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 1,
                    'unit_price' => 10,
                ],
            ],
        ])
        ->assertSessionHasErrors('customer_id');

    expect(Sale::query()->withoutGlobalScope('tenant')->count())->toBe(0);
});

it('rejects payment allocations to documents from another company', function () {
    $tenantA = makeTenant('alloc-a');
    $tenantB = makeTenant('alloc-b');
    $userA = makeTenantUser($tenantA);

    $paymentA = app(TenantContext::class)->run(
        $tenantA,
        fn () => Payment::factory()->create(),
    );

    $saleB = app(TenantContext::class)->run(
        $tenantB,
        fn () => Sale::factory()->confirmed()->create(),
    );

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->post(route('payments.allocations.store', $paymentA), [
            'sale_id' => $saleB->id,
            'amount' => 5,
        ])
        ->assertSessionHasErrors('sale_id');

    expect(PaymentAllocation::query()->withoutGlobalScope('tenant')->count())->toBe(0);
});

it('allows the same unique values across companies', function () {
    $tenantA = makeTenant('unique-a');
    $tenantB = makeTenant('unique-b');
    $userA = makeTenantUser($tenantA);
    $userB = makeTenantUser($tenantB);

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->post(route('inventory.warehouses.store'), ['code' => 'WH-01', 'name' => 'Principal'])
        ->assertSessionHasNoErrors();

    app(TenantContext::class)->set(null);

    $this->actingAs($userB)
        ->post(route('inventory.warehouses.store'), ['code' => 'WH-01', 'name' => 'Principal'])
        ->assertSessionHasNoErrors();

    expect(Warehouse::query()->withoutGlobalScope('tenant')->count())->toBe(2);

    app(TenantContext::class)->set(null);

    $this->actingAs($userA)
        ->post(route('inventory.warehouses.store'), ['code' => 'WH-01', 'name' => 'Sucursal'])
        ->assertSessionHasErrors('code');
});

it('uses the user locale preference over the tenant locale', function () {
    $tenant = makeTenant('locale');
    $tenant->update(['locale' => 'es']);
    $user = makeTenantUser($tenant);
    $user->update(['locale' => 'en']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('tenant.id', $tenant->id));
});

it('persists user language and timezone preferences', function () {
    $tenant = makeTenant('preferences');
    $user = makeTenantUser($tenant);

    $this->actingAs($user)
        ->put(route('preferences.update'), [
            'locale' => 'en',
            'timezone' => 'America/Mexico_City',
        ])
        ->assertRedirect();

    expect($user->refresh()->locale)->toBe('en');
    expect($user->timezone)->toBe('America/Mexico_City');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('switches between companies for a multi-tenant user', function () {
    $tenantA = makeTenant('switch-a');
    $tenantB = makeTenant('switch-b');
    $user = makeTenantUser($tenantA);
    $user->tenants()->attach($tenantB->id, [
        'role_id' => $user->role_id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('tenant.switch', $tenantB))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('tenant.id', $tenantB->id));
});

it('lists only users that belong to the active company', function () {
    $tenantA = makeTenant('users-a');
    $tenantB = makeTenant('users-b');
    $admin = makeTenantUser($tenantA);
    $other = makeTenantUser($tenantB);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $admin->id));

    $this->actingAs($admin)
        ->get(route('admin.users.show', $other))
        ->assertForbidden();
});

it('renders the SaaS settings, returns, cash, charts and export pages', function () {
    $tenant = makeTenant('pages');
    $user = makeTenantUser($tenant, null, true);

    foreach ([
        'dashboard',
        'sales.returns.index',
        'purchasing.returns.index',
        'cash.sessions.index',
        'cash.sessions.create',
        'admin.settings.edit',
        'preferences.edit',
        'exports.index',
        'platform.settings.edit',
    ] as $route) {
        $this->actingAs($user)->get(route($route))->assertOk();
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('charts.salesPurchases')
            ->has('charts.cashFlow'));
});

it('keeps the platform timezone independent from tenant timezone', function () {
    $tenant = makeTenant('timezone');
    $tenant->update(['timezone' => 'America/Mexico_City']);
    $user = makeTenantUser($tenant);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('tenant.timezone', 'America/Mexico_City')
            ->where('timezone', 'America/Mexico_City'));
});
