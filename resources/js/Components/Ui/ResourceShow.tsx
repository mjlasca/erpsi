import type { ReactNode } from 'react';
import { Card, PageHeader } from '@/Components/Ui/Primitives';

export type ShowField = {
    label: string;
    value: ReactNode;
    fullWidth?: boolean;
};

export default function ResourceShow({
    title,
    description,
    fields,
    actions,
    children,
}: {
    title: string;
    description?: string;
    fields: ShowField[];
    actions?: ReactNode;
    children?: ReactNode;
}) {
    return (
        <div>
            <PageHeader
                title={title}
                description={description}
                actions={actions}
            />

            <Card className="p-5">
                <dl className="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    {fields.map((field) => (
                        <div
                            key={field.label}
                            className={
                                field.fullWidth
                                    ? 'sm:col-span-2 lg:col-span-3'
                                    : ''
                            }
                        >
                            <dt className="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                {field.label}
                            </dt>
                            <dd className="mt-1 text-sm whitespace-pre-line text-gray-900">
                                {field.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </Card>

            {children && <div className="mt-6 space-y-6">{children}</div>}
        </div>
    );
}
