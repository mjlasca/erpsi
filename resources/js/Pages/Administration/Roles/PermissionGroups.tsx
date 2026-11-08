import { Checkbox } from '@/Components/Ui/Primitives';

export default function PermissionGroups({
    groups,
    selected,
    onChange,
    error,
}: {
    groups: Record<string, Record<string, string>>;
    selected: string[];
    onChange: (permissions: string[]) => void;
    error?: string;
}) {
    const hasAll = selected.includes('*');

    const toggle = (permission: string, checked: boolean) => {
        if (permission === '*') {
            onChange(checked ? ['*'] : []);
            return;
        }

        const next = checked
            ? [...selected.filter((item) => item !== '*'), permission]
            : selected.filter((item) => item !== permission);

        onChange(next);
    };

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                <Checkbox
                    checked={hasAll}
                    onChange={(event) => toggle('*', event.target.checked)}
                    label="Acceso total (todos los permisos)"
                />
            </div>

            {Object.entries(groups).map(([group, permissions]) => (
                <div key={group}>
                    <h3 className="mb-3 text-sm font-semibold text-gray-900">
                        {group}
                    </h3>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {Object.entries(permissions).map(
                            ([permission, label]) => (
                                <Checkbox
                                    key={permission}
                                    checked={
                                        hasAll || selected.includes(permission)
                                    }
                                    disabled={hasAll}
                                    onChange={(event) =>
                                        toggle(permission, event.target.checked)
                                    }
                                    label={label}
                                />
                            ),
                        )}
                    </div>
                </div>
            ))}

            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}
