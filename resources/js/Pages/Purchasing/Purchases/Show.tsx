import { useState } from 'react';
import { router } from '@inertiajs/react';
import Can from '@/Components/Ui/Can';
import DocumentPaymentDialog from '@/Components/Ui/DocumentPaymentDialog';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    ConfirmButton,
    DeleteButton,
    LinkButton,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import type { Purchase } from '@/types/models';
import type { SelectOption } from '@/types/erp';
import {
    cancel,
    destroy,
    edit,
    index,
    pay,
} from '@/routes/purchasing/purchases';

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

export default function PurchasesShow({
    purchase,
    paymentMethods,
}: {
    purchase: Purchase;
    paymentMethods: SelectOption[];
}) {
    const [payOpen, setPayOpen] = useState(false);

    return (
        <ErpLayout>
            <ResourceShow
                title={purchase.purchase_number}
                description={`${purchase.supplier?.name ?? ''} · ${formatDate(purchase.purchase_date)}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        {purchase.status === 'DRAFT' && (
                            <>
                                <Can permission="purchasing.purchases.update">
                                    <LinkButton href={edit.url(purchase.id)}>
                                        Editar
                                    </LinkButton>
                                </Can>
                                <Can permission="purchasing.purchases.pay">
                                    <Button
                                        type="button"
                                        onClick={() => setPayOpen(true)}
                                    >
                                        Pagar
                                    </Button>
                                </Can>
                            </>
                        )}
                        {purchase.status !== 'CANCELLED' && (
                            <Can permission="purchasing.purchases.cancel">
                                <ConfirmButton
                                    message="¿Cancelar esta compra?"
                                    confirmLabel="Cancelar compra"
                                    title="Cancelar compra"
                                    onConfirm={() =>
                                        router.post(
                                            cancel.url(purchase.id),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Cancelar
                                </ConfirmButton>
                            </Can>
                        )}
                        <Can permission="purchasing.purchases.delete">
                            <DeleteButton
                                url={destroy.url(purchase.id)}
                                confirmMessage="¿Eliminar esta compra?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    {
                        label: 'Proveedor',
                        value: purchase.supplier?.name ?? '—',
                    },
                    {
                        label: 'Factura del proveedor',
                        value: purchase.supplier_invoice ?? '—',
                    },
                    {
                        label: 'Almacén',
                        value: purchase.warehouse?.name ?? '—',
                    },
                    {
                        label: 'Fecha',
                        value: formatDate(purchase.purchase_date),
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={statusTone(purchase.status)}>
                                {purchase.status_label}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Subtotal',
                        value: formatMoney(purchase.subtotal),
                    },
                    {
                        label: 'Descuento',
                        value: formatMoney(purchase.discount),
                    },
                    {
                        label: 'Impuestos',
                        value: formatMoney(purchase.tax_amount),
                    },
                    {
                        label: 'Total',
                        value: (
                            <span className="text-base font-bold">
                                {formatMoney(purchase.total)}
                            </span>
                        ),
                    },
                    {
                        label: 'Pagado',
                        value: formatMoney(purchase.paid_amount),
                    },
                    {
                        label: 'Saldo',
                        value: formatMoney(purchase.balance),
                    },
                    {
                        label: 'Recibida',
                        value: formatDate(purchase.received_at),
                    },
                    {
                        label: 'Notas',
                        value: purchase.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            >
                <Card>
                    <CardHeader title="Líneas de la compra" />
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Producto
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Cantidad
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Precio
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Desc.
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Impuesto
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {(purchase.details ?? []).map((detail) => (
                                    <tr key={detail.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-gray-900">
                                                {detail.product?.name ?? '—'}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {detail.variant?.name ?? ''}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatNumber(detail.quantity)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatMoney(detail.unit_price)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatMoney(detail.discount)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatNumber(detail.tax_rate, 2)}%
                                            · {formatMoney(detail.tax_amount)}
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium">
                                            {formatMoney(detail.total)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card>
                    <CardHeader title="Pagos aplicados" />
                    {!purchase.allocations?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin pagos aplicados.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {purchase.allocations.map((allocation) => (
                                <div
                                    key={allocation.id}
                                    className="flex items-center justify-between px-5 py-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {allocation.payment
                                                ?.payment_method_label ??
                                                'Pago'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {allocation.payment?.reference ??
                                                'Sin referencia'}{' '}
                                            ·{' '}
                                            {formatDate(
                                                allocation.payment
                                                    ?.payment_date,
                                            )}
                                        </p>
                                    </div>
                                    <span className="font-medium text-gray-900">
                                        {formatMoney(allocation.amount)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </ResourceShow>

            {payOpen && (
                <DocumentPaymentDialog
                    title="Pagar compra"
                    total={purchase.total}
                    action={pay.url(purchase.id)}
                    paymentMethods={paymentMethods}
                    onClose={() => setPayOpen(false)}
                />
            )}
        </ErpLayout>
    );
}
