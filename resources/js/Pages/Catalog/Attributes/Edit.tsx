import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { ProductAttribute, ProductVariant, Unit } from '@/types/models';
import { index, update } from '@/routes/catalog/attributes';

export default function AttributesEdit({
    attribute,
    products,
    variants,
    units,
}: {
    attribute: ProductAttribute;
    products: Array<{ id: number; name: string }>;
    variants: ProductVariant[];
    units: Unit[];
}) {
    const fields: FormField[] = [
        {
            name: 'product_id',
            label: 'Producto',
            type: 'select',
            required: true,
            options: products.map((product) => ({
                value: product.id,
                label: product.name,
            })),
        },
        {
            name: 'product_variant_id',
            label: 'Variante',
            type: 'select',
            options: variants.map((variant) => ({
                value: variant.id,
                label: `${variant.product?.name ?? ''} · ${variant.name}`,
            })),
        },
        {
            name: 'name',
            label: 'Nombre del atributo',
            required: true,
        },
        {
            name: 'value',
            label: 'Valor',
            required: true,
        },
        {
            name: 'unit_id',
            label: 'Unidad',
            type: 'select',
            options: units.map((unit) => ({
                value: unit.id,
                label: `${unit.name} (${unit.abbreviation})`,
            })),
        },
        {
            name: 'sort_order',
            label: 'Orden',
            type: 'number',
            min: '0',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title={`Editar ${attribute.name}`}
                fields={fields}
                initial={attribute as unknown as Record<string, unknown>}
                action={update.url(attribute.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`attribute:${attribute.id}`}
            />
        </ErpLayout>
    );
}
