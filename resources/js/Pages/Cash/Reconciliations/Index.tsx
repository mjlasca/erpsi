import { FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Badge,
    Button,
    Card,
    EmptyState,
    Input,
    LinkButton,
    PageHeader,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import type { CashSummary } from '@/types/models';
import { index, show } from '@/routes/cash/reconciliations';

export default function CashReconciliationsIndex({
    summaries,
    filters,
    totals,
}: {
    summaries: CashSummary[];
    filters: { from: string; to: string };
    totals: {
        cash_in: string;
        cash_out: string;
        inflow: string;
        outflow: string;
    };
}) {
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const data = Object.fromEntries(
            new FormData(event.currentTarget).entries(),
        );

        router.get(index.url(), data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const status = (summary: CashSummary) => {
        if (!summary.reconciled) {
            return <Badge tone="amber">Pendiente</Badge>;
        }

        return Number(summary.difference ?? 0) === 0 ? (
            <Badge tone="green">Cuadrado</Badge>
        ) : (
            <Badge tone="red">Diferencia</Badge>
        );
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Arqueo de caja"
                description="Movimiento diario de efectivo y flujo de caja."
            />

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        Entradas en efectivo
                    </p>
                    <p className="mt-2 text-2xl font-bold text-emerald-700">
                        {formatMoney(totals.cash_in)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Salidas en efectivo</p>
                    <p className="mt-2 text-2xl font-bold text-red-600">
                        {formatMoney(totals.cash_out)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Flujo total cobrado</p>
                    <p className="mt-2 text-2xl font-bold text-gray-900">
                        {formatMoney(totals.inflow)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Flujo total pagado</p>
                    <p className="mt-2 text-2xl font-bold text-gray-900">
                        {formatMoney(totals.outflow)}
                    </p>
                </Card>
            </div>

            <Card>
                <form
                    onSubmit={submit}
                    className="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4"
                >
                    <label className="text-sm font-medium text-gray-700">
                        Desde
                        <Input
                            type="date"
                            name="from"
                            defaultValue={filters.from}
                            className="mt-1"
                        />
                    </label>
                    <label className="text-sm font-medium text-gray-700">
                        Hasta
                        <Input
                            type="date"
                            name="to"
                            defaultValue={filters.to}
                            className="mt-1"
                        />
                    </label>
                    <Button type="submit">Filtrar</Button>
                    <LinkButton href={index.url()} variant="secondary">
                        Limpiar
                    </LinkButton>
                </form>

                {summaries.length === 0 ? (
                    <EmptyState message="No hay movimientos de caja en el periodo seleccionado." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Día
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Entradas
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Salidas
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Esperado
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Contado
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Diferencia
                                    </th>
                                    <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Estado
                                    </th>
                                    <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {summaries.map((summary) => (
                                    <tr key={summary.date}>
                                        <td className="px-5 py-3 font-medium text-gray-900">
                                            <Link
                                                href={show.url(summary.date)}
                                                className="hover:text-blue-700"
                                            >
                                                {formatDate(summary.date)}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-3 text-right text-emerald-700">
                                            {formatMoney(summary.cash_in)}
                                        </td>
                                        <td className="px-5 py-3 text-right text-red-600">
                                            {formatMoney(summary.cash_out)}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {formatMoney(summary.expected_cash)}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {summary.counted_cash === null ||
                                            summary.counted_cash === undefined
                                                ? '—'
                                                : formatMoney(
                                                      summary.counted_cash,
                                                  )}
                                        </td>
                                        <td className="px-5 py-3 text-right font-medium">
                                            {summary.difference === null ||
                                            summary.difference === undefined
                                                ? '—'
                                                : formatMoney(
                                                      summary.difference,
                                                  )}
                                        </td>
                                        <td className="px-5 py-3">
                                            {status(summary)}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <Link
                                                href={show.url(summary.date)}
                                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                                            >
                                                Revisar
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </ErpLayout>
    );
}
