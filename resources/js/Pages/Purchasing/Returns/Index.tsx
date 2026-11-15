import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import type { PurchaseReturn } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { index, show } from '@/routes/purchasing/returns';
import { index as purchasesIndex } from '@/routes/purchasing/purchases';

const statusTone = (status: string) => {
    if (status === 'CONFIRMED') return 'green' as const;
    if (status === 'CANCELLED') return 'red' as const;
    return 'amber' as const;
};

export default function PurchaseReturnsIndex({
    returns,
    filters,
    statuses,
}: {
    returns: Paginated<PurchaseReturn>;
    filters: Filters;
    statuses: SelectOption[];
}) {
    const { t } = useTranslation();

    return (
        <ErpLayout>
            <ResourceIndex
                title={t('returns.purchasesTitle')}
                description={t('returns.purchasesDescription')}
                items={returns}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder={t('common.search')}
                filterControls={
                    <FilterSelect
                        name="status"
                        value={filters.status?.toString()}
                        placeholder={t('common.status')}
                        options={statuses}
                    />
                }
                extraActions={
                    <a
                        href={purchasesIndex.url()}
                        className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        {t('nav.purchases')}
                    </a>
                }
                columns={[
                    {
                        key: 'return_number',
                        label: t('returns.number'),
                        render: (item) => (
                            <p className="font-medium text-gray-900">
                                {item.return_number}
                            </p>
                        ),
                    },
                    {
                        key: 'purchase',
                        label: t('returns.originalDocument'),
                        render: (item) => item.purchase?.purchase_number ?? '—',
                    },
                    {
                        key: 'return_date',
                        label: t('returns.date'),
                        render: (item) => formatDate(item.return_date),
                    },
                    {
                        key: 'total',
                        label: t('common.total'),
                        render: (item) => formatMoney(item.total),
                    },
                    {
                        key: 'status',
                        label: t('common.status'),
                        render: (item) => (
                            <Badge tone={statusTone(item.status)}>
                                {item.status_label}
                            </Badge>
                        ),
                    },
                ]}
                actions={(item) => (
                    <RowLink href={show.url(item.id)}>
                        {t('common.view')}
                    </RowLink>
                )}
                emptyMessage={t('returns.empty')}
            />
        </ErpLayout>
    );
}
