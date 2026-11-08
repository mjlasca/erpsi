import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    DeleteButton,
    Field,
    Input,
    LinkButton,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { Payment, Purchase, Sale } from '@/types/models';
import { edit, index, destroy } from '@/routes/payments/payments';
import {
    destroy as destroyAllocation,
    store as storeAllocation,
} from '@/routes/payments/allocations';

type AllocationFormData = {
    sale_id: string;
    purchase_id: string;
    amount: string;
};

export default function PaymentsShow({
    payment,
    openSales,
    openPurchases,
}: {
    payment: Payment;
    openSales: Sale[];
    openPurchases: Purchase[];
}) {
    const isCustomerPayment = payment.payment_type === 'CUSTOMER_PAYMENT';
    const documents = isCustomerPayment ? openSales : openPurchases;
    const allocationForm = useForm<AllocationFormData>({
        sale_id: '',
        purchase_id: '',
        amount: '',
    });

    const selectedDocumentId = isCustomerPayment
        ? allocationForm.data.sale_id
        : allocationForm.data.purchase_id;

    const selectDocument = (value: string) => {
        allocationForm.setData({
            ...allocationForm.data,
            sale_id: isCustomerPayment ? value : '',
            purchase_id: isCustomerPayment ? '' : value,
        });
    };

    const submitAllocation = (event: FormEvent) => {
        event.preventDefault();
        allocationForm.post(storeAllocation.url(payment.id), {
            preserveScroll: true,
            onSuccess: () => allocationForm.reset(),
        });
    };

    const removeAllocation = (allocationId: number) => {
        if (!window.confirm('¿Eliminar esta aplicación de pago?')) {
            return;
        }

        router.delete(
            destroyAllocation.url({
                payment: payment.id,
                allocation: allocationId,
            }),
            { preserveScroll: true },
        );
    };

    return (
        <ErpLayout>
            <ResourceShow
                title={`Pago #${payment.id}`}
                description={`${payment.payment_type_label} · ${formatDate(payment.payment_date)}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(payment.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(payment.id)}
                            confirmMessage="¿Eliminar este pago?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Tipo',
                        value: (
                            <Badge tone={isCustomerPayment ? 'green' : 'blue'}>
                                {payment.payment_type_label}
                            </Badge>
                        ),
                    },
                    {
                        label: isCustomerPayment ? 'Cliente' : 'Proveedor',
                        value:
                            payment.customer?.full_name ??
                            payment.supplier?.name ??
                            '—',
                    },
                    {
                        label: 'Fecha',
                        value: formatDate(payment.payment_date),
                    },
                    {
                        label: 'Método',
                        value: payment.payment_method_label,
                    },
                    {
                        label: 'Monto',
                        value: formatMoney(payment.amount),
                    },
                    {
                        label: 'Aplicado',
                        value: formatMoney(payment.allocated_amount),
                    },
                    {
                        label: 'Sin aplicar',
                        value: formatMoney(payment.unallocated_amount),
                    },
                    {
                        label: 'Referencia',
                        value: payment.reference ?? '—',
                    },
                    {
                        label: 'Notas',
                        value: payment.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            >
                <Card>
                    <CardHeader
                        title="Aplicaciones del pago"
                        description="Aplica el saldo disponible a facturas o compras abiertas."
                    />

                    {!payment.allocations?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin aplicaciones registradas.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {payment.allocations.map((allocation) => (
                                <div
                                    key={allocation.id}
                                    className="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {allocation.sale?.invoice_number ??
                                                allocation.purchase
                                                    ?.purchase_number ??
                                                'Documento'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {allocation.sale
                                                ? (allocation.sale.customer
                                                      ?.full_name ?? '')
                                                : (allocation.purchase?.supplier
                                                      ?.name ?? '')}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="font-medium text-gray-900">
                                            {formatMoney(allocation.amount)}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="danger"
                                            onClick={() =>
                                                removeAllocation(allocation.id)
                                            }
                                        >
                                            Eliminar
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader
                        title="Documentos abiertos"
                        description={
                            isCustomerPayment
                                ? 'Ventas confirmadas con saldo pendiente.'
                                : 'Compras recibidas con saldo pendiente.'
                        }
                    />

                    {documents.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            No hay documentos con saldo pendiente.
                        </p>
                    ) : (
                        <form
                            onSubmit={submitAllocation}
                            className="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-3"
                        >
                            <Field
                                label="Documento"
                                htmlFor="allocation-document"
                                required
                            >
                                <Select
                                    id="allocation-document"
                                    value={selectedDocumentId}
                                    onChange={(event) =>
                                        selectDocument(event.target.value)
                                    }
                                >
                                    <option value="">
                                        Selecciona documento
                                    </option>
                                    {isCustomerPayment
                                        ? openSales.map((sale) => (
                                              <option
                                                  key={sale.id}
                                                  value={sale.id}
                                              >
                                                  {sale.invoice_number} ·{' '}
                                                  {formatMoney(sale.balance)}
                                              </option>
                                          ))
                                        : openPurchases.map((purchase) => (
                                              <option
                                                  key={purchase.id}
                                                  value={purchase.id}
                                              >
                                                  {purchase.purchase_number} ·{' '}
                                                  {formatMoney(
                                                      purchase.balance,
                                                  )}
                                              </option>
                                          ))}
                                </Select>
                            </Field>

                            <Field
                                label="Monto a aplicar"
                                htmlFor="allocation-amount"
                                required
                            >
                                <Input
                                    id="allocation-amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={allocationForm.data.amount}
                                    onChange={(event) =>
                                        allocationForm.setData(
                                            'amount',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <div className="flex items-end">
                                <Button
                                    type="submit"
                                    disabled={allocationForm.processing}
                                    className="w-full"
                                >
                                    {allocationForm.processing && <Spinner />}
                                    Aplicar pago
                                </Button>
                            </div>

                            {Object.keys(allocationForm.errors).length > 0 && (
                                <p className="text-sm text-red-600 md:col-span-3">
                                    {Object.values(allocationForm.errors).join(
                                        ' ',
                                    )}
                                </p>
                            )}
                        </form>
                    )}
                </Card>
            </ResourceShow>
        </ErpLayout>
    );
}
