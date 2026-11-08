import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField, SelectOption } from '@/types/erp';
import { index, store } from '@/routes/catalog/units';

export default function UnitsCreate({
    unitTypes,
}: {
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
            placeholder: 'Ej. PZA',
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
                title="Nueva unidad"
                fields={fields}
                initial={{ allows_decimal: false, is_active: true }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear unidad"
            />
        </ErpLayout>
    );
}
