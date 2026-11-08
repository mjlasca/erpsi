import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import { toSelectOptions } from '@/types/erp';
import { index, store } from '@/routes/admin/users';

export default function UsersCreate({
    roles,
}: {
    roles: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceForm
                title="Nuevo usuario"
                description="Crea un usuario con acceso al ERP."
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear usuario"
                initial={{ is_active: true }}
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
                        label: 'Contraseña',
                        type: 'password',
                        required: true,
                    },
                    {
                        name: 'password_confirmation',
                        label: 'Confirmar contraseña',
                        type: 'password',
                        required: true,
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
