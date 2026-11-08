<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Administration\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'erp:create-admin
        {email : Correo del administrador}
        {--name= : Nombre del administrador}
        {--password= : Contraseña (se solicita de forma segura si se omite)}';

    protected $description = 'Crea o actualiza el usuario administrador del ERP';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: str($email)->before('@')->headline());
        $password = $this->option('password') ?: $this->secret('Contraseña');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', Password::defaults() ?? Password::min(8)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permissions' => ['*'], 'is_active' => true],
        );

        $role->update(['permissions' => ['*'], 'is_active' => true]);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role_id' => $role->id,
                'is_active' => true,
            ],
        );

        $this->info("Administrador disponible: {$user->email}");

        return self::SUCCESS;
    }
}
