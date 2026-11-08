import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, store } from '@/routes/catalog/variants';

export default function VariantsCreate({
    products,
    selectedProductId,
}: {
    products: Array<{ id: number; name: string }>;
    selectedProductId?: number | null;
}) {
    const fields: FormField[] = [
        {
            name: 'product_id',
            label: 'Producto',
            type: 'select',
            required: true,
            options: products.map((product) => ({
                value: product.id,
                label: product.name,
            })),
            className: 'md:col-span-2',
        },
        {
            name: 'sku',
            label: 'SKU',
            required: true,
        },
        {
            name: 'name',
            label: 'Nombre',
            required: true,
        },
        {
            name: 'barcode',
            label: 'Código de barras',
        },
        {
            name: 'cost_price',
            label: 'Costo',
            type: 'number',
            step: '0.01',
            min: '0',
        },
        {
            name: 'sale_price',
            label: 'Precio de venta',
            type: 'number',
            step: '0.01',
            min: '0',
        },
        {
            name: 'is_active',
            label: 'Activa',
            type: 'checkbox',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title="Nueva variante"
                fields={fields}
                initial={{ product_id: selectedProductId, is_active: true }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear variante"
            />
        </ErpLayout>
    );
}
