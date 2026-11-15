<?php

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantLimitChecker;
use App\Support\Tenancy\TenantProvisioner;
use Illuminate\Validation\ValidationException;

function platformAdmin(): User
{
    return User::factory()->create([
        'name' => 'Platform Admin',
        'is_platform_admin' => true,
    ]);
}

function samplePlan(int $maxProducts = 100): Plan
{
    return Plan::query()->create([
        'name' => 'Test Plan '.fake()->unique()->numberBetween(1, 9999),
        'slug' => 'test-plan-'.fake()->unique()->numberBetween(1, 9999),
        'price' => '100.00',
        'currency' => 'MXN',
        'billing_period' => 'MONTHLY',
        'max_users' => 5,
        'max_warehouses' => 2,
        'max_products' => $maxProducts,
        'max_monthly_sales' => 100,
        'features' => ['Test'],
        'is_active' => true,
    ]);
}

it('renders the platform console without requiring a tenant membership', function () {
    $admin = platformAdmin();

    foreach ([
        'platform.dashboard',
        'platform.tenants.index',
        'platform.tenants.create',
        'platform.plans.index',
        'platform.subscriptions.index',
        'platform.users.index',
        'platform.audit.index',
        'platform.settings.edit',
    ] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

it('creates a company with roles, warehouse, owner membership and subscription', function () {
    $admin = platformAdmin();
    $owner = User::factory()->create();
    $plan = samplePlan();

    $this->actingAs($admin)
        ->post(route('platform.tenants.store'), [
            'name' => 'Empresa Nueva',
            'slug' => 'empresa-nueva',
            'legal_name' => 'Empresa Nueva SA',
            'tax_id' => 'NEW-001',
            'email' => 'empresa@example.com',
            'phone' => '555-0100',
            'address' => 'Calle 1',
            'locale' => 'es',
            'timezone' => 'America/Mexico_City',
            'currency' => 'MXN',
            'plan_id' => $plan->id,
            'owner_id' => $owner->id,
            'billing_email' => 'billing@example.com',
            'trial_days' => 14,
        ])
        ->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'empresa-nueva')->firstOrFail();

    expect($tenant->name)->toBe('Empresa Nueva');
    expect($tenant->plan_id)->toBe($plan->id);
    expect($tenant->subscription_status)->toBe('TRIALING');
    expect($tenant->trial_ends_at)->not->toBeNull();
    expect($tenant->owner_id)->toBe($owner->id);
    expect($tenant->default_warehouse_id)->not->toBeNull();
    expect($owner->tenants()->whereKey($tenant->id)->exists())->toBeTrue();

    app(TenantContext::class)->run($tenant, function (): void {
        expect(Role::query()->where('name', 'Administrator')->exists())->toBeTrue();
        expect(Role::query()->where('name', 'Sales')->exists())->toBeTrue();
        expect(Warehouse::query()->where('code', 'MAIN')->exists())->toBeTrue();
    });

    $this->assertDatabaseHas('subscriptions', [
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'TRIALING',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'tenant.created',
    ]);
});

it('suspends, reactivates and audits a tenant', function () {
    $admin = platformAdmin();
    $tenant = makeTenant('suspend-me');

    $this->actingAs($admin)
        ->post(route('platform.tenants.suspend', $tenant), [
            'reason' => 'Falta de pago',
        ])
        ->assertRedirect();

    $tenant->refresh();
    expect($tenant->is_active)->toBeFalse();
    expect($tenant->suspension_reason)->toBe('Falta de pago');
    expect(AuditLog::query()->where('action', 'tenant.suspended')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->post(route('platform.tenants.reactivate', $tenant))
        ->assertRedirect();

    $tenant->refresh();
    expect($tenant->is_active)->toBeTrue();
    expect($tenant->suspended_at)->toBeNull();
    expect(AuditLog::query()->where('action', 'tenant.reactivated')->exists())->toBeTrue();
});

it('allows platform support mode and returns to platform administration', function () {
    $admin = platformAdmin();
    $tenant = makeTenant('support-tenant');

    app(TenantProvisioner::class)->provision($tenant);

    $this->actingAs($admin)
        ->post(route('platform.tenants.impersonate', $tenant))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('supportMode', true)
            ->where('supportTenant.id', $tenant->id));

    expect(AuditLog::query()->where('action', 'platform.support_started')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->post(route('platform.support.leave'))
        ->assertRedirect(route('platform.tenants.index'));

    expect(session('support_tenant_id'))->toBeNull();
    expect(AuditLog::query()->where('action', 'platform.support_ended')->exists())->toBeTrue();
});

it('lists global users and protects the last platform administrator', function () {
    $admin = platformAdmin();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('platform.users.toggle-platform-admin', $other))
        ->assertRedirect();

    expect($other->refresh()->is_platform_admin)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('platform.users.toggle-platform-admin', $admin))
        ->assertRedirect();

    expect($admin->refresh()->is_platform_admin)->toBeFalse();

    $this->actingAs($other)
        ->patch(route('platform.users.toggle-platform-admin', $other))
        ->assertSessionHasErrors('platform_admin');

    expect($other->refresh()->is_platform_admin)->toBeTrue();
});

it('creates and updates plans and enforces plan limits', function () {
    $admin = platformAdmin();

    $this->actingAs($admin)
        ->post(route('platform.plans.store'), [
            'name' => 'Plan Limitado',
            'slug' => 'plan-limitado',
            'description' => 'Plan de prueba',
            'price' => '50.00',
            'currency' => 'MXN',
            'billing_period' => 'MONTHLY',
            'max_users' => 1,
            'max_warehouses' => 1,
            'max_products' => 1,
            'max_monthly_sales' => 1,
            'features' => "Una característica\nOtra característica",
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->assertRedirect(route('platform.plans.index'));

    $plan = Plan::query()->where('slug', 'plan-limitado')->firstOrFail();
    expect($plan->features)->toHaveCount(2);

    $tenant = makeTenant('limited-tenant');
    $tenant->update(['plan_id' => $plan->id]);

    app(TenantContext::class)->run($tenant, function () use ($tenant): void {
        Product::factory()->create();

        expect(fn () => app(TenantLimitChecker::class)->assertProducts($tenant))
            ->toThrow(ValidationException::class);
    });
});
