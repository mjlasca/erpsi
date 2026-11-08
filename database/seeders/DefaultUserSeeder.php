<?php

namespace Database\Seeders;

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

        User::query()->updateOrCreate(
            ['email' => 'admin@erpsi.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role_id' => $administrator->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
