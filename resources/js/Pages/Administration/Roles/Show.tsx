import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Card,
    CardHeader,
    DeleteButton,
    LinkButton,
} from '@/Components/Ui/Primitives';
import type { Role } from '@/types/models';
import { destroy, edit, index } from '@/routes/admin/roles';

export default function RolesShow({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: Record<string, Record<string, string>>;
}) {
    const hasAll = role.permissions.includes('*');

    return (
        <ErpLayout>
            <ResourceShow
                title={role.name}
                description="Detalle de permisos del rol."
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(role.id)}>Editar</LinkButton>
                        <DeleteButton
                            url={destroy.url(role.id)}
                            confirmMessage="¿Eliminar este rol?"
                        />
                    </>
                }
                fields={[
                    { label: 'Nombre', value: role.name },
                    {
                        label: 'Usuarios asignados',
                        value: role.users_count ?? 0,
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={role.is_active ? 'green' : 'gray'}>
                                {role.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
            >
                <Card>
                    <CardHeader
                        title="Permisos"
                        description={
                            hasAll
                                ? 'Este rol tiene acceso total.'
                                : 'Permisos asignados a este rol.'
                        }
                    />
                    <div className="space-y-5 px-5 py-4">
                        {Object.entries(permissionGroups).map(
                            ([group, permissions]) => {
                                const granted = Object.entries(
                                    permissions,
                                ).filter(
                                    ([permission]) =>
                                        hasAll ||
                                        role.permissions.includes(permission),
                                );

                                if (granted.length === 0) {
                                    return null;
                                }

                                return (
                                    <div key={group}>
                                        <h3 className="mb-2 text-sm font-semibold text-gray-900">
                                            {group}
                                        </h3>
                                        <div className="flex flex-wrap gap-2">
                                            {granted.map(
                                                ([permission, label]) => (
                                                    <Badge
                                                        key={permission}
                                                        tone="blue"
                                                    >
                                                        {label}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                );
                            },
                        )}
                    </div>
                </Card>
            </ResourceShow>
        </ErpLayout>
    );
}
