<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformSupportController extends Controller
{
    public function leave(Request $request, AuditLogger $audit): RedirectResponse
    {
        $tenant = null;
        $tenantId = (int) $request->session()->get('support_tenant_id', 0);

        if ($tenantId > 0) {
            $tenant = Tenant::query()->find($tenantId);
        }

        $request->session()->forget(['support_tenant_id', 'support_started_at']);

        if ($tenant !== null) {
            $audit->record(
                'platform.support_ended',
                "Administrador de plataforma salió de {$tenant->name}.",
                $tenant,
                $tenant,
            );
        }

        return redirect()
            ->route('platform.tenants.index')
            ->with('success', 'Modo soporte finalizado.');
    }
}
