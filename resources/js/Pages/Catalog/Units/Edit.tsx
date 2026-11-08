import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField, SelectOption } from '@/types/erp';
import type { Unit } from '@/types/models';
import { index, update } from '@/routes/catalog/units';

export default function UnitsEdit({
    unit,
    unitTypes,
}: {
    unit: Unit;
    unitTypes: SelectOption[];
}) {
    const fields: FormField[] = [
        {
            name: 'name',
            label: 'Nombre',
            required: true,
        },
        {
            name: 'abbreviation',
            label: 'Abreviatura',
            required: true,
        },
        {
            name: 'unit_type',
            label: 'Tipo',
            type: 'select',
            options: unitTypes,
        },
        {
            name: 'allows_decimal',
            label: 'Permite decimales',
            type: 'checkbox',
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
                title={`Editar ${unit.name}`}
                fields={fields}
                initial={unit as unknown as Record<string, unknown>}
                action={update.url(unit.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`unit:${unit.id}`}
            />
        </ErpLayout>
    );
}
