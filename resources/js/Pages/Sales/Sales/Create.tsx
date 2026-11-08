import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import LineItemsEditor, {
    DetailRow,
    emptyDetail,
} from '@/Components/Ui/LineItemsEditor';
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
import type { Customer, TaxRate, Warehouse } from '@/types/models';
import { index, store } from '@/routes/sales/sales';
import { search as customerSearch } from '@/routes/sales/customers';

type SaleFormData = {
    customer_id: string;
    warehouse_id: string;
    sale_date: string;
    due_date: string;
    notes: string;
    details: DetailRow[];
};

export default function SalesCreate({
    warehouses,
    taxRates,
}: {
    warehouses: Warehouse[];
    taxRates: TaxRate[];
}) {
    const form = useForm<SaleFormData>({
        customer_id: '',
        warehouse_id: warehouses[0] ? String(warehouses[0].id) : '',
        sale_date: new Date().toISOString().slice(0, 10),
        due_date: '',
        notes: '',
        details: [emptyDetail(taxRates)],
    });

    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Nueva venta"
                description="Registra una venta en borrador."
            />

            <form onSubmit={submit} className="space-y-6">
                <Card className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
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
                                label: customer.company_name
                                    ? `${customer.full_name} · ${customer.company_name}`
                                    : customer.full_name,
                                data: customer,
                            })}
                            placeholder="Busca cliente..."
                            ariaLabel="Cliente"
                            required
                        />
                    </Field>

                    <Field
                        label="Almacén de salida"
                        htmlFor="warehouse_id"
                        required
                        error={errorFor('warehouse_id')}
                    >
                        <Select
                            id="warehouse_id"
                            value={form.data.warehouse_id}
                            onChange={(event) =>
                                form.setData('warehouse_id', event.target.value)
                            }
                        >
                            <option value="">Selecciona almacén</option>
                            {warehouses.map((warehouse) => (
                                <option key={warehouse.id} value={warehouse.id}>
                                    {warehouse.code} · {warehouse.name}
                                </option>
                            ))}
                        </Select>
                    </Field>

                    <Field
                        label="Fecha de venta"
                        htmlFor="sale_date"
                        required
                        error={errorFor('sale_date')}
                    >
                        <Input
                            id="sale_date"
                            type="date"
                            value={form.data.sale_date}
                            onChange={(event) =>
                                form.setData('sale_date', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Fecha de vencimiento"
                        htmlFor="due_date"
                        error={errorFor('due_date')}
                    >
                        <Input
                            id="due_date"
                            type="date"
                            value={form.data.due_date}
                            onChange={(event) =>
                                form.setData('due_date', event.target.value)
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

                <LineItemsEditor
                    details={form.data.details}
                    onChange={(details) => form.setData('details', details)}
                    taxRates={taxRates}
                    priceField="sale_price"
                    errors={form.errors as Record<string, string>}
                />

                {Object.keys(form.errors).length > 0 && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Revisa los datos del documento y sus líneas.
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
                        Crear venta
                    </Button>
                </div>
            </form>
        </ErpLayout>
    );
}
