import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField, SelectOption } from '@/types/erp';
import type { Product } from '@/types/models';
import { update, index } from '@/routes/catalog/products';

export default function ProductsEdit({
    product,
    categories,
    units,
    productTypes,
}: {
    product: Product;
    categories: Array<{ id: number; name: string }>;
    units: Array<{ id: number; name: string; abbreviation: string }>;
    productTypes: SelectOption[];
}) {
    const fields: FormField[] = [
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
            name: 'product_type',
            label: 'Tipo',
            type: 'select',
            required: true,
            options: productTypes,
        },
        {
            name: 'category_id',
            label: 'Categoría',
            type: 'select',
            options: categories.map((category) => ({
                value: category.id,
                label: category.name,
            })),
        },
        {
            name: 'unit_id',
            label: 'Unidad',
            type: 'select',
            required: true,
            options: units.map((unit) => ({
                value: unit.id,
                label: `${unit.name} (${unit.abbreviation})`,
            })),
        },
        {
            name: 'brand',
            label: 'Marca',
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
            required: true,
        },
        {
            name: 'sale_price',
            label: 'Precio de venta',
            type: 'number',
            step: '0.01',
            min: '0',
            required: true,
        },
        {
            name: 'min_stock',
            label: 'Existencia mínima',
            type: 'number',
            step: '0.001',
            min: '0',
            required: true,
        },
        {
            name: 'description',
            label: 'Descripción',
            type: 'textarea',
            className: 'md:col-span-2',
        },
        {
            name: 'is_inventory',
            label: 'Inventariable',
            type: 'checkbox',
        },
        {
            name: 'track_lots',
            label: 'Maneja lotes',
            type: 'checkbox',
        },
        {
            name: 'is_active',
            label: 'Activo',
            type: 'checkbox',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title={`Editar ${product.name}`}
                description={`SKU ${product.sku}`}
                fields={fields}
                initial={product as unknown as Record<string, unknown>}
                action={update.url(product.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`product:${product.id}`}
            />
        </ErpLayout>
    );
}
