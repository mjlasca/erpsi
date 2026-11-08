import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField, SelectOption } from '@/types/erp';
import type { InventoryLot, Product, Warehouse } from '@/types/models';
import { index, store } from '@/routes/inventory/movements';

export default function MovementsCreate({
    products,
    warehouses,
    lots,
    movementTypes,
    selectedProductId,
}: {
    products: Product[];
    warehouses: Warehouse[];
    lots: InventoryLot[];
    movementTypes: SelectOption[];
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
            name: 'movement_type',
            label: 'Tipo de movimiento',
            type: 'select',
            required: true,
            options: movementTypes,
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
            name: 'inventory_lot_id',
            label: 'Lote',
            type: 'select',
            options: lots.map((lot) => ({
                value: lot.id,
                label: `${lot.code} · ${lot.product?.name ?? ''} · ${lot.warehouse?.name ?? ''}`,
            })),
            help: 'Obligatorio para productos que manejan lotes.',
        },
        {
            name: 'quantity',
            label: 'Cantidad',
            type: 'number',
            step: '0.001',
            required: true,
            help: 'Usa un número negativo para disminuir existencias.',
        },
        {
            name: 'unit_cost',
            label: 'Costo unitario',
            type: 'number',
            step: '0.01',
            min: '0',
        },
        {
            name: 'movement_date',
            label: 'Fecha del movimiento',
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
                title="Registrar movimiento"
                description="Ajustes y devoluciones manuales. Las compras y ventas generan sus propios movimientos."
                fields={fields}
                initial={{
                    movement_type: 'ADJUSTMENT',
                    product_id: selectedProductId,
                }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Registrar movimiento"
            />
        </ErpLayout>
    );
}
