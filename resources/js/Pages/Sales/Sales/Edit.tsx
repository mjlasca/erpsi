import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import LineItemsEditor, { DetailRow } from '@/Components/Ui/LineItemsEditor';
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
import type {
    Customer,
    Product,
    Sale,
    TaxRate,
    Warehouse,
} from '@/types/models';
import { index, update } from '@/routes/sales/sales';

type SaleFormData = {
    customer_id: string;
    warehouse_id: string;
    sale_date: string;
    due_date: string;
    notes: string;
    details: DetailRow[];
};

export default function SalesEdit({
    sale,
    customers,
    warehouses,
    products,
    taxRates,
}: {
    sale: Sale;
    customers: Customer[];
    warehouses: Warehouse[];
    products: Product[];
    taxRates: TaxRate[];
}) {
    const details: DetailRow[] = (sale.details ?? []).map((detail) => ({
        id: detail.id,
        product_id: String(detail.product_id),
        product_variant_id: detail.product_variant_id
            ? String(detail.product_variant_id)
            : '',
        quantity: detail.quantity,
        unit_price: detail.unit_price,
        discount: detail.discount,
        tax_rate_id: detail.tax_rate_id ? String(detail.tax_rate_id) : '',
    }));

    const form = useForm<SaleFormData>({
        customer_id: String(sale.customer_id),
        warehouse_id: String(sale.warehouse_id),
        sale_date: sale.sale_date.slice(0, 10),
        due_date: sale.due_date ? sale.due_date.slice(0, 10) : '',
        notes: sale.notes ?? '',
        details,
    });

    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(sale.id));
    };

    return (
        <ErpLayout>
            <PageHeader
                title={`Editar ${sale.invoice_number}`}
                description="Solo las ventas en borrador se pueden modificar."
            />

            <form onSubmit={submit} className="space-y-6">
                <Card className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
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
                                form.setData('customer_id', event.target.value)
                            }
                        >
                            <option value="">Selecciona cliente</option>
                            {customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.full_name}
                                </option>
                            ))}
                        </Select>
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
                    onChange={(rows) => form.setData('details', rows)}
                    products={products}
                    taxRates={taxRates}
                    priceField="sale_price"
                    errors={form.errors as Record<string, string>}
                />

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
