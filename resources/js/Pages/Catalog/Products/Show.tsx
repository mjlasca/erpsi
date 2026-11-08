import { Link } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Card,
    CardHeader,
    DeleteButton,
    LinkButton,
} from '@/Components/Ui/Primitives';
import { formatMoney, formatNumber } from '@/lib/format';
import type { Product } from '@/types/models';
import { destroy, edit, index } from '@/routes/catalog/products';
import { create as createVariant } from '@/routes/catalog/variants';
import { create as createAttribute } from '@/routes/catalog/attributes';

export default function ProductsShow({ product }: { product: Product }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={product.name}
                description={`SKU ${product.sku}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(product.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(product.id)}
                            confirmMessage="¿Eliminar este producto?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Tipo',
                        value: product.product_type_label,
                    },
                    {
                        label: 'Categoría',
                        value: product.category?.name ?? '—',
                    },
                    {
                        label: 'Unidad',
                        value: product.unit
                            ? `${product.unit.name} (${product.unit.abbreviation})`
                            : '—',
                    },
                    {
                        label: 'Marca',
                        value: product.brand ?? '—',
                    },
                    {
                        label: 'Código de barras',
                        value: product.barcode ?? '—',
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={product.is_active ? 'green' : 'red'}>
                                {product.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Costo',
                        value: formatMoney(product.cost_price),
                    },
                    {
                        label: 'Precio de venta',
                        value: formatMoney(product.sale_price),
                    },
                    {
                        label: 'Existencia mínima',
                        value: formatNumber(product.min_stock),
                    },
                    {
                        label: 'Inventariable',
                        value: product.is_inventory ? 'Sí' : 'No',
                    },
                    {
                        label: 'Maneja lotes',
                        value: product.track_lots ? 'Sí' : 'No',
                    },
                    {
                        label: 'Descripción',
                        value: product.description ?? '—',
                        fullWidth: true,
                    },
                ]}
            >
                <Card>
                    <CardHeader
                        title="Variantes"
                        action={
                            <Link
                                href={createVariant.url({
                                    query: { product_id: product.id },
                                })}
                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Agregar variante
                            </Link>
                        }
                    />
                    {!product.variants?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin variantes registradas.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {product.variants.map((variant) => (
                                <div
                                    key={variant.id}
                                    className="flex items-center justify-between gap-3 px-5 py-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {variant.name}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {variant.sku}
                                        </p>
                                    </div>
                                    <div className="text-right text-sm text-gray-600">
                                        {formatMoney(variant.sale_price)}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader
                        title="Atributos"
                        action={
                            <Link
                                href={createAttribute.url({
                                    query: { product_id: product.id },
                                })}
                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Agregar atributo
                            </Link>
                        }
                    />
                    {!product.attributes?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin atributos registrados.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {product.attributes.map((attribute) => (
                                <div
                                    key={attribute.id}
                                    className="flex items-center justify-between gap-3 px-5 py-3"
                                >
                                    <span className="text-sm font-medium text-gray-900">
                                        {attribute.name}
                                    </span>
                                    <span className="text-sm text-gray-600">
                                        {attribute.value}{' '}
                                        {attribute.unit?.abbreviation ?? ''}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </ResourceShow>
        </ErpLayout>
    );
}
