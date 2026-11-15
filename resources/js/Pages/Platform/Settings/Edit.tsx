import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import {
    Button,
    Card,
    Checkbox,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { update } from '@/routes/platform/settings';

export default function PlatformSettingsEdit({
    settings,
    timezones,
}: {
    settings: {
        platform_name: string;
        support_email: string | null;
        default_locale: string;
        default_timezone: string;
        default_currency: string;
        default_trial_days: number;
        supported_locales: string[];
    };
    timezones: string[];
}) {
    const form = useForm({
        platform_name: settings.platform_name,
        support_email: settings.support_email ?? '',
        default_locale: settings.default_locale,
        default_timezone: settings.default_timezone,
        default_currency: settings.default_currency,
        default_trial_days: String(settings.default_trial_days),
        supported_locales: settings.supported_locales,
    });

    const toggleLocale = (locale: string, checked: boolean) => {
        form.setData(
            'supported_locales',
            checked
                ? [...form.data.supported_locales, locale]
                : form.data.supported_locales.filter((item) => item !== locale),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <PlatformLayout>
            <PageHeader
                title="Configuración global"
                description="Valores predeterminados para nuevas empresas y funcionamiento de la plataforma."
            />
            <form onSubmit={submit}>
                <Card className="max-w-3xl p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label="Nombre de la plataforma"
                            htmlFor="platform_name"
                            error={form.errors.platform_name}
                        >
                            <Input
                                id="platform_name"
                                value={form.data.platform_name}
                                onChange={(event) =>
                                    form.setData(
                                        'platform_name',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Correo de soporte"
                            htmlFor="support_email"
                            error={form.errors.support_email}
                        >
                            <Input
                                id="support_email"
                                type="email"
                                value={form.data.support_email}
                                onChange={(event) =>
                                    form.setData(
                                        'support_email',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Idioma predeterminado"
                            htmlFor="default_locale"
                            error={form.errors.default_locale}
                        >
                            <Select
                                id="default_locale"
                                value={form.data.default_locale}
                                onChange={(event) =>
                                    form.setData(
                                        'default_locale',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="es">Español</option>
                                <option value="en">English</option>
                            </Select>
                        </Field>
                        <Field
                            label="Zona horaria predeterminada"
                            htmlFor="default_timezone"
                            error={form.errors.default_timezone}
                        >
                            <Select
                                id="default_timezone"
                                value={form.data.default_timezone}
                                onChange={(event) =>
                                    form.setData(
                                        'default_timezone',
                                        event.target.value,
                                    )
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
                            label="Moneda predeterminada"
                            htmlFor="default_currency"
                            error={form.errors.default_currency}
                        >
                            <Input
                                id="default_currency"
                                maxLength={3}
                                value={form.data.default_currency}
                                onChange={(event) =>
                                    form.setData(
                                        'default_currency',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Días de prueba predeterminados"
                            htmlFor="default_trial_days"
                            error={form.errors.default_trial_days}
                        >
                            <Input
                                id="default_trial_days"
                                type="number"
                                min="0"
                                max="365"
                                value={form.data.default_trial_days}
                                onChange={(event) =>
                                    form.setData(
                                        'default_trial_days',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Idiomas habilitados"
                            error={
                                form.errors['supported_locales'] as
                                    | string
                                    | undefined
                            }
                            className="md:col-span-2"
                        >
                            <div className="flex gap-6">
                                {['es', 'en'].map((locale) => (
                                    <Checkbox
                                        key={locale}
                                        label={
                                            locale === 'es'
                                                ? 'Español'
                                                : 'English'
                                        }
                                        checked={form.data.supported_locales.includes(
                                            locale,
                                        )}
                                        onChange={(event) =>
                                            toggleLocale(
                                                locale,
                                                event.target.checked,
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </Field>
                    </div>

                    <div className="mt-6 flex justify-end border-t border-gray-100 pt-5">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Guardar configuración
                        </Button>
                    </div>
                </Card>
            </form>
        </PlatformLayout>
    );
}
