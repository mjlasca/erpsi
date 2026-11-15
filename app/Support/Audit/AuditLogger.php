<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?Tenant $tenant = null,
        array $metadata = [],
    ): AuditLog {
        $request = request();

        return AuditLog::query()->create([
            'tenant_id' => $tenant === null ? app(TenantContext::class)->id() : $tenant->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);
    }
}
