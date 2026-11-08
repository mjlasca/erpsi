import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    Field,
    Input,
    PageHeader,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney } from '@/lib/format';
import { useCan } from '@/lib/permissions';
import type { CashSummary, Payment } from '@/types/models';
import { index, update } from '@/routes/cash/reconciliations';

type ReconciliationFormData = {
    opening_balance: string;
    counted_cash: string;
    notes: string;
};

export default function CashReconciliationsShow({
    date,
    summary,
    payments,
    reconciliation,
}: {
    date: string;
    summary: CashSummary;
    payments: Payment[];
    reconciliation: {
        id: number;
        date: string;
        opening_balance: string;
        counted_cash: string | null;
        notes: string | null;
    } | null;
}) {
    const can = useCan();
    const canManage =
        can('cash.reconciliations.update') ||
        can('cash.reconciliations.create');
    const form = useForm<ReconciliationFormData>({
        opening_balance: reconciliation?.opening_balance ?? '0',
        counted_cash: reconciliation?.counted_cash ?? '',
        notes: reconciliation?.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(date), { preserveScroll: true });
    };

    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    return (
        <ErpLayout>
            <PageHeader
                title={`Arqueo de caja · ${formatDate(date)}`}
                description="Concentrado de efectivo y flujo del día."
                actions={
                    <Link
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Volver
                    </Link>
                }
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                    <p className="text-sm text-gray-500">
                        Entradas en efectivo
                    </p>
                    <p className="mt-2 text-2xl font-bold text-emerald-700">
                        {formatMoney(summary.cash_in)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Salidas en efectivo</p>
                    <p className="mt-2 text-2xl font-bold text-red-600">
                        {formatMoney(summary.cash_out)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Efectivo esperado</p>
                    <p className="mt-2 text-2xl font-bold text-gray-900">
                        {formatMoney(summary.expected_cash)}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-gray-500">Diferencia</p>
                    <p className="mt-2 text-2xl font-bold">
                        {summary.difference === null ||
                        summary.difference === undefined ? (
                            <span className="text-gray-400">Pendiente</span>
                        ) : (
                            <span
                                className={
                                    Number(summary.difference) === 0
                                        ? 'text-emerald-700'
                                        : 'text-red-600'
                                }
                            >
                                {formatMoney(summary.difference)}
                            </span>
                        )}
                    </p>
                </Card>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-1">
                    <CardHeader
                        title={
                            reconciliation ? 'Editar arqueo' : 'Cerrar arqueo'
                        }
                        description="Registra el fondo inicial y el efectivo contado."
                    />
                    {canManage ? (
                        <form onSubmit={submit} className="space-y-4 p-5">
                            <Field
                                label="Fondo inicial"
                                htmlFor="opening_balance"
                                required
                                error={errorFor('opening_balance')}
                            >
                                <Input
                                    id="opening_balance"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.opening_balance}
                                    onChange={(event) =>
                                        form.setData(
                                            'opening_balance',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <Field
                                label="Efectivo contado"
                                htmlFor="counted_cash"
                                error={errorFor('counted_cash')}
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
                                label="Notas"
                                htmlFor="notes"
                                error={errorFor('notes')}
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
                                disabled={form.processing}
                                className="w-full"
                            >
                                {form.processing && <Spinner />}
                                Guardar arqueo
                            </Button>
                        </form>
                    ) : (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            No tienes permiso para modificar el arqueo.
                        </p>
                    )}
                </Card>

                <Card className="xl:col-span-2">
                    <CardHeader
                        title="Movimientos del día"
                        description="Todos los cobros y pagos registrados, con su método."
                    />
                    {payments.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin movimientos de pago.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Tipo
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Método
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Cliente / Proveedor
                                        </th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Referencia
                                        </th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            Monto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {payments.map((payment) => {
                                        const isIncome =
                                            payment.payment_type ===
                                            'CUSTOMER_PAYMENT';

                                        return (
                                            <tr key={payment.id}>
                                                <td className="px-5 py-3">
                                                    <Badge
                                                        tone={
                                                            isIncome
                                                                ? 'green'
                                                                : 'blue'
                                                        }
                                                    >
                                                        {
                                                            payment.payment_type_label
                                                        }
                                                    </Badge>
                                                </td>
                                                <td className="px-5 py-3 text-gray-700">
                                                    {
                                                        payment.payment_method_label
                                                    }
                                                </td>
                                                <td className="px-5 py-3 text-gray-700">
                                                    {payment.customer
                                                        ?.full_name ??
                                                        payment.supplier
                                                            ?.name ??
                                                        '—'}
                                                </td>
                                                <td className="px-5 py-3 text-gray-500">
                                                    {payment.reference ?? '—'}
                                                </td>
                                                <td
                                                    className={`px-5 py-3 text-right font-medium ${
                                                        isIncome
                                                            ? 'text-emerald-700'
                                                            : 'text-red-600'
                                                    }`}
                                                >
                                                    {isIncome ? '+' : '−'}
                                                    {formatMoney(
                                                        payment.amount,
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </ErpLayout>
    );
}
