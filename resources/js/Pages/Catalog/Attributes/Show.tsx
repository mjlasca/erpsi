import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import type { ProductAttribute } from '@/types/models';
import { destroy, edit, index } from '@/routes/catalog/attributes';

export default function AttributesShow({
    attribute,
}: {
    attribute: ProductAttribute;
}) {
    return (
        <ErpLayout>
            <ResourceShow
                title={attribute.name}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(attribute.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(attribute.id)}
                            confirmMessage="¿Eliminar este atributo?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Producto',
                        value: attribute.product?.name ?? '—',
                    },
                    {
                        label: 'Variante',
                        value: attribute.variant?.name ?? '—',
                    },
                    {
                        label: 'Valor',
                        value: `${attribute.value} ${attribute.unit?.abbreviation ?? ''}`.trim(),
                    },
                    {
                        label: 'Unidad',
                        value: attribute.unit?.name ?? '—',
                    },
                    {
                        label: 'Orden',
                        value: attribute.sort_order ?? 0,
                    },
                ]}
            />
        </ErpLayout>
    );
}
