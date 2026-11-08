import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import type { Unit } from '@/types/models';
import { destroy, edit, index } from '@/routes/catalog/units';

export default function UnitsShow({ unit }: { unit: Unit }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={unit.name}
                description={unit.abbreviation}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <Can permission="catalog.units.update">
                            <LinkButton href={edit.url(unit.id)}>
                                Editar
                            </LinkButton>
                        </Can>
                        <Can permission="catalog.units.delete">
                            <DeleteButton
                                url={destroy.url(unit.id)}
                                confirmMessage="¿Eliminar esta unidad?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    {
                        label: 'Abreviatura',
                        value: unit.abbreviation,
                    },
                    {
                        label: 'Tipo',
                        value: unit.unit_type_label ?? '—',
                    },
                    {
                        label: 'Permite decimales',
                        value: unit.allows_decimal ? 'Sí' : 'No',
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={unit.is_active ? 'green' : 'red'}>
                                {unit.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                ]}
            />
        </ErpLayout>
    );
}
