import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, Input } from '@/Components/Ui/Primitives';
import { formatDateTime } from '@/lib/format';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { index } from '@/routes/platform/audit';

type Log = {
    id: number;
    action: string;
    description: string;
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
    user?: { id: number; name: string; email: string } | null;
    tenant?: { id: number; name: string } | null;
};

export default function PlatformAuditIndex({
    logs,
    filters,
    tenants,
    actions,
}: {
    logs: Paginated<Log>;
    filters: Filters;
    tenants: Array<{ id: number; name: string }>;
    actions: string[];
}) {
    const actionOptions: SelectOption[] = actions.map((action) => ({
        value: action,
        label: action,
    }));

    return (
        <PlatformLayout>
            <ResourceIndex
                title="Auditoría"
                description="Historial de acciones administrativas de la plataforma y de las empresas."
                items={logs}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar acción o descripción..."
                filterControls={
                    <>
                        <FilterSelect
                            name="tenant_id"
                            value={filters.tenant_id?.toString()}
                            placeholder="Todas las empresas"
                            options={tenants.map((tenant) => ({
                                value: tenant.id,
                                label: tenant.name,
                            }))}
                        />
                        <FilterSelect
                            name="action"
                            value={filters.action?.toString()}
                            placeholder="Todas las acciones"
                            options={actionOptions}
                        />
                        <Input
                            type="date"
                            name="from"
                            defaultValue={filters.from?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                        <Input
                            type="date"
                            name="to"
                            defaultValue={filters.to?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                    </>
                }
                columns={[
                    {
                        key: 'description',
                        label: 'Evento',
                        render: (log) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {log.description}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {log.action} · {log.user?.name ?? 'Sistema'}
                                    {log.tenant ? ` · ${log.tenant.name}` : ''}
                                </p>
                                {log.metadata && (
                                    <details className="mt-1 text-xs text-gray-500">
                                        <summary className="cursor-pointer text-blue-600">
                                            Ver datos
                                        </summary>
                                        <pre className="mt-1 max-w-xl overflow-x-auto rounded bg-gray-50 p-2 text-[11px]">
                                            {JSON.stringify(
                                                log.metadata,
                                                null,
                                                2,
                                            )}
                                        </pre>
                                    </details>
                                )}
                            </div>
                        ),
                    },
                    {
                        key: 'created_at',
                        label: 'Fecha',
                        render: (log) => formatDateTime(log.created_at),
                    },
                    {
                        key: 'ip_address',
                        label: 'IP',
                        render: (log) => (
                            <Badge tone="gray">{log.ip_address ?? '—'}</Badge>
                        ),
                    },
                ]}
            />
        </PlatformLayout>
    );
}
