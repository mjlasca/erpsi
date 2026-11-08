import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import { formatNumber } from '@/lib/format';
import type { TaxRate } from '@/types/models';
import { destroy, edit, index } from '@/routes/admin/tax-rates';

export default function TaxRatesShow({ taxRate }: { taxRate: TaxRate }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={taxRate.name}
                description={`${formatNumber(taxRate.rate, 2)}%`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <Can permission="administration.tax_rates.update">
                            <LinkButton href={edit.url(taxRate.id)}>
                                Editar
                            </LinkButton>
                        </Can>
                        <Can permission="administration.tax_rates.delete">
                            <DeleteButton
                                url={destroy.url(taxRate.id)}
                                confirmMessage="¿Eliminar este impuesto?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    { label: 'Nombre', value: taxRate.name },
                    {
                        label: 'Tasa',
                        value: `${formatNumber(taxRate.rate, 2)}%`,
                    },
                    {
                        label: 'Tipo',
                        value: taxRate.tax_type ?? '—',
                    },
                    {
                        label: 'Jurisdicción',
                        value: taxRate.jurisdiction ?? '—',
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={taxRate.is_active ? 'green' : 'gray'}>
                                {taxRate.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
            />
        </ErpLayout>
    );
}
