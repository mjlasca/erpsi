import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import { formatNumber } from '@/lib/format';
import type { TaxRate } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/admin/tax-rates';

export default function TaxRatesIndex({
    taxRates,
    filters,
}: {
    taxRates: Paginated<TaxRate>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Impuestos"
                description="Tasas de impuesto disponibles para los documentos."
                items={taxRates}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar impuesto o jurisdicción..."
                createHref={create.url()}
                createLabel="Nuevo impuesto"
                columns={[
                    {
                        key: 'name',
                        label: 'Impuesto',
                        render: (taxRate) => (
                            <p className="font-medium text-gray-900">
                                {taxRate.name}
                            </p>
                        ),
                    },
                    {
                        key: 'rate',
                        label: 'Tasa',
                        render: (taxRate) =>
                            `${formatNumber(taxRate.rate, 2)}%`,
                    },
                    {
                        key: 'tax_type',
                        label: 'Tipo',
                        render: (taxRate) => taxRate.tax_type ?? '—',
                    },
                    {
                        key: 'jurisdiction',
                        label: 'Jurisdicción',
                        render: (taxRate) => taxRate.jurisdiction ?? '—',
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (taxRate) => (
                            <Badge tone={taxRate.is_active ? 'green' : 'gray'}>
                                {taxRate.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(taxRate) => (
                    <>
                        <RowLink href={show.url(taxRate.id)}>Ver</RowLink>
                        <RowLink href={edit.url(taxRate.id)}>Editar</RowLink>
                        <DeleteButton
                            url={destroy.url(taxRate.id)}
                            confirmMessage="¿Eliminar este impuesto?"
                        />
                    </>
                )}
            />
        </ErpLayout>
    );
}
