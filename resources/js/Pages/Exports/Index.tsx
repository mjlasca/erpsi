import { usePoll } from '@inertiajs/react';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge } from '@/Components/Ui/Primitives';
import { formatDateTime } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import type { Paginated } from '@/types/erp';
import { download, index } from '@/routes/exports';

type ExportRow = {
    id: number;
    type: string;
    status: string;
    filename: string;
    error: string | null;
    expires_at: string | null;
    created_at: string;
    can_download: boolean;
};

const tone = (status: string) => {
    if (status === 'READY') return 'green' as const;
    if (status === 'FAILED') return 'red' as const;
    if (status === 'PROCESSING') return 'blue' as const;
    return 'amber' as const;
};

export default function ExportsIndex({
    exports,
    polling,
}: {
    exports: Paginated<ExportRow>;
    polling: boolean;
}) {
    const { t } = useTranslation();
    usePoll(5000, {}, { autoStart: polling });

    const statusLabel = (status: string) => matchStatus(status, t);

    return (
        <ErpLayout>
            <ResourceIndex
                title={t('exports.title')}
                description={t('exports.description')}
                items={exports}
                baseUrl={index.url()}
                filters={{}}
                columns={[
                    {
                        key: 'filename',
                        label: t('exports.filename'),
                        render: (row) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {row.filename}
                                </p>
                                {row.error && (
                                    <p className="text-xs text-red-600">
                                        {row.error}
                                    </p>
                                )}
                            </div>
                        ),
                    },
                    {
                        key: 'type',
                        label: t('exports.type'),
                        render: (row) => row.type,
                    },
                    {
                        key: 'status',
                        label: t('common.status'),
                        render: (row) => (
                            <Badge tone={tone(row.status)}>
                                {statusLabel(row.status)}
                            </Badge>
                        ),
                    },
                    {
                        key: 'created_at',
                        label: t('exports.created'),
                        render: (row) => formatDateTime(row.created_at),
                    },
                    {
                        key: 'expires_at',
                        label: t('exports.expires'),
                        render: (row) =>
                            row.expires_at
                                ? formatDateTime(row.expires_at)
                                : '—',
                    },
                ]}
                actions={(row) =>
                    row.can_download ? (
                        <a
                            href={download.url(row.id)}
                            className="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50"
                        >
                            {t('exports.download')}
                        </a>
                    ) : null
                }
                emptyMessage={t('exports.empty')}
            />
        </ErpLayout>
    );
}

function matchStatus(status: string, t: (key: string) => string): string {
    return (
        {
            PENDING: t('exports.pending'),
            PROCESSING: t('exports.processing'),
            READY: t('exports.ready'),
            FAILED: t('exports.failed'),
        }[status] ?? status
    );
}
