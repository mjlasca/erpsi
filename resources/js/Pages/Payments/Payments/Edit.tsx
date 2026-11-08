import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import type { Customer, Payment, Supplier } from '@/types/models';
import type { SelectOption } from '@/types/erp';
import { index, update } from '@/routes/payments/payments';

type PaymentFormData = {
    payment_type: string;
    customer_id: string;
    supplier_id: string;
    payment_date: string;
    amount: string;
    payment_method: string;
    reference: string;
    notes: string;
};

export default function PaymentsEdit({
    payment,
    customers,
    suppliers,
    paymentTypes,
    paymentMethods,
}: {
    payment: Payment;
    customers: Customer[];
    suppliers: Supplier[];
    paymentTypes: SelectOption[];
    paymentMethods: SelectOption[];
}) {
    const form = useForm<PaymentFormData>({
        payment_type: payment.payment_type,
        customer_id: payment.customer_id ? String(payment.customer_id) : '',
        supplier_id: payment.supplier_id ? String(payment.supplier_id) : '',
        payment_date: payment.payment_date.slice(0, 10),
        amount: payment.amount,
        payment_method: payment.payment_method,
        reference: payment.reference ?? '',
        notes: payment.notes ?? '',
    });

    const isCustomerPayment = form.data.payment_type === 'CUSTOMER_PAYMENT';
    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(payment.id));
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Editar pago"
                description="Solo puedes modificar pagos sin aplicaciones."
            />

            <form onSubmit={submit} className="space-y-6">
                <Card className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <Field
                        label="Tipo de movimiento"
                        htmlFor="payment_type"
                        required
                        error={errorFor('payment_type')}
                    >
                        <Select
                            id="payment_type"
                            value={form.data.payment_type}
                            onChange={(event) =>
                                form.setData({
                                    ...form.data,
                                    payment_type: event.target.value,
                                    customer_id: '',
                                    supplier_id: '',
                                })
                            }
                        >
                            {paymentTypes.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    </Field>

                    {isCustomerPayment ? (
                        <Field
                            label="Cliente"
                            htmlFor="customer_id"
                            required
                            error={errorFor('customer_id')}
                        >
                            <Select
                                id="customer_id"
                                value={form.data.customer_id}
                                onChange={(event) =>
                                    form.setData(
                                        'customer_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Selecciona cliente</option>
                                {customers.map((customer) => (
                                    <option
                                        key={customer.id}
                                        value={customer.id}
                                    >
                                        {customer.full_name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    ) : (
                        <Field
                            label="Proveedor"
                            htmlFor="supplier_id"
                            required
                            error={errorFor('supplier_id')}
                        >
                            <Select
                                id="supplier_id"
                                value={form.data.supplier_id}
                                onChange={(event) =>
                                    form.setData(
                                        'supplier_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Selecciona proveedor</option>
                                {suppliers.map((supplier) => (
                                    <option
                                        key={supplier.id}
                                        value={supplier.id}
                                    >
                                        {supplier.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    )}

                    <Field
                        label="Fecha"
                        htmlFor="payment_date"
                        required
                        error={errorFor('payment_date')}
                    >
                        <Input
                            id="payment_date"
                            type="date"
                            value={form.data.payment_date}
                            onChange={(event) =>
                                form.setData('payment_date', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Monto"
                        htmlFor="amount"
                        required
                        error={errorFor('amount')}
                    >
                        <Input
                            id="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={form.data.amount}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Método de pago"
                        htmlFor="payment_method"
                        required
                        error={errorFor('payment_method')}
                    >
                        <Select
                            id="payment_method"
                            value={form.data.payment_method}
                            onChange={(event) =>
                                form.setData(
                                    'payment_method',
                                    event.target.value,
                                )
                            }
                        >
                            {paymentMethods.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    </Field>

                    <Field
                        label="Referencia"
                        htmlFor="reference"
                        error={errorFor('reference')}
                    >
                        <Input
                            id="reference"
                            value={form.data.reference}
                            onChange={(event) =>
                                form.setData('reference', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Notas"
                        htmlFor="notes"
                        error={errorFor('notes')}
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
                </Card>

                <div className="flex items-center justify-end gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Guardar cambios
                    </Button>
                </div>
            </form>
        </ErpLayout>
    );
}
