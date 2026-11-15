<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Tenancy\AssignTenantSubscription;
use App\Support\Tenancy\TenantUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSubscriptionController extends Controller
{
    public function index(Request $request, TenantUsage $usage): Response
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($query) => $query->where('subscription_status', $request->string('status')->value()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Platform/Subscriptions/Index', [
            'tenants' => $tenants->through(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'is_active' => $tenant->is_active,
                'subscription_status' => $tenant->subscription_status,
                'trial_ends_at' => $tenant->trial_ends_at,
                'subscription_ends_at' => $tenant->subscription_ends_at,
                'plan' => $tenant->plan?->only(['id', 'name', 'price', 'currency']),
                'usage' => $usage->summary($tenant),
            ]),
            'filters' => $request->only('search', 'status'),
            'statuses' => [
                ['value' => 'TRIALING', 'label' => 'Prueba'],
                ['value' => 'ACTIVE', 'label' => 'Activa'],
                ['value' => 'PAST_DUE', 'label' => 'Pago pendiente'],
                ['value' => 'CANCELLED', 'label' => 'Cancelada'],
                ['value' => 'SUSPENDED', 'label' => 'Suspendida'],
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant, AssignTenantSubscription $assign): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'status' => ['required', Rule::in(['TRIALING', 'ACTIVE', 'PAST_DUE', 'CANCELLED', 'SUSPENDED'])],
            'billing_email' => ['nullable', 'email', 'max:150'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $assign->execute($tenant, $validated, $request->user());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', 'Suscripción actualizada correctamente.');
    }
}
