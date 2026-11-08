import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { InventoryLot, Product, Warehouse } from '@/types/models';
import { store } from '@/routes/inventory/transfers';
import { index as movementsIndex } from '@/routes/inventory/movements';

export default function TransfersCreate({
    products,
    warehouses,
    lots,
}: {
    products: Product[];
    warehouses: Warehouse[];
    lots: InventoryLot[];
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
            name: 'from_warehouse_id',
            label: 'Almacén de origen',
            type: 'select',
            required: true,
            options: warehouses.map((warehouse) => ({
                value: warehouse.id,
                label: `${warehouse.code} · ${warehouse.name}`,
            })),
        },
        {
            name: 'to_warehouse_id',
            label: 'Almacén de destino',
            type: 'select',
            required: true,
            options: warehouses.map((warehouse) => ({
                value: warehouse.id,
                label: `${warehouse.code} · ${warehouse.name}`,
            })),
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
            min: '0.001',
            required: true,
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
                title="Transferir inventario"
                description="Mueve existencias entre almacenes."
                fields={fields}
                action={store.url()}
                cancelHref={movementsIndex.url()}
                submitLabel="Registrar transferencia"
            />
        </ErpLayout>
    );
}
