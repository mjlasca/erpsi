import { router } from '@inertiajs/react';
import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Card,
    CardHeader,
    ConfirmButton,
    DeleteButton,
    LinkButton,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import type { Sale } from '@/types/models';
import { cancel, confirm, destroy, edit, index } from '@/routes/sales/sales';

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

export default function SalesShow({ sale }: { sale: Sale }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={sale.invoice_number}
                description={`${sale.customer?.full_name ?? ''} · ${formatDate(sale.sale_date)}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        {sale.status === 'DRAFT' && (
                            <>
                                <Can permission="sales.sales.update">
                                    <LinkButton href={edit.url(sale.id)}>
                                        Editar
                                    </LinkButton>
                                </Can>
                                <Can permission="sales.sales.confirm">
                                    <ConfirmButton
                                        message="¿Confirmar esta venta? Se descontará inventario usando asignación FIFO automática."
                                        confirmLabel="Confirmar"
                                        title="Confirmar venta"
                                        tone="primary"
                                        variant="primary"
                                        onConfirm={() =>
                                            router.post(
                                                confirm.url(sale.id),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Confirmar
                                    </ConfirmButton>
                                </Can>
                            </>
                        )}
                        {sale.status !== 'CANCELLED' && (
                            <Can permission="sales.sales.cancel">
                                <ConfirmButton
                                    message="¿Cancelar esta venta?"
                                    confirmLabel="Cancelar venta"
                                    title="Cancelar venta"
                                    onConfirm={() =>
                                        router.post(
                                            cancel.url(sale.id),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Cancelar
                                </ConfirmButton>
                            </Can>
                        )}
                        <Can permission="sales.sales.delete">
                            <DeleteButton
                                url={destroy.url(sale.id)}
                                confirmMessage="¿Eliminar esta venta?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    {
                        label: 'Cliente',
                        value: sale.customer?.full_name ?? '—',
                    },
                    {
                        label: 'Almacén',
                        value: sale.warehouse?.name ?? '—',
                    },
                    {
                        label: 'Fecha',
                        value: formatDate(sale.sale_date),
                    },
                    {
                        label: 'Vencimiento',
                        value: formatDate(sale.due_date),
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={statusTone(sale.status)}>
                                {sale.status_label}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Subtotal',
                        value: formatMoney(sale.subtotal),
                    },
                    {
                        label: 'Descuento',
                        value: formatMoney(sale.discount),
                    },
                    {
                        label: 'Impuestos',
                        value: formatMoney(sale.tax_amount),
                    },
                    {
                        label: 'Total',
                        value: (
                            <span className="text-base font-bold">
                                {formatMoney(sale.total)}
                            </span>
                        ),
                    },
                    {
                        label: 'Pagado',
                        value: formatMoney(sale.paid_amount),
                    },
                    {
                        label: 'Saldo',
                        value: formatMoney(sale.balance),
                    },
                    {
                        label: 'Confirmada',
                        value: formatDate(sale.confirmed_at),
                    },
                    {
                        label: 'Notas',
                        value: sale.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            >
                <Card>
                    <CardHeader
                        title="Líneas de la venta"
                        description={
                            sale.status === 'DRAFT'
                                ? 'Al confirmar se asignarán lotes FIFO automáticamente.'
                                : 'Lotes asignados al confirmar la venta.'
                        }
                    />
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
                                {(sale.details ?? []).map((detail) => (
                                    <tr key={detail.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-gray-900">
                                                {detail.product?.name ?? '—'}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {detail.variant?.name ?? ''}
                                            </p>
                                            {detail.lot_allocations?.length ? (
                                                <p className="mt-1 text-xs text-gray-500">
                                                    Lotes:{' '}
                                                    {detail.lot_allocations
                                                        .map(
                                                            (allocation) =>
                                                                `${allocation.lot?.code ?? allocation.inventory_lot_id} (${formatNumber(allocation.quantity)})`,
                                                        )
                                                        .join(', ')}
                                                </p>
                                            ) : null}
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
                    {!sale.allocations?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin pagos aplicados.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {sale.allocations.map((allocation) => (
                                <div
                                    key={allocation.id}
                                    className="flex items-center justify-between px-5 py-3 text-sm"
                                >
                                    <span className="text-gray-700">
                                        Pago #{allocation.payment_id}
                                    </span>
                                    <span className="font-medium text-gray-900">
                                        {formatMoney(allocation.amount)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </ResourceShow>
        </ErpLayout>
    );
}
