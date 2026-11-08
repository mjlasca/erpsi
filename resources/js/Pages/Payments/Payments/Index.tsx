import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import {
    Badge,
    DeleteButton,
    Input,
    RowLink,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { Payment } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/payments/payments';

export default function PaymentsIndex({
    payments,
    filters,
    paymentTypes,
    paymentMethods,
}: {
    payments: Paginated<Payment>;
    filters: Filters;
    paymentTypes: SelectOption[];
    paymentMethods: SelectOption[];
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Pagos"
                description="Cobros a clientes y pagos a proveedores."
                items={payments}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar referencia o notas..."
                createHref={create.url()}
                createLabel="Registrar pago"
                filterControls={
                    <>
                        <FilterSelect
                            name="payment_type"
                            value={filters.payment_type?.toString()}
                            placeholder="Todos los tipos"
                            options={paymentTypes}
                        />
                        <FilterSelect
                            name="payment_method"
                            value={filters.payment_method?.toString()}
                            placeholder="Todos los métodos"
                            options={paymentMethods}
                        />
                        <Input
                            name="from"
                            type="date"
                            defaultValue={filters.from?.toString() ?? ''}
                            className="w-40"
                        />
                        <Input
                            name="to"
                            type="date"
                            defaultValue={filters.to?.toString() ?? ''}
                            className="w-40"
                        />
                    </>
                }
                columns={[
                    {
                        key: 'payment_date',
                        label: 'Fecha',
                        render: (payment) => formatDate(payment.payment_date),
                    },
                    {
                        key: 'payment_type',
                        label: 'Tipo',
                        render: (payment) => (
                            <Badge
                                tone={
                                    payment.payment_type === 'CUSTOMER_PAYMENT'
                                        ? 'green'
                                        : 'blue'
                                }
                            >
                                {payment.payment_type_label}
                            </Badge>
                        ),
                    },
                    {
                        key: 'party',
                        label: 'Cliente / Proveedor',
                        render: (payment) =>
                            payment.customer?.full_name ??
                            payment.supplier?.name ??
                            '—',
                    },
                    {
                        key: 'payment_method',
                        label: 'Método',
                        render: (payment) => payment.payment_method_label,
                    },
                    {
                        key: 'amount',
                        label: 'Monto',
                        render: (payment) => formatMoney(payment.amount),
                    },
                    {
                        key: 'unallocated_amount',
                        label: 'Sin aplicar',
                        render: (payment) =>
                            formatMoney(payment.unallocated_amount),
                    },
                    {
                        key: 'reference',
                        label: 'Referencia',
                        render: (payment) => payment.reference ?? '—',
                    },
                ]}
                actions={(payment) => (
                    <>
                        <RowLink href={show.url(payment.id)}>Ver</RowLink>
                        <RowLink href={edit.url(payment.id)}>Editar</RowLink>
                        <DeleteButton
                            url={destroy.url(payment.id)}
                            confirmMessage="¿Eliminar este pago?"
                        />
                    </>
                )}
            />
        </ErpLayout>
    );
}
