import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import { formatDate } from '@/lib/format';
import type { AdminUser } from '@/types/models';
import { destroy, edit, index } from '@/routes/admin/users';

export default function UsersShow({ user }: { user: AdminUser }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={user.name}
                description={user.email}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(user.id)}>Editar</LinkButton>
                        <DeleteButton
                            url={destroy.url(user.id)}
                            confirmMessage="¿Eliminar este usuario?"
                        />
                    </>
                }
                fields={[
                    { label: 'Nombre', value: user.name },
                    { label: 'Correo', value: user.email },
                    {
                        label: 'Rol',
                        value: user.role?.name ?? 'Sin rol',
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={user.is_active ? 'green' : 'gray'}>
                                {user.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Creado',
                        value: formatDate(user.created_at),
                    },
                ]}
            />
        </ErpLayout>
    );
}
