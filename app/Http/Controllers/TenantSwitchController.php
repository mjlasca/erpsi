<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(
            $tenant->is_active && $request->user()?->belongsToTenant($tenant->id),
            403,
        );

        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->forget(['support_tenant_id', 'support_started_at']);

        return redirect()
            ->route('dashboard')
            ->with('success', __('messages.tenant_switched'));
    }
}
