import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { Role } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/admin/roles';

export default function RolesIndex({
    roles,
    filters,
}: {
    roles: Paginated<Role>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Roles"
                description="Roles y permisos disponibles en el ERP."
                items={roles}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar rol..."
                createHref={create.url()}
                createPermission="administration.roles.create"
                createLabel="Nuevo rol"
                columns={[
                    {
                        key: 'name',
                        label: 'Rol',
                        render: (role) => (
                            <p className="font-medium text-gray-900">
                                {role.name}
                            </p>
                        ),
                    },
                    {
                        key: 'permissions',
                        label: 'Permisos',
                        render: (role) =>
                            role.permissions.includes('*')
                                ? 'Acceso total'
                                : `${role.permissions.length} permisos`,
                    },
                    {
                        key: 'users_count',
                        label: 'Usuarios',
                        render: (role) => role.users_count ?? 0,
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (role) => (
                            <Badge tone={role.is_active ? 'green' : 'gray'}>
                                {role.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(role) => (
                    <>
                        <RowLink href={show.url(role.id)}>Ver</RowLink>
                        <Can permission="administration.roles.update">
                            <RowLink href={edit.url(role.id)}>Editar</RowLink>
                        </Can>
                        <Can permission="administration.roles.delete">
                            <DeleteButton
                                url={destroy.url(role.id)}
                                confirmMessage="¿Eliminar este rol?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
