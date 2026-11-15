import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    Field,
    Input,
    LinkButton,
    PageHeader,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import {
    formatDate,
    formatDateTime,
    formatMoney,
    formatNumber,
} from '@/lib/format';
import { useCan } from '@/lib/permissions';
import { useTranslation } from '@/lib/i18n';
import type { CashSession, Payment, StockDifference } from '@/types/models';
import {
    close,
    exportDifferences,
    exportMethod,
    index,
} from '@/routes/cash/sessions';

export default function CashSessionsShow({
    session,
    summary,
    payments,
    snapshots,
    stockDifferences,
}: {
    session: CashSession;
    summary: {
        cash_in: string;
        cash_out: string;
        refunds_out: string;
        refunds_in: string;
        expected_cash: string;
        counted_cash: string | null;
        difference: string | null;
    };
    payments: Payment[];
    snapshots: {
        opening: { id: number; type: string; captured_at: string } | null;
        closing: { id: number; type: string; captured_at: string } | null;
    };
    stockDifferences: StockDifference[];
}) {
    const can = useCan();
    const { t } = useTranslation();
    const form = useForm({
        counted_cash: session.counted_cash ?? '',
        notes: session.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(close.url(session.id), { preserveScroll: true });
    };

    return (
        <ErpLayout>
            <PageHeader
                title={session.session_number}
                description={`${formatDate(session.business_date)} · ${session.warehouse?.name ?? '—'}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            {t('common.back')}
                        </LinkButton>
                        <a
                            href={exportMethod.url(session.id)}
                            className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {t('common.exportCsv')}
                        </a>
                        <a
                            href={exportDifferences.url(session.id)}
                            className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {t('cashSessions.exportDifferences')}
                        </a>
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('common.status')}
                    </p>
                    <Badge tone={session.status === 'OPEN' ? 'blue' : 'green'}>
                        {session.status_label}
                    </Badge>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('cashSessions.openingBalance')}
                    </p>
                    <p className="mt-1 text-xl font-bold">
                        {formatMoney(session.opening_balance)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('cashSessions.expectedCash')}
                    </p>
                    <p className="mt-1 text-xl font-bold">
                        {formatMoney(summary.expected_cash)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        {t('cashSessions.difference')}
                    </p>
                    <p className="mt-1 text-xl font-bold">
                        {summary.difference === null
                            ? '—'
                            : formatMoney(summary.difference)}
                    </p>
                </Card>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-1">
                    <CardHeader title={t('cashSessions.closeTitle')} />
                    {session.status === 'OPEN' && can('cash.sessions.close') ? (
                        <form onSubmit={submit} className="space-y-4 p-5">
                            <Field
                                label={t('cashSessions.countedCash')}
                                htmlFor="counted_cash"
                                error={form.errors.counted_cash}
                            >
                                <Input
                                    id="counted_cash"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.counted_cash}
                                    onChange={(event) =>
                                        form.setData(
                                            'counted_cash',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label={t('common.notes')}
                                htmlFor="notes"
                                error={form.errors.notes}
                            >
                                <Textarea
                                    id="notes"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                {t('cashSessions.close')}
                            </Button>
                        </form>
                    ) : (
                        <div className="space-y-2 p-5 text-sm text-gray-600">
                            <p>
                                {t('cashSessions.countedCash')}:{' '}
                                {session.counted_cash === null
                                    ? '—'
                                    : formatMoney(session.counted_cash)}
                            </p>
                            <p>
                                {t('cashSessions.openedAt')}:{' '}
                                {formatDateTime(session.opened_at)}
                            </p>
                            <p>
                                {t('cashSessions.closedAt')}:{' '}
                                {formatDateTime(session.closed_at)}
                            </p>
                        </div>
                    )}
                </Card>

                <Card className="xl:col-span-2">
                    <CardHeader
                        title={t('cashSessions.stockDifferences')}
                        description={
                            snapshots.closing
                                ? `${t('cashSessions.openingSnapshot')}: ${formatDateTime(snapshots.opening?.captured_at)} · ${t('cashSessions.closingSnapshot')}: ${formatDateTime(snapshots.closing.captured_at)}`
                                : t('cashSessions.noClosing')
                        }
                    />
                    {stockDifferences.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            {t('cashSessions.noDifferences')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            {t('exports.stock.product')}
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            {t('exports.stock.warehouse')}
                                        </th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            {t('cashSessions.opening')}
                                        </th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            {t('cashSessions.closing')}
                                        </th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            {t('cashSessions.variance')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {stockDifferences.map((row) => (
                                        <tr key={row.key}>
                                            <td className="px-5 py-3">
                                                <p className="font-medium text-gray-900">
                                                    {row.product_name}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {row.sku}
                                                    {row.variant_name
                                                        ? ` · ${row.variant_name}`
                                                        : ''}
                                                </p>
                                            </td>
                                            <td className="px-5 py-3 text-gray-600">
                                                {row.warehouse_name}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                {formatNumber(row.opening)}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                {formatNumber(row.closing)}
                                            </td>
                                            <td
                                                className={`px-5 py-3 text-right font-semibold ${
                                                    Number(row.difference) === 0
                                                        ? 'text-gray-700'
                                                        : 'text-amber-600'
                                                }`}
                                            >
                                                {formatNumber(row.difference)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                <Card className="xl:col-span-3">
                    <CardHeader
                        title={t('dashboard.cashFlow')}
                        description={`${t('dashboard.cashIn')}: ${formatMoney(summary.cash_in)} · ${t('dashboard.cashOut')}: ${formatMoney(summary.cash_out)}`}
                    />
                    {payments.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            {t('common.none')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            {t('common.date')}
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            {t('common.status')}
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            {t('common.amount')}
                                        </th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            {t('common.total')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {payments.map((payment) => (
                                        <tr key={payment.id}>
                                            <td className="px-5 py-3 text-gray-600">
                                                {formatDate(
                                                    payment.payment_date,
                                                )}
                                            </td>
                                            <td className="px-5 py-3">
                                                {payment.payment_type_label}
                                            </td>
                                            <td className="px-5 py-3">
                                                {payment.payment_method_label}
                                            </td>
                                            <td className="px-5 py-3 text-right font-medium">
                                                {formatMoney(payment.amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </ErpLayout>
    );
}
