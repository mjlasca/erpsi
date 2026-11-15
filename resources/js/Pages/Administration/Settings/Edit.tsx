import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/admin/settings';

type Tenant = {
    id: number;
    name: string;
    slug: string;
    legal_name: string | null;
    tax_id: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    logo_path: string | null;
    locale: string;
    timezone: string;
    currency: string;
    date_format: string;
    week_starts_on: number;
    invoice_prefix: string;
    purchase_prefix: string;
    sale_return_prefix: string;
    purchase_return_prefix: string;
    cash_session_prefix: string;
    default_warehouse_id: number | null;
};

export default function TenantSettingsEdit({
    tenant,
    warehouses,
    supportedLocales,
    timezones,
}: {
    tenant: Tenant;
    warehouses: Array<{ id: number; name: string; code: string }>;
    supportedLocales: string[];
    timezones: string[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        name: tenant.name,
        slug: tenant.slug,
        legal_name: tenant.legal_name ?? '',
        tax_id: tenant.tax_id ?? '',
        email: tenant.email ?? '',
        phone: tenant.phone ?? '',
        address: tenant.address ?? '',
        locale: tenant.locale,
        timezone: tenant.timezone,
        currency: tenant.currency,
        date_format: tenant.date_format,
        week_starts_on: String(tenant.week_starts_on),
        invoice_prefix: tenant.invoice_prefix,
        purchase_prefix: tenant.purchase_prefix,
        sale_return_prefix: tenant.sale_return_prefix,
        purchase_return_prefix: tenant.purchase_return_prefix,
        cash_session_prefix: tenant.cash_session_prefix,
        default_warehouse_id: tenant.default_warehouse_id
            ? String(tenant.default_warehouse_id)
            : '',
        logo: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, _method: 'put' }));
        form.post(update.url(), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const input = (
        name: keyof typeof form.data,
        label: string,
        type = 'text',
    ) => (
        <Field label={label} htmlFor={String(name)} error={form.errors[name]}>
            <Input
                id={String(name)}
                type={type}
                value={
                    typeof form.data[name] === 'string' ||
                    typeof form.data[name] === 'number'
                        ? String(form.data[name])
                        : ''
                }
                onChange={(event) =>
                    form.setData(name, event.target.value as never)
                }
            />
        </Field>
    );

    return (
        <ErpLayout>
            <PageHeader
                title={t('settings.company')}
                description={t('settings.companyDescription')}
            />
            <form onSubmit={submit}>
                <Card className="p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {input('name', t('settings.name'))}
                        {input('slug', t('settings.slug'))}
                        {input('legal_name', t('settings.legalName'))}
                        {input('tax_id', t('settings.taxId'))}
                        {input('email', t('settings.email'), 'email')}
                        {input('phone', t('settings.phone'))}
                        {input('address', t('settings.address'))}

                        <Field
                            label={t('settings.locale')}
                            htmlFor="locale"
                            error={form.errors.locale}
                        >
                            <Select
                                id="locale"
                                value={form.data.locale}
                                onChange={(event) =>
                                    form.setData('locale', event.target.value)
                                }
                            >
                                {supportedLocales.map((locale) => (
                                    <option key={locale} value={locale}>
                                        {locale === 'es'
                                            ? 'Español'
                                            : 'English'}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field
                            label={t('settings.timezone')}
                            htmlFor="timezone"
                            error={form.errors.timezone}
                        >
                            <Select
                                id="timezone"
                                value={form.data.timezone}
                                onChange={(event) =>
                                    form.setData('timezone', event.target.value)
                                }
                            >
                                {timezones.map((timezone) => (
                                    <option key={timezone} value={timezone}>
                                        {timezone}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        {input('currency', t('settings.currency'))}
                        {input('date_format', t('settings.dateFormat'))}
                        {input('invoice_prefix', t('settings.invoicePrefix'))}
                        {input('purchase_prefix', t('settings.purchasePrefix'))}
                        {input(
                            'sale_return_prefix',
                            t('settings.saleReturnPrefix'),
                        )}
                        {input(
                            'purchase_return_prefix',
                            t('settings.purchaseReturnPrefix'),
                        )}
                        {input(
                            'cash_session_prefix',
                            t('settings.cashSessionPrefix'),
                        )}

                        <Field
                            label={t('settings.weekStartsOn')}
                            htmlFor="week_starts_on"
                            error={form.errors.week_starts_on}
                        >
                            <Select
                                id="week_starts_on"
                                value={form.data.week_starts_on}
                                onChange={(event) =>
                                    form.setData(
                                        'week_starts_on',
                                        event.target.value,
                                    )
                                }
                            >
                                {[
                                    ['1', 'Lunes / Monday'],
                                    ['0', 'Domingo / Sunday'],
                                ].map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field
                            label={t('settings.defaultWarehouse')}
                            htmlFor="default_warehouse_id"
                            error={form.errors.default_warehouse_id}
                        >
                            <Select
                                id="default_warehouse_id"
                                value={form.data.default_warehouse_id}
                                onChange={(event) =>
                                    form.setData(
                                        'default_warehouse_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">—</option>
                                {warehouses.map((warehouse) => (
                                    <option
                                        key={warehouse.id}
                                        value={warehouse.id}
                                    >
                                        {warehouse.code} · {warehouse.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field
                            label={t('settings.logo')}
                            htmlFor="logo"
                            error={form.errors.logo}
                        >
                            <input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={(event) =>
                                    form.setData(
                                        'logo',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                                className="block w-full text-sm text-gray-600"
                            />
                            {tenant.logo_path && (
                                <p className="mt-1 text-xs text-gray-500">
                                    {tenant.logo_path}
                                </p>
                            )}
                        </Field>
                    </div>

                    <div className="mt-6 flex justify-end border-t border-gray-100 pt-5">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t('common.save')}
                        </Button>
                    </div>
                </Card>
            </form>
        </ErpLayout>
    );
}
