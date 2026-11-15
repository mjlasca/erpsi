<?php

use App\Modules\Catalog\Models\Product;
use App\Support\Tenancy\TenantContext;

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

    $this->actingAs($userA)
        ->get(route('catalog.products.show', $productA))
        ->assertOk();

    $this->actingAs($userA)
        ->get(route('catalog.products.show', $productB))
        ->assertNotFound();

    $this->actingAs($userB)
        ->get(route('catalog.products.index'))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.name', 'Beta product')
            ->has('products.data', 1));
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
