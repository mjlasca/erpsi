import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import { formatMoney } from '@/lib/format';
import type { ProductVariant } from '@/types/models';
import { destroy, edit, index } from '@/routes/catalog/variants';

export default function VariantsShow({ variant }: { variant: ProductVariant }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={variant.name}
                description={variant.sku}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <Can permission="catalog.variants.update">
                            <LinkButton href={edit.url(variant.id)}>
                                Editar
                            </LinkButton>
                        </Can>
                        <Can permission="catalog.variants.delete">
                            <DeleteButton
                                url={destroy.url(variant.id)}
                                confirmMessage="¿Eliminar esta variante?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    {
                        label: 'Producto',
                        value: variant.product?.name ?? '—',
                    },
                    {
                        label: 'SKU',
                        value: variant.sku,
                    },
                    {
                        label: 'Código de barras',
                        value: variant.barcode ?? '—',
                    },
                    {
                        label: 'Costo',
                        value: formatMoney(variant.cost_price),
                    },
                    {
                        label: 'Precio de venta',
                        value: formatMoney(variant.sale_price),
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={variant.is_active ? 'green' : 'red'}>
                                {variant.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                ]}
            />
        </ErpLayout>
    );
}
