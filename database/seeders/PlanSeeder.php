<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Para equipos pequeños que inician su operación.',
                'price' => '299.00',
                'currency' => 'MXN',
                'billing_period' => 'MONTHLY',
                'max_users' => 5,
                'max_warehouses' => 2,
                'max_products' => 500,
                'max_monthly_sales' => 300,
                'features' => ['Dashboard', 'Inventario', 'Ventas y compras'],
                'sort_order' => 10,
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Para empresas con varias áreas y mayor volumen.',
                'price' => '799.00',
                'currency' => 'MXN',
                'billing_period' => 'MONTHLY',
                'max_users' => 20,
                'max_warehouses' => 5,
                'max_products' => 5000,
                'max_monthly_sales' => 2000,
                'features' => ['Todo lo del plan Básico', 'Devoluciones', 'Arqueos con inventario', 'Exportaciones'],
                'sort_order' => 20,
            ],
            [
                'name' => 'Empresa',
                'slug' => 'empresa',
                'description' => 'Para operaciones grandes y equipos multisede.',
                'price' => '1999.00',
                'currency' => 'MXN',
                'billing_period' => 'MONTHLY',
                'max_users' => 50,
                'max_warehouses' => 20,
                'max_products' => 50000,
                'max_monthly_sales' => 10000,
                'features' => ['Todo lo del plan Profesional', 'Soporte prioritario', 'Mayor capacidad'],
                'sort_order' => 30,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'is_active' => true],
            );
        }

        $defaultTenant = Tenant::query()->where('slug', 'default')->first();
        $professional = Plan::query()->where('slug', 'profesional')->first();

        if ($defaultTenant !== null && $professional !== null) {
            $defaultTenant->update([
                'plan_id' => $defaultTenant->plan_id ?? $professional->id,
                'subscription_status' => $defaultTenant->subscription_status ?: 'TRIALING',
                'trial_ends_at' => $defaultTenant->trial_ends_at ?? now()->addDays(14),
                'subscription_starts_at' => $defaultTenant->subscription_starts_at ?? now(),
            ]);

            Subscription::query()->firstOrCreate(
                [
                    'tenant_id' => $defaultTenant->id,
                    'plan_id' => $defaultTenant->plan_id,
                    'starts_at' => $defaultTenant->subscription_starts_at,
                ],
                [
                    'status' => $defaultTenant->subscription_status,
                    'trial_ends_at' => $defaultTenant->trial_ends_at,
                    'amount' => 0,
                    'currency' => $defaultTenant->currency,
                    'notes' => 'Suscripción inicial de demostración.',
                ],
            );
        }
    }
}
