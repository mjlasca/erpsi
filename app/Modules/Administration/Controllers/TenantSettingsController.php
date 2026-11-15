<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function edit(Request $request, TenantContext $context): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $tenant = $context->tenant();

        abort_if($tenant === null, 403);

        return Inertia::render('Administration/Settings/Edit', [
            'tenant' => $tenant->only([
                'id',
                'name',
                'slug',
                'legal_name',
                'tax_id',
                'email',
                'phone',
                'address',
                'logo_path',
                'locale',
                'timezone',
                'currency',
                'date_format',
                'week_starts_on',
                'invoice_prefix',
                'purchase_prefix',
                'sale_return_prefix',
                'purchase_return_prefix',
                'cash_session_prefix',
                'default_warehouse_id',
            ]),
            'warehouses' => Warehouse::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'supportedLocales' => config('app.supported_locales', ['es', 'en']),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request, TenantContext $context): RedirectResponse
    {
        $this->authorize('update', $context->tenant() ?? Tenant::class);

        $tenant = $context->tenant();

        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in(config('app.supported_locales', ['es', 'en']))],
            'timezone' => ['required', 'timezone:all'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'date_format' => ['required', 'string', 'max:20'],
            'week_starts_on' => ['required', 'integer', 'between:0,6'],
            'invoice_prefix' => ['required', 'string', 'max:20', 'alpha_num'],
            'purchase_prefix' => ['required', 'string', 'max:20', 'alpha_num'],
            'sale_return_prefix' => ['required', 'string', 'max:20', 'alpha_num'],
            'purchase_return_prefix' => ['required', 'string', 'max:20', 'alpha_num'],
            'cash_session_prefix' => ['required', 'string', 'max:20', 'alpha_num'],
            'default_warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', $tenant->id),
            ],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('tenant-logos', 'public');
            $validated['logo_path'] = $path;

            if ($tenant->logo_path !== null && ! str_starts_with($tenant->logo_path, 'http')) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
        }

        unset($validated['logo']);

        $tenant->update($validated);
        app()->setLocale($tenant->locale);

        return back()->with('success', __('messages.settings_updated'));
    }
}
