import { useForm } from '@inertiajs/react';
import {
    Button,
    Field,
    Input,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { formatMoney } from '@/lib/format';
import type { SelectOption } from '@/types/erp';

type PaymentLine = {
    payment_method: string;
    amount: string;
    reference: string;
    notes: string;
};

export default function DocumentPaymentDialog({
    title,
    total,
    action,
    paymentMethods,
    onClose,
}: {
    title: string;
    total: string;
    action: string;
    paymentMethods: SelectOption[];
    onClose: () => void;
}) {
    const form = useForm<{ payments: PaymentLine[] }>({
        payments: [
            {
                payment_method: String(paymentMethods[0]?.value ?? 'CASH'),
                amount: total,
                reference: '',
                notes: '',
            },
        ],
    });

    const amount = form.data.payments.reduce(
        (sum, payment) => sum + Number(payment.amount || 0),
        0,
    );
    const difference = Number(total) - amount;
    const matchesTotal = Math.abs(difference) < 0.005;

    const updateLine = (index: number, changes: Partial<PaymentLine>) => {
        form.setData(
            'payments',
            form.data.payments.map((payment, current) =>
                current === index ? { ...payment, ...changes } : payment,
            ),
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(action, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const generalError =
        typeof form.errors.payments === 'string'
            ? form.errors.payments
            : undefined;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="payment-dialog-title"
                className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl"
            >
                <h2
                    id="payment-dialog-title"
                    className="text-lg font-semibold text-gray-900"
                >
                    {title}
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Total a pagar: <strong>{formatMoney(total)}</strong>. Puedes
                    dividir el pago en varios métodos.
                </p>

                <form onSubmit={submit} className="mt-5 space-y-4">
                    {form.data.payments.map((payment, index) => (
                        <div
                            key={index}
                            className="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-4 md:grid-cols-4"
                        >
                            <Field
                                label="Método de pago"
                                htmlFor={`payment-method-${index}`}
                                required
                            >
                                <Select
                                    id={`payment-method-${index}`}
                                    aria-label={`Método de pago ${index + 1}`}
                                    value={payment.payment_method}
                                    onChange={(event) =>
                                        updateLine(index, {
                                            payment_method: event.target.value,
                                        })
                                    }
                                >
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
                                label="Monto"
                                htmlFor={`payment-amount-${index}`}
                                required
                            >
                                <Input
                                    id={`payment-amount-${index}`}
                                    aria-label={`Monto ${index + 1}`}
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={payment.amount}
                                    onChange={(event) =>
                                        updateLine(index, {
                                            amount: event.target.value,
                                        })
                                    }
                                />
                            </Field>

                            <Field
                                label="Referencia"
                                htmlFor={`payment-reference-${index}`}
                            >
                                <Input
                                    id={`payment-reference-${index}`}
                                    aria-label={`Referencia ${index + 1}`}
                                    value={payment.reference}
                                    onChange={(event) =>
                                        updateLine(index, {
                                            reference: event.target.value,
                                        })
                                    }
                                />
                            </Field>

                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-red-600"
                                    disabled={form.data.payments.length === 1}
                                    onClick={() =>
                                        form.setData(
                                            'payments',
                                            form.data.payments.filter(
                                                (_, current) =>
                                                    current !== index,
                                            ),
                                        )
                                    }
                                >
                                    Quitar
                                </Button>
                            </div>
                        </div>
                    ))}

                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() =>
                            form.setData('payments', [
                                ...form.data.payments,
                                {
                                    payment_method: String(
                                        paymentMethods[0]?.value ?? 'CASH',
                                    ),
                                    amount: '0',
                                    reference: '',
                                    notes: '',
                                },
                            ])
                        }
                    >
                        Agregar método de pago
                    </Button>

                    <div className="rounded-lg bg-gray-50 px-4 py-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-gray-600">
                                Total capturado
                            </span>
                            <span className="font-medium text-gray-900">
                                {formatMoney(amount)}
                            </span>
                        </div>
                        <div className="mt-1 flex justify-between">
                            <span className="text-gray-600">Diferencia</span>
                            <span
                                className={
                                    matchesTotal
                                        ? 'font-medium text-emerald-700'
                                        : 'font-medium text-red-600'
                                }
                            >
                                {formatMoney(difference)}
                            </span>
                        </div>
                    </div>

                    {generalError && (
                        <p className="text-sm text-red-600">{generalError}</p>
                    )}

                    <div className="flex justify-end gap-2 border-t border-gray-100 pt-5">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={onClose}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing || !matchesTotal}
                        >
                            {form.processing && <Spinner />}
                            Pagar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
