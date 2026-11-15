<?php

namespace App\Support\Tenancy;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTenantSubscription
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, array $data, User $actor): Subscription
    {
        return DB::transaction(function () use ($tenant, $data, $actor): Subscription {
            $plan = isset($data['plan_id'])
                ? Plan::query()->findOrFail((int) $data['plan_id'])
                : null;

            $status = (string) $data['status'];
            $startsAt = isset($data['subscription_starts_at'])
                ? Carbon::parse($data['subscription_starts_at'])
                : now();

            if (in_array($status, ['ACTIVE', 'PAST_DUE'], true) && $plan === null) {
                throw ValidationException::withMessages([
                    'plan_id' => 'Debes seleccionar un plan para una suscripción activa.',
                ]);
            }

            $tenant->update([
                'plan_id' => $plan?->id,
                'subscription_status' => $status,
                'billing_email' => $data['billing_email'] ?? $tenant->billing_email,
                'trial_ends_at' => $data['trial_ends_at'] ?? $tenant->trial_ends_at,
                'subscription_starts_at' => $startsAt,
                'subscription_ends_at' => $data['subscription_ends_at'] ?? null,
            ]);

            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $data['subscription_ends_at'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'amount' => $plan === null ? 0 : $plan->price,
                'currency' => $plan === null ? $tenant->currency : $plan->currency,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->audit->record(
                'tenant.subscription_updated',
                "Suscripción de {$tenant->name} actualizada.",
                $tenant,
                $tenant,
                [
                    'plan' => $plan?->name,
                    'status' => $status,
                    'subscription_id' => $subscription->id,
                ],
            );

            return $subscription;
        });
    }
}
