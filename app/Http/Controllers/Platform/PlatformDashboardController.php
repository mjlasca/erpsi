<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $trialSoon = Tenant::query()
            ->where('subscription_status', 'TRIALING')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(14)])
            ->orderBy('trial_ends_at')
            ->limit(6)
            ->get(['id', 'name', 'trial_ends_at', 'plan_id']);

        $mrr = Tenant::query()
            ->where('is_active', true)
            ->with('plan:id,price')
            ->get()
            ->sum(fn (Tenant $tenant): float => $tenant->plan === null ? 0 : (float) $tenant->plan->price);

        return Inertia::render('Platform/Dashboard/Index', [
            'stats' => [
                'tenants' => Tenant::query()->count(),
                'active_tenants' => Tenant::query()->where('is_active', true)->count(),
                'suspended_tenants' => Tenant::query()->where('is_active', false)->count(),
                'users' => User::query()->count(),
                'trialing' => Tenant::query()->where('subscription_status', 'TRIALING')->count(),
                'platform_admins' => User::query()->where('is_platform_admin', true)->count(),
                'mrr' => number_format($mrr, 2, '.', ''),
                'plans' => Plan::query()->where('is_active', true)->count(),
            ],
            'trialSoon' => $trialSoon->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'trial_ends_at' => $tenant->trial_ends_at,
            ])->all(),
            'recentAudit' => AuditLog::query()
                ->with(['user:id,name', 'tenant:id,name'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->only(['id', 'name']),
                    'tenant' => $log->tenant?->only(['id', 'name']),
                    'created_at' => $log->created_at,
                ])->all(),
        ]);
    }
}
