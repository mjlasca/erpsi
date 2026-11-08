import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { ProductVariant } from '@/types/models';
import { index, update } from '@/routes/catalog/variants';

export default function VariantsEdit({
    variant,
    products,
}: {
    variant: ProductVariant;
    products: Array<{ id: number; name: string }>;
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
                title={`Editar ${variant.name}`}
                fields={fields}
                initial={variant as unknown as Record<string, unknown>}
                action={update.url(variant.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`variant:${variant.id}`}
            />
        </ErpLayout>
    );
}
