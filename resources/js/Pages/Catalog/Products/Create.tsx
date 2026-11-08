import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import { index, store } from '@/routes/catalog/products';
import type { FormField, SelectOption } from '@/types/erp';

export default function ProductsCreate({
    categories,
    units,
    productTypes,
}: {
    categories: Array<{ id: number; name: string }>;
    units: Array<{ id: number; name: string; abbreviation: string }>;
    productTypes: SelectOption[];
}) {
    const fields: FormField[] = [
        {
            name: 'sku',
            label: 'SKU',
            required: true,
            placeholder: 'Ej. PROD-001',
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
            help: 'Controla existencias de este producto.',
        },
        {
            name: 'track_lots',
            label: 'Maneja lotes',
            type: 'checkbox',
            help: 'Requiere lote en cada movimiento.',
        },
        {
            name: 'is_active',
            label: 'Activo',
            type: 'checkbox',
            help: 'Disponible para nuevas operaciones.',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title="Nuevo producto"
                description="Registra un producto o servicio en el catálogo."
                fields={fields}
                initial={{
                    product_type: 'GOODS',
                    is_inventory: true,
                    track_lots: false,
                    is_active: true,
                    cost_price: '0',
                    sale_price: '0',
                    min_stock: '0',
                }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear producto"
            />
        </ErpLayout>
    );
}
