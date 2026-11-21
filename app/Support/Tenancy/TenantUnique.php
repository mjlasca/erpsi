<?php

namespace App\Support\Tenancy;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Builds a "unique" rule scoped to the active tenant.
 *
 * Two companies may reuse the same SKU, warehouse code or tax id, so the
 * uniqueness check must never look at rows owned by another tenant.
 */
class TenantUnique
{
    public static function make(
        string $table,
        string $column,
        mixed $ignore = null,
        string $tenantColumn = 'tenant_id',
    ): Unique {
        $rule = Rule::unique($table, $column)->ignore($ignore);

        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $rule->where($tenantColumn, $tenantId);
        }

        return $rule;
    }
}
