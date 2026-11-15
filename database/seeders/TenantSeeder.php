<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Empresa principal',
                'legal_name' => 'Empresa principal',
                'locale' => PlatformSetting::value('default_locale', 'es') ?? 'es',
                'timezone' => PlatformSetting::value('default_timezone', 'UTC') ?? 'UTC',
                'currency' => 'MXN',
                'date_format' => 'd/m/Y',
                'week_starts_on' => 1,
                'invoice_prefix' => 'INV',
                'purchase_prefix' => 'COMP',
                'sale_return_prefix' => 'RDEV',
                'purchase_return_prefix' => 'RCOMP',
                'cash_session_prefix' => 'CAJA',
                'is_active' => true,
            ],
        );
    }
}
