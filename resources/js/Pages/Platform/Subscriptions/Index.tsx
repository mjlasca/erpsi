import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { index } from '@/routes/platform/subscriptions';
import { show as tenantShow } from '@/routes/platform/tenants';

type Row = {
    id: number;
    name: string;
    is_active: boolean;
    subscription_status: string;
    trial_ends_at: string | null;
    subscription_ends_at: string | null;
    plan?: { id: number; name: string; price: string; currency: string } | null;
    usage: {
        users: number;
        products: number;
        sales_this_month: number;
        limits: Record<string, number | null>;
        percentages: Record<string, number | null>;
    };
};

const tone = (status: string) => {
    if (status === 'ACTIVE') return 'green' as const;
    if (status === 'TRIALING') return 'blue' as const;
    if (status === 'PAST_DUE') return 'amber' as const;
    return 'red' as const;
};

export default function PlatformSubscriptionsIndex({
    tenants,
    filters,
    statuses,
}: {
    tenants: Paginated<Row>;
    filters: Filters;
    statuses: SelectOption[];
}) {
    return (
        <PlatformLayout>
            <ResourceIndex
                title="Suscripciones"
                description="Estado de suscripción, plan y consumo por empresa."
                items={tenants}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar empresa..."
                filterControls={
                    <FilterSelect
                        name="status"
                        value={filters.status?.toString()}
                        placeholder="Todos los estados"
                        options={statuses}
                    />
                }
                columns={[
                    {
                        key: 'name',
                        label: 'Empresa',
                        render: (row) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {row.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {row.plan?.name ?? 'Sin plan'} ·{' '}
                                    {row.is_active ? 'Activa' : 'Suspendida'}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'subscription_status',
                        label: 'Estado',
                        render: (row) => (
                            <Badge tone={tone(row.subscription_status)}>
                                {row.subscription_status}
                            </Badge>
                        ),
                    },
                    {
                        key: 'trial_ends_at',
                        label: 'Prueba / fin',
                        render: (row) => (
                            <span className="text-xs text-gray-600">
                                {row.trial_ends_at
                                    ? `Prueba: ${formatDate(row.trial_ends_at)}`
                                    : 'Sin prueba'}
                                {row.subscription_ends_at
                                    ? ` · Fin: ${formatDate(row.subscription_ends_at)}`
                                    : ''}
                            </span>
                        ),
                    },
                    {
                        key: 'price',
                        label: 'Importe',
                        render: (row) =>
                            row.plan ? formatMoney(row.plan.price) : '—',
                    },
                    {
                        key: 'usage',
                        label: 'Uso',
                        render: (row) => (
                            <div className="text-xs text-gray-600">
                                {row.usage.users} usuarios ·{' '}
                                {row.usage.products} productos ·{' '}
                                {row.usage.sales_this_month} ventas
                            </div>
                        ),
                    },
                ]}
                actions={(row) => (
                    <RowLink href={tenantShow.url(row.id)}>Administrar</RowLink>
                )}
            />
        </PlatformLayout>
    );
}
