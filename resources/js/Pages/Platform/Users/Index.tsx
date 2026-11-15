import { router } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, ConfirmButton } from '@/Components/Ui/Primitives';
import { formatDate } from '@/lib/format';
import type { Filters, Paginated } from '@/types/erp';
import { index, togglePlatformAdmin } from '@/routes/platform/users';

type GlobalUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    is_platform_admin: boolean;
    created_at: string;
    memberships: Array<{
        tenant_id: number;
        tenant_name: string;
        role: string | null;
        is_default: boolean;
        is_active: boolean;
    }>;
};

export default function PlatformUsersIndex({
    users,
    filters,
}: {
    users: Paginated<GlobalUser>;
    filters: Filters;
}) {
    return (
        <PlatformLayout>
            <ResourceIndex
                title="Usuarios globales"
                description="Usuarios de todas las empresas y acceso al panel de plataforma."
                items={users}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar nombre o correo..."
                filterControls={
                    <FilterSelect
                        name="platform_admin"
                        value={filters.platform_admin?.toString()}
                        placeholder="Todos los accesos"
                        options={[
                            {
                                value: '1',
                                label: 'Administradores de plataforma',
                            },
                            { value: '0', label: 'Usuarios de empresa' },
                        ]}
                    />
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
                        key: 'memberships',
                        label: 'Empresas',
                        render: (user) =>
                            user.memberships.length === 0 ? (
                                <span className="text-xs text-gray-400">
                                    Sin membresías
                                </span>
                            ) : (
                                <div className="flex flex-wrap gap-1">
                                    {user.memberships.map((membership) => (
                                        <Badge
                                            key={membership.tenant_id}
                                            tone={
                                                membership.is_active
                                                    ? 'blue'
                                                    : 'gray'
                                            }
                                        >
                                            {membership.tenant_name} ·{' '}
                                            {membership.role ?? 'Sin rol'}
                                        </Badge>
                                    ))}
                                </div>
                            ),
                    },
                    {
                        key: 'is_active',
                        label: 'Cuenta',
                        render: (user) => (
                            <Badge tone={user.is_active ? 'green' : 'red'}>
                                {user.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                    {
                        key: 'is_platform_admin',
                        label: 'Plataforma',
                        render: (user) => (
                            <Badge
                                tone={user.is_platform_admin ? 'amber' : 'gray'}
                            >
                                {user.is_platform_admin
                                    ? 'Administrador'
                                    : 'Usuario'}
                            </Badge>
                        ),
                    },
                    {
                        key: 'created_at',
                        label: 'Alta',
                        render: (user) => formatDate(user.created_at),
                    },
                ]}
                actions={(user) => (
                    <ConfirmButton
                        message={
                            user.is_platform_admin
                                ? '¿Revocar el acceso de plataforma a este usuario?'
                                : '¿Conceder acceso de plataforma a este usuario?'
                        }
                        confirmLabel={
                            user.is_platform_admin
                                ? 'Revocar acceso'
                                : 'Conceder acceso'
                        }
                        tone="primary"
                        variant="secondary"
                        onConfirm={() =>
                            router.patch(
                                togglePlatformAdmin.url(user.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        {user.is_platform_admin
                            ? 'Revocar plataforma'
                            : 'Hacer admin de plataforma'}
                    </ConfirmButton>
                )}
            />
        </PlatformLayout>
    );
}
