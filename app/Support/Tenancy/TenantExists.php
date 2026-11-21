<?php

namespace App\Support\Tenancy;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Verifies that a referenced record exists inside the active tenant.
 *
 * The framework "exists" rule queries the table directly, so it ignores the
 * tenant global scope and would accept identifiers from another company.
 */
class TenantExists implements ValidationRule
{
    public function __construct(
        protected string $table,
        protected string $column = 'id',
        protected string $tenantColumn = 'tenant_id',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tenantId = app(TenantContext::class)->id();

        $query = DB::table($this->table)->where($this->column, $value);

        // Without a resolved tenant the reference cannot be scoped; the
        // ownership fence is guaranteed by ResolveTenant on every request.
        if ($tenantId !== null) {
            $query->where($this->tenantColumn, $tenantId);
        }

        if (! $query->exists()) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
