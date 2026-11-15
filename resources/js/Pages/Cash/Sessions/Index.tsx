import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, Input, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import type { CashSession } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { create, index, show } from '@/routes/cash/sessions';

const tone = (status: string) => (status === 'OPEN' ? 'blue' : 'green');

export default function CashSessionsIndex({
    sessions,
    filters,
    warehouses,
    statuses,
}: {
    sessions: Paginated<CashSession>;
    filters: Filters;
    warehouses: Array<{ id: number; name: string; code: string }>;
    statuses: SelectOption[];
}) {
    const { t } = useTranslation();

    return (
        <ErpLayout>
            <ResourceIndex
                title={t('cashSessions.title')}
                description={t('cashSessions.description')}
                items={sessions}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder={t('common.search')}
                createHref={create.url()}
                createPermission="cash.sessions.create"
                createLabel={t('cashSessions.open')}
                filterControls={
                    <>
                        <FilterSelect
                            name="status"
                            value={filters.status?.toString()}
                            placeholder={t('common.status')}
                            options={statuses}
                        />
                        <FilterSelect
                            name="warehouse_id"
                            value={filters.warehouse_id?.toString()}
                            placeholder={t('dashboard.warehouse')}
                            options={warehouses.map((warehouse) => ({
                                value: warehouse.id,
                                label: warehouse.code,
                            }))}
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
                        key: 'session_number',
                        label: t('cashSessions.session'),
                        render: (session) => (
                            <p className="font-medium text-gray-900">
                                {session.session_number}
                            </p>
                        ),
                    },
                    {
                        key: 'business_date',
                        label: t('cashSessions.businessDate'),
                        render: (session) => formatDate(session.business_date),
                    },
                    {
                        key: 'opened_at',
                        label: t('cashSessions.openedAt'),
                        render: (session) => formatDateTime(session.opened_at),
                    },
                    {
                        key: 'opening_balance',
                        label: t('cashSessions.openingBalance'),
                        render: (session) =>
                            formatMoney(session.opening_balance),
                    },
                    {
                        key: 'expected_cash',
                        label: t('cashSessions.expectedCash'),
                        render: (session) => formatMoney(session.expected_cash),
                    },
                    {
                        key: 'difference',
                        label: t('cashSessions.difference'),
                        render: (session) =>
                            session.difference === null
                                ? '—'
                                : formatMoney(session.difference),
                    },
                    {
                        key: 'status',
                        label: t('common.status'),
                        render: (session) => (
                            <Badge tone={tone(session.status)}>
                                {session.status_label}
                            </Badge>
                        ),
                    },
                ]}
                actions={(session) => (
                    <RowLink href={show.url(session.id)}>
                        {t('common.view')}
                    </RowLink>
                )}
            />
        </ErpLayout>
    );
}
