import { router } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Badge,
    Card,
    CardHeader,
    ConfirmButton,
    DeleteButton,
    LinkButton,
    PageHeader,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { useCan } from '@/lib/permissions';
import { useTranslation } from '@/lib/i18n';
import type { PurchaseReturn } from '@/types/models';
import { cancel, confirm, destroy, index } from '@/routes/purchasing/returns';

const tone = (status: string) => {
    if (status === 'CONFIRMED') return 'green' as const;
    if (status === 'CANCELLED') return 'red' as const;
    return 'amber' as const;
};

export default function PurchaseReturnsShow({
    return: purchaseReturn,
}: {
    return: PurchaseReturn;
}) {
    const can = useCan();
    const { t } = useTranslation();

    return (
        <ErpLayout>
            <PageHeader
                title={purchaseReturn.return_number}
                description={`${purchaseReturn.purchase?.purchase_number ?? ''} · ${formatDate(purchaseReturn.return_date)}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            {t('common.back')}
                        </LinkButton>
                        {purchaseReturn.status === 'DRAFT' && (
                            <ConfirmButton
                                message={t('returns.confirm') + '?'}
                                confirmLabel={t('returns.confirm')}
                                tone="primary"
                                variant="primary"
                                onConfirm={() =>
                                    router.post(
                                        confirm.url(purchaseReturn.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {t('returns.confirm')}
                            </ConfirmButton>
                        )}
                        {purchaseReturn.status === 'CONFIRMED' && (
                            <ConfirmButton
                                message={t('returns.cancel') + '?'}
                                confirmLabel={t('returns.cancel')}
                                onConfirm={() =>
                                    router.post(
                                        cancel.url(purchaseReturn.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {t('returns.cancel')}
                            </ConfirmButton>
                        )}
                        {purchaseReturn.status === 'DRAFT' &&
                            can('purchasing.returns.delete') && (
                                <DeleteButton
                                    url={destroy.url(purchaseReturn.id)}
                                />
                            )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('common.status')}
                    </p>
                    <Badge tone={tone(purchaseReturn.status)}>
                        {purchaseReturn.status_label}
                    </Badge>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">{t('common.total')}</p>
                    <p className="mt-1 text-xl font-bold">
                        {formatMoney(purchaseReturn.total)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('returns.credited')}
                    </p>
                    <p className="mt-1 text-xl font-bold">
                        {formatMoney(purchaseReturn.credited_amount)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('returns.refunded')}
                    </p>
                    <p className="mt-1 text-xl font-bold">
                        {formatMoney(purchaseReturn.refunded_amount)}
                    </p>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader title={t('returns.details')} />
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-100 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    {t('exports.products.name')}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    {t('returns.lot')}
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                    {t('returns.quantity')}
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                    {t('common.total')}
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {(purchaseReturn.details ?? []).map((detail) => (
                                <tr key={detail.id}>
                                    <td className="px-4 py-3">
                                        <p className="font-medium text-gray-900">
                                            {detail.product?.name ?? '—'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {detail.variant?.name ?? ''}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3">
                                        {detail.lot?.code ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatNumber(detail.quantity)}
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
        </ErpLayout>
    );
}
