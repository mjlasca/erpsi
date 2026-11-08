import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { Unit } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/catalog/units';

export default function UnitsIndex({
    units,
    filters,
}: {
    units: Paginated<Unit>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Unidades"
                description="Unidades de medida para productos."
                items={units}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar unidad..."
                createHref={create.url()}
                createLabel="Nueva unidad"
                columns={[
                    {
                        key: 'name',
                        label: 'Nombre',
                        render: (unit) => (
                            <span className="font-medium text-gray-900">
                                {unit.name}
                            </span>
                        ),
                    },
                    {
                        key: 'abbreviation',
                        label: 'Abreviatura',
                    },
                    {
                        key: 'unit_type_label',
                        label: 'Tipo',
                        render: (unit) => unit.unit_type_label ?? '—',
                    },
                    {
                        key: 'allows_decimal',
                        label: 'Decimales',
                        render: (unit) => (unit.allows_decimal ? 'Sí' : 'No'),
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (unit) => (
                            <Badge tone={unit.is_active ? 'green' : 'red'}>
                                {unit.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(unit) => (
                    <>
                        <RowLink href={show.url(unit.id)}>Ver</RowLink>
                        <RowLink href={edit.url(unit.id)}>Editar</RowLink>
                        <DeleteButton
                            url={destroy.url(unit.id)}
                            confirmMessage="¿Eliminar esta unidad?"
                        />
                    </>
                )}
            />
        </ErpLayout>
    );
}
