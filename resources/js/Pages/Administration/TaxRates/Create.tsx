import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import { index, store } from '@/routes/admin/tax-rates';

export default function TaxRatesCreate() {
    return (
        <ErpLayout>
            <ResourceForm
                title="Nuevo impuesto"
                description="Registra una tasa de impuesto."
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear impuesto"
                initial={{ is_active: true }}
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
