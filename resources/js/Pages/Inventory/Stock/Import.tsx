import { FormEvent, useRef } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    CardHeader,
    PageHeader,
    Spinner,
} from '@/Components/Ui/Primitives';
import { index } from '@/routes/inventory/stock';
import { store, template } from '@/routes/inventory/stock/import-products';

export default function ImportProducts() {
    const form = useForm<{ file: File | null }>({ file: null });
    const fileInput = useRef<HTMLInputElement>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url(), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Importar productos"
                description="Carga masiva de productos desde un archivo CSV."
                actions={
                    <a
                        href={template.url()}
                        className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Descargar plantilla CSV
                    </a>
                }
            />

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card className="p-5 xl:col-span-2">
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label
                                htmlFor="product-file"
                                className="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Archivo CSV
                            </label>
                            <input
                                ref={fileInput}
                                id="product-file"
                                type="file"
                                accept=".csv,text/csv"
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                                className="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700"
                            />
                            {form.errors.file && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.file}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                            <Link
                                href={index.url()}
                                className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </Link>
                            <Button
                                type="submit"
                                disabled={form.processing || !form.data.file}
                            >
                                {form.processing && <Spinner />}
                                Importar productos
                            </Button>
                        </div>
                    </form>
                </Card>

                <Card>
                    <CardHeader
                        title="Formato esperado"
                        description="La primera fila debe contener los encabezados."
                    />
                    <div className="space-y-4 px-5 py-4 text-sm text-gray-600">
                        <p>
                            <strong>Obligatorios:</strong> sku, name,
                            product_type, unit_abbreviation, cost_price,
                            sale_price.
                        </p>
                        <p>
                            <strong>Opcionales:</strong> description,
                            category_name, brand, barcode, min_stock,
                            is_inventory, track_lots, is_active.
                        </p>
                        <p>
                            <strong>product_type:</strong> GOODS, SERVICE o
                            BUNDLE.
                        </p>
                        <p>
                            <strong>unit_abbreviation</strong> debe existir.
                            Ejemplo: EA, PCS, SQFT.
                        </p>
                        <p>
                            <strong>category_name</strong> debe existir si se
                            captura.
                        </p>
                        <p>Booleanos aceptados: 1/0, true/false o si/no.</p>
                        <p>Los SKU y códigos de barras deben ser únicos.</p>
                    </div>
                </Card>
            </div>
        </ErpLayout>
    );
}
