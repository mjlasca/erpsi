import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import RemoteSelect from '@/Components/Ui/RemoteSelect';
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
import type { Customer, Supplier } from '@/types/models';
import type { SelectOption } from '@/types/erp';
import { index, store } from '@/routes/payments/payments';
import { search as customerSearch } from '@/routes/sales/customers';
import { search as supplierSearch } from '@/routes/purchasing/suppliers';

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

export default function PaymentsCreate({
    paymentTypes,
    paymentMethods,
}: {
    paymentTypes: SelectOption[];
    paymentMethods: SelectOption[];
}) {
    const form = useForm<PaymentFormData>({
        payment_type: 'CUSTOMER_PAYMENT',
        customer_id: '',
        supplier_id: '',
        payment_date: new Date().toISOString().slice(0, 10),
        amount: '',
        payment_method: 'CASH',
        reference: '',
        notes: '',
    });

    const isCustomerPayment = form.data.payment_type === 'CUSTOMER_PAYMENT';
    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Registrar pago"
                description="Registra un cobro o pago para aplicarlo después a documentos."
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
                            <RemoteSelect<Customer>
                                id="customer_id"
                                value={form.data.customer_id}
                                onChange={(value) =>
                                    form.setData('customer_id', value)
                                }
                                searchUrl={customerSearch.url()}
                                mapOption={(customer) => ({
                                    value: String(customer.id),
                                    label: customer.full_name,
                                    data: customer,
                                })}
                                placeholder="Busca cliente..."
                                ariaLabel="Cliente"
                                required
                            />
                        </Field>
                    ) : (
                        <Field
                            label="Proveedor"
                            htmlFor="supplier_id"
                            required
                            error={errorFor('supplier_id')}
                        >
                            <RemoteSelect<Supplier>
                                id="supplier_id"
                                value={form.data.supplier_id}
                                onChange={(value) =>
                                    form.setData('supplier_id', value)
                                }
                                searchUrl={supplierSearch.url()}
                                mapOption={(supplier) => ({
                                    value: String(supplier.id),
                                    label: supplier.name,
                                    data: supplier,
                                })}
                                placeholder="Busca proveedor..."
                                ariaLabel="Proveedor"
                                required
                            />
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

                {Object.keys(form.errors).length > 0 && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Revisa los datos del pago.
                    </div>
                )}

                <div className="flex items-center justify-end gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Registrar pago
                    </Button>
                </div>
            </form>
        </ErpLayout>
    );
}
