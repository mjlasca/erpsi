import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import {
    Badge,
    DeleteButton,
    Input,
    LinkButton,
    RowLink,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import { withQuery } from '@/lib/url';
import type { Purchase } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import {
    create,
    destroy,
    edit,
    exportMethod,
    index,
    show,
} from '@/routes/purchasing/purchases';
import { index as returnsIndex } from '@/routes/purchasing/returns';

const statusTone = (status: string) => {
    switch (status) {
        case 'RECEIVED':
        case 'PAID':
            return 'green' as const;
        case 'CANCELLED':
            return 'red' as const;
        default:
            return 'gray' as const;
    }
};

export default function PurchasesIndex({
    purchases,
    filters,
    suppliers,
    statuses,
}: {
    purchases: Paginated<Purchase>;
    filters: Filters;
    suppliers: Array<{ id: number; name: string }>;
    statuses: SelectOption[];
}) {
    const { t } = useTranslation();

    return (
        <ErpLayout>
            <ResourceIndex
                title="Compras"
                description="Órdenes de compra y recepciones."
                items={purchases}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar folio o factura..."
                createHref={create.url()}
                createPermission="purchasing.purchases.create"
                createLabel="Nueva compra"
                extraActions={
                    <>
                        <LinkButton
                            href={returnsIndex.url()}
                            variant="secondary"
                        >
                            {t('returns.purchasesTitle')}
                        </LinkButton>
                        <a
                            href={withQuery(exportMethod.url(), filters)}
                            className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {t('common.exportCsv')}
                        </a>
                    </>
                }
                filterControls={
                    <>
                        <FilterSelect
                            name="status"
                            value={filters.status?.toString()}
                            placeholder="Todos los estados"
                            options={statuses}
                        />
                        <FilterSelect
                            name="supplier_id"
                            value={filters.supplier_id?.toString()}
                            placeholder="Todos los proveedores"
                            options={suppliers.map((supplier) => ({
                                value: supplier.id,
                                label: supplier.name,
                            }))}
                        />
                        <Input
                            type="date"
                            name="from"
                            aria-label="Desde"
                            defaultValue={filters.from?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                        <Input
                            type="date"
                            name="to"
                            aria-label="Hasta"
                            defaultValue={filters.to?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                    </>
                }
                columns={[
                    {
                        key: 'purchase_number',
                        label: 'Folio',
                        render: (purchase) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {purchase.purchase_number}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {purchase.supplier_invoice ?? ''}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'supplier',
                        label: 'Proveedor',
                        render: (purchase) => purchase.supplier?.name ?? '—',
                    },
                    {
                        key: 'purchase_date',
                        label: 'Fecha',
                        render: (purchase) =>
                            formatDate(purchase.purchase_date),
                    },
                    {
                        key: 'total',
                        label: 'Total',
                        render: (purchase) => formatMoney(purchase.total),
                    },
                    {
                        key: 'balance',
                        label: 'Saldo',
                        render: (purchase) => formatMoney(purchase.balance),
                    },
                    {
                        key: 'status',
                        label: 'Estado',
                        render: (purchase) => (
                            <Badge tone={statusTone(purchase.status)}>
                                {purchase.status_label}
                            </Badge>
                        ),
                    },
                ]}
                actions={(purchase) => (
                    <>
                        <RowLink href={show.url(purchase.id)}>Ver</RowLink>
                        {purchase.status === 'DRAFT' && (
                            <RowLink href={edit.url(purchase.id)}>
                                Editar
                            </RowLink>
                        )}
                        <Can permission="purchasing.purchases.delete">
                            <DeleteButton
                                url={destroy.url(purchase.id)}
                                confirmMessage="¿Eliminar esta compra?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
