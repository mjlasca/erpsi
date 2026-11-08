import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { ProductAttribute } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { toSelectOptions } from '@/types/erp';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/catalog/attributes';

export default function AttributesIndex({
    attributes,
    filters,
    products,
}: {
    attributes: Paginated<ProductAttribute>;
    filters: Filters;
    products: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Atributos"
                description="Características adicionales de productos y variantes."
                items={attributes}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar atributo..."
                createHref={create.url()}
                createPermission="catalog.attributes.create"
                createLabel="Nuevo atributo"
                filterControls={
                    <FilterSelect
                        name="product_id"
                        value={filters.product_id?.toString()}
                        placeholder="Todos los productos"
                        options={toSelectOptions(products)}
                    />
                }
                columns={[
                    {
                        key: 'name',
                        label: 'Atributo',
                        render: (attribute) => (
                            <span className="font-medium text-gray-900">
                                {attribute.name}
                            </span>
                        ),
                    },
                    {
                        key: 'value',
                        label: 'Valor',
                        render: (attribute) =>
                            `${attribute.value} ${attribute.unit?.abbreviation ?? ''}`.trim(),
                    },
                    {
                        key: 'product',
                        label: 'Producto',
                        render: (attribute) => attribute.product?.name ?? '—',
                    },
                    {
                        key: 'variant',
                        label: 'Variante',
                        render: (attribute) => attribute.variant?.name ?? '—',
                    },
                    {
                        key: 'sort_order',
                        label: 'Orden',
                        render: (attribute) => attribute.sort_order ?? 0,
                    },
                ]}
                actions={(attribute) => (
                    <>
                        <RowLink href={show.url(attribute.id)}>Ver</RowLink>
                        <Can permission="catalog.attributes.update">
                            <RowLink href={edit.url(attribute.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="catalog.attributes.delete">
                            <DeleteButton
                                url={destroy.url(attribute.id)}
                                confirmMessage="¿Eliminar este atributo?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
