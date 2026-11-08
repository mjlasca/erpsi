import type { ReactNode } from 'react';

export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links?: PaginatorLink[];
    current_page?: number;
    last_page?: number;
    total?: number;
    from?: number | null;
    to?: number | null;
    meta?: {
        current_page?: number;
        last_page?: number;
        total?: number;
        from?: number | null;
        to?: number | null;
        links?: PaginatorLink[];
    };
};

export type SelectOption = {
    value: string | number;
    label: string;
};

export type Column<T> = {
    key: string;
    label: string;
    render?: (row: T) => ReactNode;
    className?: string;
};

export type FormField = {
    name: string;
    label: string;
    type?:
        | 'text'
        | 'email'
        | 'password'
        | 'number'
        | 'date'
        | 'textarea'
        | 'select'
        | 'checkbox';
    options?: SelectOption[];
    required?: boolean;
    step?: string;
    min?: string | number;
    max?: string | number;
    placeholder?: string;
    help?: string;
    className?: string;
};

export type IdName = {
    id: number;
    name: string;
    [key: string]: unknown;
};

export type Filters = Record<
    string,
    string | number | boolean | null | undefined
>;

export function toSelectOptions(
    items: Array<{ id: number; name: string }>,
): SelectOption[] {
    return items.map((item) => ({ value: item.id, label: item.name }));
}
