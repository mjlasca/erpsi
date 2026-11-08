import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { AdminUser } from '@/types/models';
import { toSelectOptions } from '@/types/erp';
import { index, update } from '@/routes/admin/users';

export default function UsersEdit({
    user,
    roles,
}: {
    user: AdminUser;
    roles: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceForm
                title={`Editar ${user.name}`}
                description="Actualiza los datos de acceso y el rol."
                action={update.url(user.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                initial={user as unknown as Record<string, unknown>}
                fields={[
                    {
                        name: 'name',
                        label: 'Nombre',
                        required: true,
                    },
                    {
                        name: 'email',
                        label: 'Correo',
                        type: 'email',
                        required: true,
                    },
                    {
                        name: 'role_id',
                        label: 'Rol',
                        type: 'select',
                        options: toSelectOptions(roles),
                        placeholder: 'Sin rol',
                    },
                    {
                        name: 'password',
                        label: 'Nueva contraseña',
                        type: 'password',
                        help: 'Déjala vacía para conservar la contraseña actual.',
                    },
                    {
                        name: 'password_confirmation',
                        label: 'Confirmar contraseña',
                        type: 'password',
                    },
                    {
                        name: 'is_active',
                        label: 'Activo',
                        type: 'checkbox',
                        help: 'Permite iniciar sesión en el ERP.',
                    },
                ]}
            />
        </ErpLayout>
    );
}
