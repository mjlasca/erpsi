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
import type { Product, Purchase, TaxRate, Warehouse } from '@/types/models';
import { index, update } from '@/routes/purchasing/purchases';

type PurchaseFormData = {
    supplier_id: string;
    supplier_invoice: string;
    warehouse_id: string;
    purchase_date: string;
    notes: string;
    details: DetailRow[];
};

export default function PurchasesEdit({
    purchase,
    suppliers,
    warehouses,
    products,
    taxRates,
}: {
    purchase: Purchase;
    suppliers: Array<{ id: number; name: string }>;
    warehouses: Warehouse[];
    products: Product[];
    taxRates: TaxRate[];
}) {
    const details: DetailRow[] = (purchase.details ?? []).map((detail) => ({
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

    const form = useForm<PurchaseFormData>({
        supplier_id: String(purchase.supplier_id),
        supplier_invoice: purchase.supplier_invoice ?? '',
        warehouse_id: String(purchase.warehouse_id),
        purchase_date: purchase.purchase_date.slice(0, 10),
        notes: purchase.notes ?? '',
        details,
    });

    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(purchase.id));
    };

    return (
        <ErpLayout>
            <PageHeader
                title={`Editar ${purchase.purchase_number}`}
                description="Solo las compras en borrador se pueden modificar."
            />

            <form onSubmit={submit} className="space-y-6">
                <Card className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
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
                                form.setData('supplier_id', event.target.value)
                            }
                        >
                            <option value="">Selecciona proveedor</option>
                            {suppliers.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.name}
                                </option>
                            ))}
                        </Select>
                    </Field>

                    <Field
                        label="Factura del proveedor"
                        htmlFor="supplier_invoice"
                        error={errorFor('supplier_invoice')}
                    >
                        <Input
                            id="supplier_invoice"
                            value={form.data.supplier_invoice}
                            onChange={(event) =>
                                form.setData(
                                    'supplier_invoice',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>

                    <Field
                        label="Almacén de destino"
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
                        label="Fecha de compra"
                        htmlFor="purchase_date"
                        required
                        error={errorFor('purchase_date')}
                    >
                        <Input
                            id="purchase_date"
                            type="date"
                            value={form.data.purchase_date}
                            onChange={(event) =>
                                form.setData(
                                    'purchase_date',
                                    event.target.value,
                                )
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
                    priceField="cost_price"
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
