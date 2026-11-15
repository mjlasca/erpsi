<?php

namespace App\Support\Tenancy;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTenant
{
    public function __construct(
        protected TenantProvisioner $provisioner,
        protected AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Tenant
    {
        return DB::transaction(function () use ($data, $actor): Tenant {
            $plan = isset($data['plan_id'])
                ? Plan::query()->findOrFail((int) $data['plan_id'])
                : Plan::query()->where('is_active', true)->orderBy('sort_order')->first();

            $trialDays = (int) ($data['trial_days'] ?? PlatformSetting::value('default_trial_days', '14') ?? 14);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'legal_name' => $data['legal_name'] ?? $data['name'],
                'tax_id' => $data['tax_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'locale' => $data['locale'] ?? PlatformSetting::value('default_locale', 'es'),
                'timezone' => $data['timezone'] ?? PlatformSetting::value('default_timezone', 'UTC'),
                'currency' => $data['currency'] ?? PlatformSetting::value('default_currency', 'MXN'),
                'date_format' => $data['date_format'] ?? 'd/m/Y',
                'week_starts_on' => 1,
                'plan_id' => $plan?->id,
                'owner_id' => $data['owner_id'] ?? null,
                'billing_email' => $data['billing_email'] ?? null,
                'subscription_status' => 'TRIALING',
                'trial_ends_at' => now()->addDays(max(0, $trialDays)),
                'subscription_starts_at' => now(),
                'is_active' => true,
            ]);

            $tenant = $this->provisioner->provision($tenant);

            if (! empty($data['owner_id'])) {
                $owner = User::query()->findOrFail((int) $data['owner_id']);
                $roleId = app(TenantContext::class)->run($tenant, fn () => Role::query()
                    ->where('name', 'Administrator')
                    ->value('id'));

                if ($roleId === null) {
                    throw ValidationException::withMessages([
                        'owner_id' => 'No se pudo asignar el rol administrador de la empresa.',
                    ]);
                }

                $owner->tenants()->syncWithoutDetaching([
                    $tenant->id => [
                        'role_id' => $roleId,
                        'is_default' => true,
                        'is_active' => true,
                    ],
                ]);

                $tenant->update(['owner_id' => $owner->id]);
            }

            if ($plan !== null) {
                Subscription::query()->create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'TRIALING',
                    'starts_at' => now(),
                    'trial_ends_at' => $tenant->trial_ends_at,
                    'amount' => 0,
                    'currency' => $tenant->currency,
                    'notes' => 'Inicio de prueba del tenant.',
                    'created_by' => $actor->id,
                ]);
            }

            $this->audit->record(
                'tenant.created',
                "Empresa {$tenant->name} creada.",
                $tenant,
                $tenant,
                ['plan' => $plan?->name, 'owner_id' => $data['owner_id'] ?? null],
            );

            return $tenant->refresh();
        });
    }
}
