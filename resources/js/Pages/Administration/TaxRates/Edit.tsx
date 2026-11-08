import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { TaxRate } from '@/types/models';
import { index, update } from '@/routes/admin/tax-rates';

export default function TaxRatesEdit({ taxRate }: { taxRate: TaxRate }) {
    return (
        <ErpLayout>
            <ResourceForm
                title={`Editar ${taxRate.name}`}
                description="Actualiza la tasa de impuesto."
                action={update.url(taxRate.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                initial={taxRate as unknown as Record<string, unknown>}
                fields={[
                    {
                        name: 'name',
                        label: 'Nombre',
                        required: true,
                    },
                    {
                        name: 'rate',
                        label: 'Tasa (%)',
                        type: 'number',
                        step: '0.0001',
                        min: 0,
                        max: 100,
                        required: true,
                    },
                    {
                        name: 'tax_type',
                        label: 'Tipo',
                        placeholder: 'IVA, ISR, etc.',
                    },
                    {
                        name: 'jurisdiction',
                        label: 'Jurisdicción',
                    },
                    {
                        name: 'is_active',
                        label: 'Activo',
                        type: 'checkbox',
                        help: 'Permite usar este impuesto en nuevos documentos.',
                    },
                ]}
            />
        </ErpLayout>
    );
}
