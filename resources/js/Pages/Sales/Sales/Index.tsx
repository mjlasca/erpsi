import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import {
    Badge,
    DeleteButton,
    Input,
    RowLink,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { Customer, Sale } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/sales/sales';

const statusTone = (status: string) => {
    switch (status) {
        case 'PAID':
            return 'green' as const;
        case 'PARTIAL':
            return 'amber' as const;
        case 'CANCELLED':
            return 'red' as const;
        default:
            return 'gray' as const;
    }
};

export default function SalesIndex({
    sales,
    filters,
    customers,
    statuses,
}: {
    sales: Paginated<Sale>;
    filters: Filters;
    customers: Customer[];
    statuses: SelectOption[];
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Ventas"
                description="Facturas y notas de venta."
                items={sales}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar folio o cliente..."
                createHref={create.url()}
                createPermission="sales.sales.create"
                createLabel="Nueva venta"
                filterControls={
                    <>
                        <FilterSelect
                            name="status"
                            value={filters.status?.toString()}
                            placeholder="Todos los estados"
                            options={statuses}
                        />
                        <FilterSelect
                            name="customer_id"
                            value={filters.customer_id?.toString()}
                            placeholder="Todos los clientes"
                            options={customers.map((customer) => ({
                                value: customer.id,
                                label: customer.full_name,
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
                        key: 'invoice_number',
                        label: 'Folio',
                        render: (sale) => (
                            <p className="font-medium text-gray-900">
                                {sale.invoice_number}
                            </p>
                        ),
                    },
                    {
                        key: 'customer',
                        label: 'Cliente',
                        render: (sale) => sale.customer?.full_name ?? '—',
                    },
                    {
                        key: 'sale_date',
                        label: 'Fecha',
                        render: (sale) => formatDate(sale.sale_date),
                    },
                    {
                        key: 'total',
                        label: 'Total',
                        render: (sale) => formatMoney(sale.total),
                    },
                    {
                        key: 'balance',
                        label: 'Saldo',
                        render: (sale) => formatMoney(sale.balance),
                    },
                    {
                        key: 'status',
                        label: 'Estado',
                        render: (sale) => (
                            <Badge tone={statusTone(sale.status)}>
                                {sale.status_label}
                            </Badge>
                        ),
                    },
                ]}
                actions={(sale) => (
                    <>
                        <RowLink href={show.url(sale.id)}>Ver</RowLink>
                        {sale.status === 'DRAFT' && (
                            <Can permission="sales.sales.update">
                                <RowLink href={edit.url(sale.id)}>
                                    Editar
                                </RowLink>
                            </Can>
                        )}
                        <Can permission="sales.sales.delete">
                            <DeleteButton
                                url={destroy.url(sale.id)}
                                confirmMessage="¿Eliminar esta venta?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
