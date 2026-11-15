<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Plans/Index', [
            'plans' => Plan::query()
                ->withCount('tenants')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Plan $plan): array => $this->planData($plan))
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Plans/Create');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $plan = Plan::query()->create($this->validated($request));

        $audit->record('plan.created', "Plan {$plan->name} creado.", $plan);

        return redirect()
            ->route('platform.plans.index')
            ->with('success', 'Plan creado correctamente.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Platform/Plans/Edit', [
            'plan' => $this->planData($plan),
        ]);
    }

    public function update(Request $request, Plan $plan, AuditLogger $audit): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));

        $audit->record('plan.updated', "Plan {$plan->name} actualizado.", $plan);

        return redirect()
            ->route('platform.plans.index')
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function destroy(Plan $plan, AuditLogger $audit): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', 'No se puede eliminar un plan con empresas asignadas.');
        }

        $audit->record('plan.deleted', "Plan {$plan->name} eliminado.", $plan);
        $plan->delete();

        return redirect()
            ->route('platform.plans.index')
            ->with('success', 'Plan eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('plans', 'slug')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'billing_period' => ['required', Rule::in(['MONTHLY', 'YEARLY'])],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_warehouses' => ['nullable', 'integer', 'min:0'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_monthly_sales' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['features'] = collect(explode("\n", (string) ($validated['features'] ?? '')))
            ->map(fn (string $feature): string => trim($feature))
            ->filter()
            ->values()
            ->all();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    protected function planData(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price' => $plan->price,
            'currency' => $plan->currency,
            'billing_period' => $plan->billing_period,
            'max_users' => $plan->max_users,
            'max_warehouses' => $plan->max_warehouses,
            'max_products' => $plan->max_products,
            'max_monthly_sales' => $plan->max_monthly_sales,
            'features' => $plan->features ?? [],
            'features_text' => implode("\n", $plan->features ?? []),
            'is_active' => $plan->is_active,
            'sort_order' => $plan->sort_order,
            'tenants_count' => $plan->tenants_count ?? $plan->tenants()->count(),
        ];
    }
}
