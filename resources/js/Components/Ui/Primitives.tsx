import { useEffect, useState } from 'react';
import type {
    ButtonHTMLAttributes,
    InputHTMLAttributes,
    ReactNode,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Inbox,
    Loader2,
} from 'lucide-react';
import type { Paginated, PaginatorLink } from '@/types/erp';
import { cn } from '@/lib/utils';

const buttonVariants = {
    primary:
        'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:bg-blue-300',
    secondary:
        'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 disabled:bg-red-300',
    ghost: 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:ring-gray-400',
} as const;

export function Button({
    variant = 'primary',
    className,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: keyof typeof buttonVariants;
}) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed',
                buttonVariants[variant],
                className,
            )}
            {...props}
        />
    );
}

export function LinkButton({
    href,
    variant = 'primary',
    className,
    children,
}: {
    href: string;
    variant?: keyof typeof buttonVariants;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none',
                buttonVariants[variant],
                className,
            )}
        >
            {children}
        </Link>
    );
}

export function Field({
    label,
    htmlFor,
    error,
    required,
    help,
    children,
    className,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    required?: boolean;
    help?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <label
                htmlFor={htmlFor}
                className="block text-sm font-medium text-gray-700"
            >
                {label}
                {required && <span className="ml-1 text-red-500">*</span>}
            </label>
            {children}
            {help && <p className="text-xs text-gray-500">{help}</p>}
            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}

const controlClass =
    'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-gray-50';

export function Input({
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return <input className={cn(controlClass, className)} {...props} />;
}

export function Textarea({
    className,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            className={cn(controlClass, 'min-h-24 resize-y', className)}
            {...props}
        />
    );
}

export function Select({
    className,
    children,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select className={cn(controlClass, className)} {...props}>
            {children}
        </select>
    );
}

export function Checkbox({
    label,
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement> & { label: string }) {
    return (
        <label
            className={cn(
                'flex cursor-pointer items-center gap-2 text-sm text-gray-700',
                className,
            )}
        >
            <input
                type="checkbox"
                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                {...props}
            />
            {label}
        </label>
    );
}

export function Card({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'rounded-xl border border-gray-200 bg-white shadow-sm',
                className,
            )}
        >
            {children}
        </section>
    );
}

export function CardHeader({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div>
                <h2 className="text-base font-semibold text-gray-900">
                    {title}
                </h2>
                {description && (
                    <p className="mt-0.5 text-sm text-gray-500">
                        {description}
                    </p>
                )}
            </div>
            {action}
        </div>
    );
}

export function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <Head title={title} />
                <h1 className="text-2xl font-bold tracking-tight text-gray-900">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-gray-500">{description}</p>
                )}
            </div>
            {actions && (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </div>
    );
}

export function Badge({
    children,
    tone = 'gray',
}: {
    children: ReactNode;
    tone?: 'gray' | 'green' | 'red' | 'blue' | 'amber';
}) {
    const tones = {
        gray: 'bg-gray-100 text-gray-700',
        green: 'bg-emerald-100 text-emerald-700',
        red: 'bg-red-100 text-red-700',
        blue: 'bg-blue-100 text-blue-700',
        amber: 'bg-amber-100 text-amber-700',
    };

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                tones[tone],
            )}
        >
            {children}
        </span>
    );
}

export function Alert({
    tone,
    children,
}: {
    tone: 'success' | 'error';
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mb-4 flex items-start gap-2 rounded-lg border px-4 py-3 text-sm',
                tone === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800',
            )}
        >
            {tone === 'success' ? (
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
            ) : (
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
            )}
            <div>{children}</div>
        </div>
    );
}

export function EmptyState({
    message = 'No hay registros para mostrar.',
}: {
    message?: string;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <Inbox className="mb-3 h-10 w-10 text-gray-300" />
            <p className="text-sm text-gray-500">{message}</p>
        </div>
    );
}

export function Spinner() {
    return <Loader2 className="h-4 w-4 animate-spin" />;
}

export function Pagination({ paginator }: { paginator: Paginated<unknown> }) {
    const links = (
        Array.isArray(paginator.links)
            ? paginator.links
            : (paginator.meta?.links ?? [])
    ).filter((link): link is PaginatorLink => Boolean(link));

    const current = paginator.current_page ?? paginator.meta?.current_page ?? 1;
    const last = paginator.last_page ?? paginator.meta?.last_page ?? 1;
    const total = paginator.total ?? paginator.meta?.total;

    if (last <= 1) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-4">
            <p className="text-sm text-gray-500">
                Página {current} de {last}
                {typeof total === 'number' && ` · ${total} registros`}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {links.map((link, index) => {
                    const label = link.label
                        .replace('&laquo;', '«')
                        .replace('&raquo;', '»');

                    if (!link.url) {
                        return (
                            <span
                                key={`${label}-${index}`}
                                className="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm text-gray-300"
                            >
                                {index === 0 ? (
                                    <ChevronLeft className="h-4 w-4" />
                                ) : index === links.length - 1 ? (
                                    <ChevronRight className="h-4 w-4" />
                                ) : (
                                    label
                                )}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={`${label}-${index}`}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100',
                            )}
                        >
                            {index === 0 ? (
                                <ChevronLeft className="h-4 w-4" />
                            ) : index === links.length - 1 ? (
                                <ChevronRight className="h-4 w-4" />
                            ) : (
                                label
                            )}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}

export function ConfirmDialog({
    open,
    title = 'Confirmar acción',
    message,
    confirmLabel = 'Confirmar',
    tone = 'danger',
    onConfirm,
    onCancel,
}: {
    open: boolean;
    title?: string;
    message: string;
    confirmLabel?: string;
    tone?: 'danger' | 'primary';
    onConfirm: () => void;
    onCancel: () => void;
}) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onCancel();
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [open, onCancel]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4 text-left"
            onClick={onCancel}
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-dialog-title"
                className="w-full max-w-md rounded-xl bg-white p-6 text-left shadow-xl"
                onClick={(event) => event.stopPropagation()}
            >
                <h2
                    id="confirm-dialog-title"
                    className="text-lg font-semibold text-gray-900"
                >
                    {title}
                </h2>
                <p className="mt-2 text-sm text-gray-600">{message}</p>

                <div className="mt-6 flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={onCancel}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        variant={tone === 'danger' ? 'danger' : 'primary'}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </div>
        </div>
    );
}

export function ConfirmButton({
    message,
    title,
    confirmLabel,
    tone = 'danger',
    variant = 'danger',
    onConfirm,
    children,
    ...props
}: {
    message: string;
    title?: string;
    confirmLabel?: string;
    tone?: 'danger' | 'primary';
    variant?: keyof typeof buttonVariants;
    onConfirm: () => void;
} & ButtonHTMLAttributes<HTMLButtonElement>) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button
                type="button"
                variant={variant}
                onClick={() => setOpen(true)}
                {...props}
            >
                {children}
            </Button>
            <ConfirmDialog
                open={open}
                title={title}
                message={message}
                confirmLabel={confirmLabel}
                tone={tone}
                onConfirm={() => {
                    setOpen(false);
                    onConfirm();
                }}
                onCancel={() => setOpen(false)}
            />
        </>
    );
}

export function DeleteButton({
    url,
    label = 'Eliminar',
    confirmMessage = '¿Confirmas que deseas eliminar este registro?',
}: {
    url: string;
    label?: string;
    confirmMessage?: string;
}) {
    return (
        <ConfirmButton
            message={confirmMessage}
            confirmLabel="Eliminar"
            onConfirm={() => router.delete(url, { preserveScroll: true })}
            className="px-3 py-1.5"
        >
            {label}
        </ConfirmButton>
    );
}

export function RowLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50"
        >
            {children}
        </Link>
    );
}
