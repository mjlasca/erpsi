import { useEffect, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { useHttp } from '@inertiajs/react';
import { Loader2, Search } from 'lucide-react';
import { cn } from '@/lib/utils';

export type RemoteOption<T = unknown> = {
    value: string;
    label: string;
    data: T;
};

export default function RemoteSelect<T>({
    id,
    value,
    onChange,
    searchUrl,
    mapOption,
    initialOptions = [],
    queryParams = {},
    placeholder = 'Buscar...',
    disabled = false,
    required = false,
    className,
    ariaLabel,
}: {
    id?: string;
    value: string | number | null | undefined;
    onChange: (value: string, option: RemoteOption<T> | null) => void;
    searchUrl: string;
    mapOption: (item: T) => RemoteOption<T>;
    initialOptions?: RemoteOption<T>[];
    queryParams?: Record<string, string | number | null | undefined>;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    className?: string;
    ariaLabel?: string;
}) {
    const { get } = useHttp<{ search: string }, { data: T[] }>({ search: '' });
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState<RemoteOption<T>[]>(initialOptions);
    const [selected, setSelected] = useState<RemoteOption<T> | null>(
        initialOptions.find(
            (option) => String(option.value) === String(value),
        ) ?? null,
    );
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const containerRef = useRef<HTMLDivElement>(null);
    const mapOptionRef = useRef(mapOption);
    const getRef = useRef(get);
    const queryParamsKey = JSON.stringify(queryParams);

    mapOptionRef.current = mapOption;
    getRef.current = get;

    useEffect(() => {
        if (value === null || value === undefined || value === '') {
            setSelected(null);
            return;
        }

        const match = [...initialOptions, ...options].find(
            (option) => String(option.value) === String(value),
        );

        if (match) {
            setSelected(match);
        }
    }, [value, initialOptions, options]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const search = query.trim();

        if (search.length < 3) {
            setOptions([]);
            setLoading(false);
            return;
        }

        const timeout = window.setTimeout(() => {
            const params = new URLSearchParams();

            Object.entries(queryParams).forEach(([key, parameterValue]) => {
                if (parameterValue !== null && parameterValue !== undefined) {
                    params.set(key, String(parameterValue));
                }
            });

            params.set('search', search);

            const separator = searchUrl.includes('?') ? '&' : '?';
            const url = params.toString()
                ? `${searchUrl}${separator}${params.toString()}`
                : searchUrl;

            setLoading(true);
            void getRef.current(url, {
                onSuccess: (response) => {
                    setOptions(
                        response.data.map((item) => mapOptionRef.current(item)),
                    );
                    setActiveIndex(0);
                },
                onFinish: () => setLoading(false),
            });
        }, 250);

        return () => window.clearTimeout(timeout);
    }, [open, query, searchUrl, queryParamsKey]);

    useEffect(() => {
        const closeOnOutsideClick = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
                setQuery('');
            }
        };

        document.addEventListener('mousedown', closeOnOutsideClick);

        return () =>
            document.removeEventListener('mousedown', closeOnOutsideClick);
    }, []);

    const trimmedQuery = query.trim();
    const belowMinimum =
        open && trimmedQuery.length > 0 && trimmedQuery.length < 3;

    const visibleOptions = useMemo(() => {
        if (belowMinimum) {
            return [];
        }

        const merged = [...initialOptions, ...options];
        const unique = new Map<string, RemoteOption<T>>();

        merged.forEach((option) => {
            unique.set(String(option.value), option);
        });

        return [...unique.values()];
    }, [initialOptions, options, belowMinimum]);

    const select = (option: RemoteOption<T>) => {
        setSelected(option);
        setQuery('');
        setOpen(false);
        onChange(String(option.value), option);
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActiveIndex((index) =>
                Math.min(index + 1, Math.max(visibleOptions.length - 1, 0)),
            );
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        }

        if (event.key === 'Enter' && open && visibleOptions[activeIndex]) {
            event.preventDefault();
            select(visibleOptions[activeIndex]);
        }

        if (event.key === 'Escape') {
            setOpen(false);
            setQuery('');
        }
    };

    return (
        <div ref={containerRef} className="relative">
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    id={id}
                    type="text"
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={`${id ?? 'remote'}-options`}
                    aria-autocomplete="list"
                    aria-label={ariaLabel}
                    aria-required={required}
                    disabled={disabled}
                    value={open ? query : (selected?.label ?? '')}
                    placeholder={placeholder}
                    onFocus={() => {
                        setOpen(true);
                        setQuery('');
                    }}
                    onChange={(event) => {
                        setOpen(true);
                        setQuery(event.target.value);
                    }}
                    onKeyDown={handleKeyDown}
                    className={cn(
                        'w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm text-gray-900 shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-gray-50',
                        className,
                    )}
                />
                {loading && (
                    <Loader2 className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-blue-500" />
                )}
            </div>

            {open && (
                <div
                    id={`${id ?? 'remote'}-options`}
                    role="listbox"
                    className="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >
                    {belowMinimum ? (
                        <p className="px-3 py-2 text-sm text-gray-500">
                            Escribe al menos 3 caracteres para buscar.
                        </p>
                    ) : loading && visibleOptions.length === 0 ? (
                        <p className="px-3 py-2 text-sm text-gray-500">
                            Buscando...
                        </p>
                    ) : visibleOptions.length === 0 ? (
                        <p className="px-3 py-2 text-sm text-gray-500">
                            {trimmedQuery.length === 0
                                ? 'Escribe al menos 3 caracteres para buscar.'
                                : 'Sin resultados.'}
                        </p>
                    ) : (
                        visibleOptions.map((option, index) => (
                            <button
                                key={option.value}
                                type="button"
                                role="option"
                                aria-selected={
                                    String(option.value) === String(value)
                                }
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => select(option)}
                                className={cn(
                                    'block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-blue-50',
                                    index === activeIndex && 'bg-blue-50',
                                )}
                            >
                                {option.label}
                            </button>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}
