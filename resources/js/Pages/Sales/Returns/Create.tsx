import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    CardHeader,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import { formatNumber } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import type { SelectOption } from '@/types/erp';
import { index, store } from '@/routes/sales/returns';

type Detail = {
    id: number;
    quantity: string;
    unit_price: string;
    returned_quantity: string;
    pending_quantity: string;
    product?: { id: number; name: string; sku: string; track_lots: boolean };
    variant?: { id: number; name: string; sku: string } | null;
    lot_allocations: Array<{
        id: number;
        inventory_lot_id: number;
        quantity: string;
        code?: string | null;
    }>;
};

type FormData = {
    return_date: string;
    reason: string;
    notes: string;
    refund_amount: string;
    refund_method: string;
    details: Array<{
        sale_detail_id: number;
        quantity: string;
        disposition: string;
        inventory_lot_id: string;
    }>;
};

export default function SalesReturnsCreate({
    sale,
    details,
    dispositions,
    paymentMethods,
}: {
    sale: {
        id: number;
        invoice_number: string;
        customer?: { id: number; full_name: string };
    };
    details: Detail[];
    dispositions: SelectOption[];
    paymentMethods: SelectOption[];
}) {
    const { t } = useTranslation();
    const form = useForm<FormData>({
        return_date: new Date().toISOString().slice(0, 10),
        reason: '',
        notes: '',
        refund_amount: '0',
        refund_method: '',
        details: details.map((detail) => ({
            sale_detail_id: detail.id,
            quantity: '',
            disposition: 'RESTOCK',
            inventory_lot_id:
                detail.lot_allocations.length === 1
                    ? String(detail.lot_allocations[0].inventory_lot_id)
                    : '',
        })),
    });

    const selected = form.data.details.filter(
        (row) => Number(row.quantity) > 0,
    );

    const updateDetail = (
        index: number,
        key: keyof FormData['details'][number],
        value: string,
    ) => {
        const next = form.data.details.map((row, position) =>
            position === index ? { ...row, [key]: value } : row,
        );
        form.setData('details', next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            details: data.details.filter((row) => Number(row.quantity) > 0),
        }));
        form.post(store.url(sale.id));
    };

    return (
        <ErpLayout>
            <PageHeader
                title={t('returns.newSale')}
                description={`${sale.invoice_number} · ${sale.customer?.full_name ?? ''}`}
            />
            <form onSubmit={submit} className="space-y-6">
                <Card className="p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label={t('returns.date')}
                            htmlFor="return_date"
                            error={form.errors.return_date}
                        >
                            <Input
                                id="return_date"
                                type="date"
                                value={form.data.return_date}
                                onChange={(event) =>
                                    form.setData(
                                        'return_date',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label={t('returns.reason')}
                            htmlFor="reason"
                            error={form.errors.reason}
                        >
                            <Input
                                id="reason"
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label={t('returns.refundAmount')}
                            htmlFor="refund_amount"
                            error={form.errors.refund_amount}
                        >
                            <Input
                                id="refund_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.refund_amount}
                                onChange={(event) =>
                                    form.setData(
                                        'refund_amount',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label={t('returns.refundMethod')}
                            htmlFor="refund_method"
                            error={form.errors.refund_method}
                        >
                            <Select
                                id="refund_method"
                                value={form.data.refund_method}
                                onChange={(event) =>
                                    form.setData(
                                        'refund_method',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">{t('common.none')}</option>
                                {paymentMethods.map((method) => (
                                    <option
                                        key={method.value}
                                        value={method.value}
                                    >
                                        {method.label}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field
                            label={t('common.notes')}
                            htmlFor="notes"
                            error={form.errors.notes}
                            className="md:col-span-2"
                        >
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                </Card>

                <Card>
                    <CardHeader title={t('returns.details')} />
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        {t('exports.products.name')}
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        {t('returns.pending')}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        {t('returns.quantity')}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        {t('returns.lot')}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        {t('returns.disposition')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {details.map((detail, index) => (
                                    <tr key={detail.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-gray-900">
                                                {detail.product?.name}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {detail.variant?.name ??
                                                    detail.product?.sku}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatNumber(
                                                detail.pending_quantity,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Input
                                                type="number"
                                                min="0"
                                                max={detail.pending_quantity}
                                                step="0.001"
                                                value={
                                                    form.data.details[index]
                                                        ?.quantity ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateDetail(
                                                        index,
                                                        'quantity',
                                                        event.target.value,
                                                    )
                                                }
                                                className="w-28"
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            {detail.product?.track_lots ? (
                                                <Select
                                                    value={
                                                        form.data.details[index]
                                                            ?.inventory_lot_id ??
                                                        ''
                                                    }
                                                    onChange={(event) =>
                                                        updateDetail(
                                                            index,
                                                            'inventory_lot_id',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="w-40"
                                                >
                                                    <option value="">—</option>
                                                    {detail.lot_allocations.map(
                                                        (allocation) => (
                                                            <option
                                                                key={
                                                                    allocation.inventory_lot_id
                                                                }
                                                                value={
                                                                    allocation.inventory_lot_id
                                                                }
                                                            >
                                                                {allocation.code ??
                                                                    allocation.inventory_lot_id}
                                                            </option>
                                                        ),
                                                    )}
                                                </Select>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Select
                                                value={
                                                    form.data.details[index]
                                                        ?.disposition ??
                                                    'RESTOCK'
                                                }
                                                onChange={(event) =>
                                                    updateDetail(
                                                        index,
                                                        'disposition',
                                                        event.target.value,
                                                    )
                                                }
                                                className="w-48"
                                            >
                                                {dispositions.map(
                                                    (disposition) => (
                                                        <option
                                                            key={
                                                                disposition.value
                                                            }
                                                            value={
                                                                disposition.value
                                                            }
                                                        >
                                                            {disposition.label}
                                                        </option>
                                                    ),
                                                )}
                                            </Select>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                {form.errors.details && (
                    <p className="text-sm text-red-600">
                        {String(form.errors.details)}
                    </p>
                )}

                <div className="flex justify-end gap-2">
                    <a
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        {t('common.cancel')}
                    </a>
                    <Button
                        type="submit"
                        disabled={form.processing || selected.length === 0}
                    >
                        {form.processing && <Spinner />}
                        {t('common.save')}
                    </Button>
                </div>
            </form>
        </ErpLayout>
    );
}
