import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    Field,
    PageHeader,
    Select,
    Spinner,
} from '@/Components/Ui/Primitives';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/preferences';

export default function PreferencesEdit({
    preferences,
    supportedLocales,
    timezones,
}: {
    preferences: { locale: string | null; timezone: string | null };
    supportedLocales: string[];
    timezones: string[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        locale: preferences.locale ?? '',
        timezone: preferences.timezone ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <ErpLayout>
            <PageHeader
                title={t('preferences.title')}
                description={t('preferences.description')}
            />
            <form onSubmit={submit}>
                <Card className="max-w-3xl p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label={t('preferences.locale')}
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
                                <option value="">
                                    {t('preferences.inherit')}
                                </option>
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
                            label={t('preferences.timezone')}
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
                                <option value="">
                                    {t('preferences.inherit')}
                                </option>
                                {timezones.map((timezone) => (
                                    <option key={timezone} value={timezone}>
                                        {timezone}
                                    </option>
                                ))}
                            </Select>
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
