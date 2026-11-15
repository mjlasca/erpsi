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
import { index, store } from '@/routes/purchasing/returns';

type Detail = {
    id: number;
    quantity: string;
    unit_price: string;
    returned_quantity: string;
    pending_quantity: string;
    product?: { id: number; name: string; sku: string; track_lots: boolean };
    variant?: { id: number; name: string; sku: string } | null;
    lots: Array<{
        id: number;
        code: string;
        quantity_available: string;
        status: string;
    }>;
};

type FormData = {
    return_date: string;
    reason: string;
    notes: string;
    refund_amount: string;
    refund_method: string;
    details: Array<{
        purchase_detail_id: number;
        quantity: string;
        inventory_lot_id: string;
    }>;
};

export default function PurchaseReturnsCreate({
    purchase,
    details,
    paymentMethods,
}: {
    purchase: {
        id: number;
        purchase_number: string;
        supplier?: { id: number; name: string };
    };
    details: Detail[];
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
            purchase_detail_id: detail.id,
            quantity: '',
            inventory_lot_id:
                detail.lots.length === 1 ? String(detail.lots[0].id) : '',
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
        form.post(store.url(purchase.id));
    };

    return (
        <ErpLayout>
            <PageHeader
                title={t('returns.newPurchase')}
                description={`${purchase.purchase_number} · ${purchase.supplier?.name ?? ''}`}
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
                                                    className="w-44"
                                                >
                                                    <option value="">—</option>
                                                    {detail.lots.map((lot) => (
                                                        <option
                                                            key={lot.id}
                                                            value={lot.id}
                                                        >
                                                            {lot.code} (
                                                            {
                                                                lot.quantity_available
                                                            }
                                                            )
                                                        </option>
                                                    ))}
                                                </Select>
                                            ) : (
                                                '—'
                                            )}
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
