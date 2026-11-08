import type { FormEvent, ReactNode } from 'react';
import { Link, router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import {
    Card,
    EmptyState,
    Input,
    LinkButton,
    PageHeader,
    Pagination,
    Select,
} from '@/Components/Ui/Primitives';
import { text } from '@/lib/format';
import { useCan } from '@/lib/permissions';
import type { Column, Filters, Paginated } from '@/types/erp';

export default function ResourceIndex<T extends { id: number }>({
    title,
    description,
    items,
    columns,
    baseUrl,
    filters = {},
    searchPlaceholder = 'Buscar...',
    filterControls,
    createHref,
    createPermission,
    createLabel = 'Nuevo registro',
    actions,
    emptyMessage,
}: {
    title: string;
    description?: string;
    items: Paginated<T>;
    columns: Column<T>[];
    baseUrl: string;
    filters?: Filters;
    searchPlaceholder?: string;
    filterControls?: ReactNode;
    createHref?: string;
    createPermission?: string;
    createLabel?: string;
    actions?: (row: T) => ReactNode;
    emptyMessage?: string;
}) {
    const can = useCan();
    const showCreate = Boolean(
        createHref && (!createPermission || can(createPermission)),
    );

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const data = Object.fromEntries(
            new FormData(event.currentTarget).entries(),
        ) as Filters;

        router.get(baseUrl, data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const hasFilters = Object.keys(filters).length > 0;

    return (
        <div>
            <PageHeader
                title={title}
                description={description}
                actions={
                    showCreate && createHref ? (
                        <LinkButton href={createHref}>{createLabel}</LinkButton>
                    ) : undefined
                }
            />

            <Card>
                <form
                    onSubmit={submit}
                    className="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4"
                >
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            name="search"
                            defaultValue={filters.search?.toString() ?? ''}
                            placeholder={searchPlaceholder}
                            className="pl-9"
                        />
                    </div>

                    {filterControls}

                    <button
                        type="submit"
                        className="inline-flex h-10 items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                    >
                        Filtrar
                    </button>

                    {hasFilters && (
                        <Link
                            href={baseUrl}
                            className="inline-flex h-10 items-center gap-1 rounded-lg px-3 text-sm font-medium text-gray-500 transition-colors hover:bg-gray-100"
                        >
                            <X className="h-4 w-4" />
                            Limpiar
                        </Link>
                    )}
                </form>

                {items.data.length === 0 ? (
                    <EmptyState message={emptyMessage} />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead className="bg-gray-50">
                                <tr>
                                    {columns.map((column) => (
                                        <th
                                            key={column.key}
                                            className={`px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase ${column.className ?? ''}`}
                                        >
                                            {column.label}
                                        </th>
                                    ))}
                                    {actions && (
                                        <th className="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            Acciones
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {items.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="transition-colors hover:bg-gray-50/70"
                                    >
                                        {columns.map((column) => (
                                            <td
                                                key={column.key}
                                                className={`px-5 py-3 text-sm text-gray-700 ${column.className ?? ''}`}
                                            >
                                                {column.render
                                                    ? column.render(row)
                                                    : text(
                                                          (
                                                              row as Record<
                                                                  string,
                                                                  unknown
                                                              >
                                                          )[column.key],
                                                      )}
                                            </td>
                                        ))}
                                        {actions && (
                                            <td className="px-5 py-3 text-right whitespace-nowrap">
                                                <div className="flex items-center justify-end gap-1">
                                                    {actions(row)}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination paginator={items} />
            </Card>
        </div>
    );
}

export function FilterSelect({
    name,
    value,
    placeholder,
    options,
}: {
    name: string;
    value?: string | number | boolean | null;
    placeholder: string;
    options: Array<{ value: string | number; label: string }>;
}) {
    return (
        <Select
            name={name}
            defaultValue={value?.toString() ?? ''}
            className="h-10 w-44"
        >
            <option value="">{placeholder}</option>
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </Select>
    );
}
