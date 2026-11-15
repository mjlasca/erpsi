import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import { useTranslation } from '@/lib/i18n';
import { index, store } from '@/routes/cash/sessions';

export default function CashSessionsCreate({
    warehouses,
}: {
    warehouses: Array<{ id: number; name: string; code: string }>;
}) {
    const { t } = useTranslation();
    const form = useForm({
        warehouse_id: warehouses.length === 1 ? String(warehouses[0].id) : '',
        opening_balance: '0',
        notes: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <ErpLayout>
            <PageHeader
                title={t('cashSessions.open')}
                description={t('cashSessions.description')}
            />
            <form onSubmit={submit}>
                <Card className="max-w-3xl p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label={t('dashboard.warehouse')}
                            htmlFor="warehouse_id"
                            error={form.errors.warehouse_id}
                        >
                            <Select
                                id="warehouse_id"
                                value={form.data.warehouse_id}
                                onChange={(event) =>
                                    form.setData(
                                        'warehouse_id',
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
                            label={t('cashSessions.openingBalance')}
                            htmlFor="opening_balance"
                            error={form.errors.opening_balance}
                        >
                            <Input
                                id="opening_balance"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.opening_balance}
                                onChange={(event) =>
                                    form.setData(
                                        'opening_balance',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>

                        <Field
                            label={t('common.notes')}
                            htmlFor="notes"
                            error={form.errors.notes}
                            className="md:col-span-2"
                        >
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <div className="mt-6 flex justify-end gap-2 border-t border-gray-100 pt-5">
                        <Link
                            href={index.url()}
                            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {t('common.cancel')}
                        </Link>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t('cashSessions.open')}
                        </Button>
                    </div>
                </Card>
            </form>
        </ErpLayout>
    );
}
