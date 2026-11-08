import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { Product, Warehouse } from '@/types/models';
import { index, store } from '@/routes/inventory/lots';

export default function LotsCreate({
    products,
    warehouses,
    selectedProductId,
}: {
    products: Product[];
    warehouses: Warehouse[];
    selectedProductId?: number | null;
}) {
    const variantOptions = products.flatMap((product) =>
        (product.variants ?? []).map((variant) => ({
            value: variant.id,
            label: `${product.name} · ${variant.name}`,
        })),
    );

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
        },
        {
            name: 'product_variant_id',
            label: 'Variante',
            type: 'select',
            options: variantOptions,
        },
        {
            name: 'warehouse_id',
            label: 'Almacén',
            type: 'select',
            required: true,
            options: warehouses.map((warehouse) => ({
                value: warehouse.id,
                label: `${warehouse.code} · ${warehouse.name}`,
            })),
        },
        {
            name: 'code',
            label: 'Código de lote',
            required: true,
            placeholder: 'Ej. LOT-2026-001',
        },
        {
            name: 'lot_number',
            label: 'Número de lote',
        },
        {
            name: 'serial_number',
            label: 'Número de serie',
        },
        {
            name: 'quantity_initial',
            label: 'Cantidad inicial',
            type: 'number',
            step: '0.001',
            min: '0.001',
            required: true,
        },
        {
            name: 'unit_cost',
            label: 'Costo unitario',
            type: 'number',
            step: '0.01',
            min: '0',
            required: true,
        },
        {
            name: 'received_at',
            label: 'Fecha de recepción',
            type: 'date',
        },
        {
            name: 'notes',
            label: 'Notas',
            type: 'textarea',
            className: 'md:col-span-2',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title="Nuevo lote"
                description="La cantidad inicial se registra como movimiento de ajuste."
                fields={fields}
                initial={{
                    product_id: selectedProductId,
                    unit_cost: '0',
                }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear lote"
            />
        </ErpLayout>
    );
}
