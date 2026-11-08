import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField, SelectOption } from '@/types/erp';
import type { InventoryLot } from '@/types/models';
import { index, update } from '@/routes/inventory/lots';

export default function LotsEdit({
    lot,
    statuses,
}: {
    lot: InventoryLot;
    warehouses: Array<{ id: number; name: string; code: string }>;
    statuses: SelectOption[];
}) {
    const fields: FormField[] = [
        {
            name: 'lot_number',
            label: 'Número de lote',
        },
        {
            name: 'serial_number',
            label: 'Número de serie',
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
            name: 'status',
            label: 'Estado',
            type: 'select',
            options: statuses,
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
                title={`Editar lote ${lot.code}`}
                description={`${lot.product?.name ?? ''} · ${lot.warehouse?.name ?? ''}`}
                fields={fields}
                initial={lot as unknown as Record<string, unknown>}
                action={update.url(lot.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`lot:${lot.id}`}
            />
        </ErpLayout>
    );
}
