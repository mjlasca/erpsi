<?php

namespace App\Http\Middleware;

use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordSupportActivity
{
    public function __construct(
        protected TenantContext $context,
        protected AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->context->isSupportMode()
            || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            || $response->getStatusCode() >= 400
            || $request->routeIs('platform.support.leave', 'platform.tenants.impersonate')) {
            return $response;
        }

        $tenant = $this->context->tenant();

        if ($tenant !== null) {
            $this->audit->record(
                'platform.support_action',
                'Acción realizada en modo soporte.',
                $tenant,
                $tenant,
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route' => $request->route()?->getName(),
                ],
            );
        }

        return $response;
    }
}
