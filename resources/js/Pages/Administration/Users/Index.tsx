import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import { formatDate } from '@/lib/format';
import type { AdminUser } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/admin/users';

export default function UsersIndex({
    users,
    filters,
    roles,
}: {
    users: Paginated<AdminUser>;
    filters: Filters;
    roles: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Usuarios"
                description="Usuarios con acceso al ERP."
                items={users}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar nombre o correo..."
                createHref={create.url()}
                createPermission="administration.users.create"
                createLabel="Nuevo usuario"
                filterControls={
                    <>
                        <FilterSelect
                            name="role_id"
                            value={filters.role_id?.toString()}
                            placeholder="Todos los roles"
                            options={roles.map((role) => ({
                                value: role.id,
                                label: role.name,
                            }))}
                        />
                        <FilterSelect
                            name="is_active"
                            value={filters.is_active?.toString()}
                            placeholder="Todos los estados"
                            options={[
                                { value: '1', label: 'Activos' },
                                { value: '0', label: 'Inactivos' },
                            ]}
                        />
                    </>
                }
                columns={[
                    {
                        key: 'name',
                        label: 'Usuario',
                        render: (user) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {user.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {user.email}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'role',
                        label: 'Rol',
                        render: (user) => user.role?.name ?? 'Sin rol',
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (user) => (
                            <Badge tone={user.is_active ? 'green' : 'gray'}>
                                {user.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        key: 'created_at',
                        label: 'Creado',
                        render: (user) => formatDate(user.created_at),
                    },
                ]}
                actions={(user) => (
                    <>
                        <RowLink href={show.url(user.id)}>Ver</RowLink>
                        <Can permission="administration.users.update">
                            <RowLink href={edit.url(user.id)}>Editar</RowLink>
                        </Can>
                        <Can permission="administration.users.delete">
                            <DeleteButton
                                url={destroy.url(user.id)}
                                confirmMessage="¿Eliminar este usuario?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
