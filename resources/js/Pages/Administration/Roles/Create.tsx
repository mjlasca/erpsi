import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Button,
    Card,
    Checkbox,
    Field,
    Input,
    PageHeader,
    Spinner,
} from '@/Components/Ui/Primitives';
import PermissionGroups from './PermissionGroups';
import { index, store } from '@/routes/admin/roles';

type RoleFormData = {
    name: string;
    permissions: string[];
    is_active: boolean;
};

export default function RolesCreate({
    permissionGroups,
}: {
    permissionGroups: Record<string, Record<string, string>>;
}) {
    const form = useForm<RoleFormData>({
        name: '',
        permissions: [],
        is_active: true,
    });

    const errorFor = (key: string) =>
        (form.errors as Record<string, string>)[key] ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <ErpLayout>
            <PageHeader
                title="Nuevo rol"
                description="Define el nombre y los permisos del rol."
            />

            <form onSubmit={submit} className="space-y-6">
                <Card className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <Field
                        label="Nombre"
                        htmlFor="name"
                        required
                        error={errorFor('name')}
                    >
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Estado"
                        htmlFor="is_active"
                        error={errorFor('is_active')}
                    >
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData('is_active', event.target.checked)
                            }
                            label="Rol activo"
                        />
                    </Field>
                </Card>

                <Card className="p-5">
                    <PermissionGroups
                        groups={permissionGroups}
                        selected={form.data.permissions}
                        onChange={(permissions) =>
                            form.setData('permissions', permissions)
                        }
                        error={errorFor('permissions')}
                    />
                </Card>

                <div className="flex items-center justify-end gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Crear rol
                    </Button>
                </div>
            </form>
        </ErpLayout>
    );
}
