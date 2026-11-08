import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import { formatMoney } from '@/lib/format';
import type { ProductVariant } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { toSelectOptions } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/catalog/variants';

export default function VariantsIndex({
    variants,
    filters,
    products,
}: {
    variants: Paginated<ProductVariant>;
    filters: Filters;
    products: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Variantes"
                description="Presentaciones y variantes de productos."
                items={variants}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar por nombre, SKU o código..."
                createHref={create.url()}
                createPermission="catalog.variants.create"
                createLabel="Nueva variante"
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
                        key: 'sku',
                        label: 'SKU',
                        render: (variant) => (
                            <span className="font-medium text-gray-900">
                                {variant.sku}
                            </span>
                        ),
                    },
                    {
                        key: 'name',
                        label: 'Variante',
                    },
                    {
                        key: 'product',
                        label: 'Producto',
                        render: (variant) => variant.product?.name ?? '—',
                    },
                    {
                        key: 'sale_price',
                        label: 'Precio venta',
                        render: (variant) => formatMoney(variant.sale_price),
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (variant) => (
                            <Badge tone={variant.is_active ? 'green' : 'red'}>
                                {variant.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(variant) => (
                    <>
                        <RowLink href={show.url(variant.id)}>Ver</RowLink>
                        <Can permission="catalog.variants.update">
                            <RowLink href={edit.url(variant.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="catalog.variants.delete">
                            <DeleteButton
                                url={destroy.url(variant.id)}
                                confirmMessage="¿Eliminar esta variante?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
