import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { index, store } from '@/routes/platform/tenants';

type Plan = {
    id: number;
    name: string;
    price: string;
    currency: string;
    max_users: number | null;
    max_products: number | null;
};

export default function PlatformTenantsCreate({
    plans,
    users,
    timezones,
    defaults,
}: {
    plans: Plan[];
    users: Array<{ id: number; name: string; email: string }>;
    timezones: string[];
    defaults: {
        locale: string;
        timezone: string;
        currency: string;
        trial_days: number;
    };
}) {
    const form = useForm({
        name: '',
        slug: '',
        legal_name: '',
        tax_id: '',
        email: '',
        phone: '',
        address: '',
        locale: defaults.locale,
        timezone: defaults.timezone,
        currency: defaults.currency,
        plan_id: plans[0] ? String(plans[0].id) : '',
        owner_id: '',
        billing_email: '',
        trial_days: String(defaults.trial_days),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <PlatformLayout>
            <PageHeader
                title="Nueva empresa"
                description="Crea la empresa, sus roles base y su almacén principal."
                actions={
                    <Link
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Volver
                    </Link>
                }
            />
            <form onSubmit={submit}>
                <Card className="p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label="Nombre comercial"
                            htmlFor="name"
                            required
                            error={form.errors.name}
                        >
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Identificador (slug)"
                            htmlFor="slug"
                            required
                            error={form.errors.slug}
                        >
                            <Input
                                id="slug"
                                value={form.data.slug}
                                onChange={(e) =>
                                    form.setData('slug', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Razón social"
                            htmlFor="legal_name"
                            error={form.errors.legal_name}
                        >
                            <Input
                                id="legal_name"
                                value={form.data.legal_name}
                                onChange={(e) =>
                                    form.setData('legal_name', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Identificación fiscal"
                            htmlFor="tax_id"
                            error={form.errors.tax_id}
                        >
                            <Input
                                id="tax_id"
                                value={form.data.tax_id}
                                onChange={(e) =>
                                    form.setData('tax_id', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Correo"
                            htmlFor="email"
                            error={form.errors.email}
                        >
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Teléfono"
                            htmlFor="phone"
                            error={form.errors.phone}
                        >
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Dirección"
                            htmlFor="address"
                            error={form.errors.address}
                            className="md:col-span-2"
                        >
                            <Input
                                id="address"
                                value={form.data.address}
                                onChange={(e) =>
                                    form.setData('address', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Plan"
                            htmlFor="plan_id"
                            error={form.errors.plan_id}
                        >
                            <Select
                                id="plan_id"
                                value={form.data.plan_id}
                                onChange={(e) =>
                                    form.setData('plan_id', e.target.value)
                                }
                            >
                                <option value="">Sin plan</option>
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>
                                        {plan.name} · {plan.price}{' '}
                                        {plan.currency}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field
                            label="Propietario"
                            htmlFor="owner_id"
                            error={form.errors.owner_id}
                        >
                            <Select
                                id="owner_id"
                                value={form.data.owner_id}
                                onChange={(e) =>
                                    form.setData('owner_id', e.target.value)
                                }
                            >
                                <option value="">Asignar después</option>
                                {users.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name} · {user.email}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field
                            label="Idioma"
                            htmlFor="locale"
                            error={form.errors.locale}
                        >
                            <Select
                                id="locale"
                                value={form.data.locale}
                                onChange={(e) =>
                                    form.setData('locale', e.target.value)
                                }
                            >
                                <option value="es">Español</option>
                                <option value="en">English</option>
                            </Select>
                        </Field>
                        <Field
                            label="Zona horaria"
                            htmlFor="timezone"
                            error={form.errors.timezone}
                        >
                            <Select
                                id="timezone"
                                value={form.data.timezone}
                                onChange={(e) =>
                                    form.setData('timezone', e.target.value)
                                }
                            >
                                {timezones.map((timezone) => (
                                    <option key={timezone} value={timezone}>
                                        {timezone}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field
                            label="Moneda"
                            htmlFor="currency"
                            error={form.errors.currency}
                        >
                            <Input
                                id="currency"
                                maxLength={3}
                                value={form.data.currency}
                                onChange={(e) =>
                                    form.setData(
                                        'currency',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Días de prueba"
                            htmlFor="trial_days"
                            error={form.errors.trial_days}
                        >
                            <Input
                                id="trial_days"
                                type="number"
                                min="0"
                                max="365"
                                value={form.data.trial_days}
                                onChange={(e) =>
                                    form.setData('trial_days', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Correo de facturación"
                            htmlFor="billing_email"
                            error={form.errors.billing_email}
                        >
                            <Input
                                id="billing_email"
                                type="email"
                                value={form.data.billing_email}
                                onChange={(e) =>
                                    form.setData(
                                        'billing_email',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <div className="mt-6 flex justify-end gap-2 border-t border-gray-100 pt-5">
                        <Link
                            href={index.url()}
                            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </Link>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Crear empresa
                        </Button>
                    </div>
                </Card>
            </form>
        </PlatformLayout>
    );
}
