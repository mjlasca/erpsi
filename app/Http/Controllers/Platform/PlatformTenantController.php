<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\CreateTenant;
use App\Support\Tenancy\TenantUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlatformTenantController extends Controller
{
    public function index(Request $request, TenantUsage $usage): Response
    {
        $tenants = Tenant::query()
            ->with(['plan', 'owner:id,name,email'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = $request->string('status')->value();
                $query->where('is_active', $status === 'active');
            })
            ->when($request->integer('plan_id'), fn ($query, int $planId) => $query->where('plan_id', $planId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => $tenants->through(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'is_active' => $tenant->is_active,
                'subscription_status' => $tenant->subscription_status,
                'created_at' => $tenant->created_at,
                'plan' => $tenant->plan?->only(['id', 'name', 'price', 'currency']),
                'owner' => $tenant->owner?->only(['id', 'name', 'email']),
                'usage' => $usage->summary($tenant),
            ]),
            'filters' => $request->only('search', 'status', 'plan_id'),
            'plans' => Plan::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Tenants/Create', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'timezones' => timezone_identifiers_list(),
            'defaults' => [
                'locale' => PlatformSetting::value('default_locale', 'es'),
                'timezone' => PlatformSetting::value('default_timezone', 'UTC'),
                'currency' => PlatformSetting::value('default_currency', 'MXN'),
                'trial_days' => (int) PlatformSetting::value('default_trial_days', '14'),
            ],
        ]);
    }

    public function store(Request $request, CreateTenant $createTenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:tenants,slug'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in(config('app.supported_locales', ['es', 'en']))],
            'timezone' => ['required', 'timezone:all'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'billing_email' => ['nullable', 'email', 'max:150'],
            'trial_days' => ['required', 'integer', 'between:0,365'],
        ]);

        $tenant = $createTenant->execute($validated, $request->user());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', 'Empresa creada correctamente.');
    }

    public function show(Tenant $tenant, TenantUsage $usage): Response
    {
        $tenant->load(['plan', 'owner', 'defaultWarehouse', 'subscriptions.plan', 'subscriptions.creator']);

        return Inertia::render('Platform/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'legal_name' => $tenant->legal_name,
                'tax_id' => $tenant->tax_id,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'locale' => $tenant->locale,
                'timezone' => $tenant->timezone,
                'currency' => $tenant->currency,
                'date_format' => $tenant->date_format,
                'is_active' => $tenant->is_active,
                'suspended_at' => $tenant->suspended_at,
                'suspension_reason' => $tenant->suspension_reason,
                'subscription_status' => $tenant->subscription_status,
                'trial_ends_at' => $tenant->trial_ends_at,
                'subscription_starts_at' => $tenant->subscription_starts_at,
                'subscription_ends_at' => $tenant->subscription_ends_at,
                'billing_email' => $tenant->billing_email,
                'created_at' => $tenant->created_at,
                'owner' => $tenant->owner?->only(['id', 'name', 'email']),
                'plan' => $tenant->plan?->only(['id', 'name', 'price', 'currency', 'billing_period']),
                'default_warehouse' => $tenant->defaultWarehouse?->only(['id', 'name', 'code']),
            ],
            'usage' => $usage->summary($tenant),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'subscriptions' => $tenant->subscriptions->sortByDesc('id')->values()->map(fn ($subscription): array => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'notes' => $subscription->notes,
                'plan' => $subscription->plan?->only(['id', 'name']),
                'creator' => $subscription->creator?->only(['id', 'name']),
                'created_at' => $subscription->created_at,
            ])->all(),
            'recentAudit' => AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->with('user:id,name')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->only(['id', 'name']),
                    'created_at' => $log->created_at,
                ])->all(),
        ]);
    }

    public function suspend(Request $request, Tenant $tenant, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'] ?? null,
        ]);

        $audit->record(
            'tenant.suspended',
            "Empresa {$tenant->name} suspendida.",
            $tenant,
            $tenant,
            ['reason' => $validated['reason'] ?? null],
        );

        return back()->with('success', 'Empresa suspendida.');
    }

    public function reactivate(Tenant $tenant, AuditLogger $audit): RedirectResponse
    {
        $tenant->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        $audit->record(
            'tenant.reactivated',
            "Empresa {$tenant->name} reactivada.",
            $tenant,
            $tenant,
        );

        return back()->with('success', 'Empresa reactivada.');
    }

    public function impersonate(Request $request, Tenant $tenant, AuditLogger $audit): RedirectResponse
    {
        if (! $tenant->is_active) {
            throw ValidationException::withMessages([
                'tenant' => 'No se puede entrar a una empresa suspendida.',
            ]);
        }

        $request->session()->put('support_tenant_id', $tenant->id);
        $request->session()->put('support_started_at', now()->toIso8601String());

        $audit->record(
            'platform.support_started',
            "Administrador de plataforma entró a {$tenant->name} en modo soporte.",
            $tenant,
            $tenant,
        );

        return redirect()
            ->route('dashboard')
            ->with('success', "Modo soporte activo en {$tenant->name}.");
    }
}
