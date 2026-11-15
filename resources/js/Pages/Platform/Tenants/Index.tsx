import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { Filters, Paginated } from '@/types/erp';
import { create, index, show } from '@/routes/platform/tenants';

type TenantRow = {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    subscription_status: string;
    created_at: string;
    plan?: { id: number; name: string; price: string; currency: string } | null;
    owner?: { id: number; name: string; email: string } | null;
    usage: {
        users: number;
        products: number;
        warehouses: number;
        sales_this_month: number;
        limits: Record<string, number | null>;
        plan?: { name: string } | null;
    };
};

const statusTone = (status: string) => {
    if (status === 'ACTIVE') return 'green' as const;
    if (status === 'TRIALING') return 'blue' as const;
    if (status === 'PAST_DUE') return 'amber' as const;
    return 'red' as const;
};

export default function PlatformTenantsIndex({
    tenants,
    filters,
    plans,
}: {
    tenants: Paginated<TenantRow>;
    filters: Filters;
    plans: Array<{ id: number; name: string }>;
}) {
    return (
        <PlatformLayout>
            <ResourceIndex
                title="Empresas"
                description="Consulta, crea y administra las empresas de la plataforma."
                items={tenants}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar empresa, slug o correo..."
                createHref={create.url()}
                createLabel="Nueva empresa"
                filterControls={
                    <>
                        <FilterSelect
                            name="status"
                            value={filters.status?.toString()}
                            placeholder="Todos los estados"
                            options={[
                                { value: 'active', label: 'Activas' },
                                { value: 'suspended', label: 'Suspendidas' },
                            ]}
                        />
                        <FilterSelect
                            name="plan_id"
                            value={filters.plan_id?.toString()}
                            placeholder="Todos los planes"
                            options={plans.map((plan) => ({
                                value: plan.id,
                                label: plan.name,
                            }))}
                        />
                    </>
                }
                columns={[
                    {
                        key: 'name',
                        label: 'Empresa',
                        render: (tenant) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {tenant.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {tenant.slug} · creada{' '}
                                    {formatDate(tenant.created_at)}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'plan',
                        label: 'Plan',
                        render: (tenant) =>
                            tenant.plan
                                ? `${tenant.plan.name} · ${formatMoney(tenant.plan.price)}`
                                : 'Sin plan',
                    },
                    {
                        key: 'status',
                        label: 'Estado',
                        render: (tenant) => (
                            <div className="flex flex-col items-start gap-1">
                                <Badge
                                    tone={tenant.is_active ? 'green' : 'red'}
                                >
                                    {tenant.is_active ? 'Activa' : 'Suspendida'}
                                </Badge>
                                <Badge
                                    tone={statusTone(
                                        tenant.subscription_status,
                                    )}
                                >
                                    {tenant.subscription_status}
                                </Badge>
                            </div>
                        ),
                    },
                    {
                        key: 'usage',
                        label: 'Uso',
                        render: (tenant) => (
                            <p className="text-xs text-gray-600">
                                {tenant.usage.users} usuarios ·{' '}
                                {tenant.usage.products} productos ·{' '}
                                {tenant.usage.sales_this_month} ventas
                            </p>
                        ),
                    },
                    {
                        key: 'owner',
                        label: 'Propietario',
                        render: (tenant) =>
                            tenant.owner ? (
                                <div>
                                    <p className="text-gray-900">
                                        {tenant.owner.name}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {tenant.owner.email}
                                    </p>
                                </div>
                            ) : (
                                '—'
                            ),
                    },
                ]}
                actions={(tenant) => (
                    <RowLink href={show.url(tenant.id)}>Administrar</RowLink>
                )}
            />
        </PlatformLayout>
    );
}
