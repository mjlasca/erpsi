import { router, usePage } from '@inertiajs/react';
import { switchMethod } from '@/routes/tenant';

type SharedProps = {
    tenant?: { id: number; name: string } | null;
    tenants?: Array<{ id: number; name: string }>;
};

export default function TenantSwitcher() {
    const page = usePage();
    const props = page.props as unknown as SharedProps;
    const tenants = props.tenants ?? [];
    const current = props.tenant;

    if (tenants.length <= 1) {
        return current ? (
            <p className="text-sm text-gray-500">{current.name}</p>
        ) : null;
    }

    return (
        <label className="flex items-center gap-2 text-sm text-gray-600">
            <span className="font-medium">Empresa</span>
            <select
                value={current?.id ?? ''}
                onChange={(event) =>
                    router.post(switchMethod.url(Number(event.target.value)))
                }
                className="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
                {tenants.map((tenant) => (
                    <option key={tenant.id} value={tenant.id}>
                        {tenant.name}
                    </option>
                ))}
            </select>
        </label>
    );
}
