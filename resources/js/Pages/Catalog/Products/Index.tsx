import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import { formatMoney } from '@/lib/format';
import type { Product } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { toSelectOptions } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/catalog/products';

export default function ProductsIndex({
    products,
    filters,
    categories,
}: {
    products: Paginated<Product>;
    filters: Filters;
    categories: Array<{ id: number; name: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Productos"
                description="Catálogo de productos y servicios."
                items={products}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar por nombre, SKU o código de barras..."
                createHref={create.url()}
                createPermission="catalog.products.create"
                createLabel="Nuevo producto"
                filterControls={
                    <>
                        <FilterSelect
                            name="category_id"
                            value={filters.category_id?.toString()}
                            placeholder="Todas las categorías"
                            options={toSelectOptions(categories)}
                        />
                        <FilterSelect
                            name="is_active"
                            value={filters.is_active?.toString()}
                            placeholder="Todos los estados"
                            options={[
                                { value: '1', label: 'Activos' },
                                { value: '0', label: 'Inactivos' },
                            ]}
                        />
                    </>
                }
                columns={[
                    {
                        key: 'sku',
                        label: 'SKU',
                        render: (product) => (
                            <span className="font-medium text-gray-900">
                                {product.sku}
                            </span>
                        ),
                    },
                    {
                        key: 'name',
                        label: 'Producto',
                        render: (product) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {product.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {product.brand ||
                                        product.product_type_label}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'category',
                        label: 'Categoría',
                        render: (product) => product.category?.name ?? '—',
                    },
                    {
                        key: 'unit',
                        label: 'Unidad',
                        render: (product) => product.unit?.abbreviation ?? '—',
                    },
                    {
                        key: 'sale_price',
                        label: 'Precio venta',
                        render: (product) => formatMoney(product.sale_price),
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (product) => (
                            <Badge tone={product.is_active ? 'green' : 'red'}>
                                {product.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(product) => (
                    <>
                        <RowLink href={show.url(product.id)}>Ver</RowLink>
                        <Can permission="catalog.products.update">
                            <RowLink href={edit.url(product.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="catalog.products.delete">
                            <DeleteButton
                                url={destroy.url(product.id)}
                                confirmMessage="¿Eliminar este producto?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
