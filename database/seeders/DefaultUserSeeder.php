<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Administration\Models\Role;
use Illuminate\Database\Seeder;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Role::query()
            ->where('name', 'Administrator')
            ->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'default')->firstOrFail();

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@erpsi.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role_id' => $administrator->id,
                'is_active' => true,
                'is_platform_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => [
                'role_id' => $administrator->id,
                'is_default' => true,
                'is_active' => true,
            ],
        ]);
    }
}
