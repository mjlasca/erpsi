import { Link } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import {
    Badge,
    Card,
    CardHeader,
    PageHeader,
} from '@/Components/Ui/Primitives';
import { formatDateTime, formatMoney } from '@/lib/format';
import { show as tenantShow } from '@/routes/platform/tenants';
import { index as tenantsIndex } from '@/routes/platform/tenants';

type Stats = {
    tenants: number;
    active_tenants: number;
    suspended_tenants: number;
    users: number;
    trialing: number;
    platform_admins: number;
    mrr: string;
    plans: number;
};

export default function PlatformDashboard({
    stats,
    trialSoon,
    recentAudit,
}: {
    stats: Stats;
    trialSoon: Array<{ id: number; name: string; trial_ends_at: string }>;
    recentAudit: Array<{
        id: number;
        action: string;
        description: string;
        user?: { id: number; name: string } | null;
        tenant?: { id: number; name: string } | null;
        created_at: string;
    }>;
}) {
    const cards = [
        {
            label: 'Empresas',
            value: stats.tenants,
            detail: `${stats.active_tenants} activas`,
        },
        {
            label: 'Suspendidas',
            value: stats.suspended_tenants,
            detail: 'Requieren seguimiento',
        },
        {
            label: 'Usuarios globales',
            value: stats.users,
            detail: `${stats.platform_admins} admins de plataforma`,
        },
        {
            label: 'En prueba',
            value: stats.trialing,
            detail: 'Suscripciones trialing',
        },
        {
            label: 'MRR estimado',
            value: formatMoney(stats.mrr),
            detail: `${stats.plans} planes activos`,
        },
    ];

    return (
        <PlatformLayout>
            <PageHeader
                title="Panel de plataforma"
                description="Salud general del SaaS y actividad reciente."
                actions={
                    <Link
                        href={tenantsIndex.url()}
                        className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Ver empresas
                    </Link>
                }
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {cards.map((card) => (
                    <Card key={card.label} className="p-5">
                        <p className="text-sm text-gray-500">{card.label}</p>
                        <p className="mt-2 text-2xl font-bold text-gray-900">
                            {card.value}
                        </p>
                        <p className="mt-1 text-xs text-gray-500">
                            {card.detail}
                        </p>
                    </Card>
                ))}
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Card>
                    <CardHeader
                        title="Pruebas por vencer"
                        description="Empresas que terminan su periodo de prueba en los próximos 14 días."
                    />
                    {trialSoon.length === 0 ? (
                        <p className="px-5 py-8 text-sm text-gray-500">
                            No hay pruebas próximas a vencer.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {trialSoon.map((tenant) => (
                                <Link
                                    key={tenant.id}
                                    href={tenantShow.url(tenant.id)}
                                    className="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50"
                                >
                                    <span className="font-medium text-gray-900">
                                        {tenant.name}
                                    </span>
                                    <span className="text-gray-500">
                                        {formatDateTime(tenant.trial_ends_at)}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader
                        title="Auditoría reciente"
                        description="Últimos movimientos administrativos de la plataforma."
                    />
                    {recentAudit.length === 0 ? (
                        <p className="px-5 py-8 text-sm text-gray-500">
                            Sin actividad registrada.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {recentAudit.map((log) => (
                                <div
                                    key={log.id}
                                    className="flex items-start justify-between gap-3 px-5 py-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {log.description}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {log.user?.name ?? 'Sistema'}
                                            {log.tenant
                                                ? ` · ${log.tenant.name}`
                                                : ''}
                                        </p>
                                    </div>
                                    <Badge tone="gray">{log.action}</Badge>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </PlatformLayout>
    );
}
