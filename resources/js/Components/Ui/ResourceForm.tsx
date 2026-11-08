import type { FormEvent, ReactNode } from 'react';
import { Link, useForm } from '@inertiajs/react';
import {
    Button,
    Card,
    Checkbox,
    Field,
    Input,
    PageHeader,
    Select,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import type { FormField } from '@/types/erp';

type FormData = Record<string, string | boolean>;

function inputValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return '';
}

function initialData(
    fields: FormField[],
    initial: Record<string, unknown>,
): FormData {
    return fields.reduce<FormData>((data, field) => {
        const value = initial[field.name];

        if (field.type === 'checkbox') {
            data[field.name] = Boolean(value);
        } else {
            data[field.name] = inputValue(value);
        }

        return data;
    }, {});
}

export default function ResourceForm({
    title,
    description,
    fields,
    initial = {},
    action,
    method = 'post',
    cancelHref,
    submitLabel = 'Guardar',
    children,
    formKey,
}: {
    title: string;
    description?: string;
    fields: FormField[];
    initial?: Record<string, unknown>;
    action: string;
    method?: 'post' | 'put' | 'patch';
    cancelHref: string;
    submitLabel?: string;
    children?: ReactNode;
    formKey?: string;
}) {
    const form = useForm<FormData>(
        formKey ?? `${method}:${action}`,
        initialData(fields, initial),
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (method === 'put') {
            form.put(action);
        } else if (method === 'patch') {
            form.patch(action);
        } else {
            form.post(action);
        }
    };

    const renderField = (field: FormField) => {
        const value = form.data[field.name] ?? '';
        const error = form.errors[field.name];
        const id = `field-${field.name}`;

        if (field.type === 'checkbox') {
            return (
                <Field
                    key={field.name}
                    label={field.label}
                    htmlFor={id}
                    error={error}
                    className={field.className}
                >
                    <Checkbox
                        id={id}
                        checked={Boolean(value)}
                        onChange={(event) =>
                            form.setData(field.name, event.target.checked)
                        }
                        label={field.help ?? field.label}
                    />
                </Field>
            );
        }

        if (field.type === 'select') {
            return (
                <Field
                    key={field.name}
                    label={field.label}
                    htmlFor={id}
                    error={error}
                    required={field.required}
                    help={field.help}
                    className={field.className}
                >
                    <Select
                        id={id}
                        value={String(value)}
                        onChange={(event) =>
                            form.setData(field.name, event.target.value)
                        }
                    >
                        <option value="">
                            {field.placeholder ?? 'Selecciona una opción'}
                        </option>
                        {field.options?.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
            );
        }

        if (field.type === 'textarea') {
            return (
                <Field
                    key={field.name}
                    label={field.label}
                    htmlFor={id}
                    error={error}
                    required={field.required}
                    help={field.help}
                    className={field.className}
                >
                    <Textarea
                        id={id}
                        value={String(value)}
                        onChange={(event) =>
                            form.setData(field.name, event.target.value)
                        }
                        placeholder={field.placeholder}
                    />
                </Field>
            );
        }

        return (
            <Field
                key={field.name}
                label={field.label}
                htmlFor={id}
                error={error}
                required={field.required}
                help={field.help}
                className={field.className}
            >
                <Input
                    id={id}
                    type={field.type ?? 'text'}
                    value={String(value)}
                    onChange={(event) =>
                        form.setData(field.name, event.target.value)
                    }
                    placeholder={field.placeholder}
                    step={field.step}
                    min={field.min}
                    max={field.max}
                />
            </Field>
        );
    };

    return (
        <div>
            <PageHeader title={title} description={description} />

            <form onSubmit={submit}>
                <Card className="p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {fields.map(renderField)}
                    </div>

                    {children && (
                        <div className="mt-6 border-t border-gray-100 pt-6">
                            {children}
                        </div>
                    )}

                    {Object.keys(form.errors).length > 0 && (
                        <div className="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            Revisa los campos marcados en rojo.
                        </div>
                    )}

                    <div className="mt-6 flex items-center justify-end gap-2 border-t border-gray-100 pt-5">
                        <Link
                            href={cancelHref}
                            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancelar
                        </Link>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </div>
                </Card>
            </form>
        </div>
    );
}
